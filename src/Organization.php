<?php

namespace duncan3dc\GitHub;

use GuzzleHttp\ClientInterface;
use GuzzleHttp\Psr7;
use Psr\Http\Message\ResponseInterface;
use Psr\SimpleCache\CacheInterface;
use function array_key_exists;
use function count;
use function print_r;
use function sha1;
use function strtotime;
use function substr;
use function time;
use function trim;

final class Organization implements OrganizationInterface
{
    use HttpTrait;

    /**
     * @var \stdClass This organization's data returned from the API.
     */
    private $data;

    /**
     * @var ApiInterface The GitHub app this installation is for.
     */
    private $api;

    /**
     * @var ClientInterface The HTTP client to communicate via.
     */
    private $client;

    /** @var CacheInterface|null */
    private $cache;

    /**
     * @var string The current installation access token.
     */
    private $token = "";

    /**
     * @var int $tokenExpires When the current installation access token expires.
     */
    private $tokenExpires = 0;


    /**
     * Create a new instance.
     *
     * @var \stdClass $data This organization's data returned from the API
     * @param ApiInterface $api The GitHub app this installation is for
     * @param ClientInterface $client The HTTP client to communicate via
     * @param CacheInterface $cache
     */
    public function __construct(\stdClass $data, ApiInterface $api, ClientInterface $client, CacheInterface $cache = null)
    {
        $this->data = $data;
        $this->api = $api;
        $this->client = $client;
        $this->cache = $cache;
    }


    public function getName(): string
    {
        return $this->data->account->login;
    }


    /**
     * @inheritDoc
     */
    public function request(string $method, string $url, array $data = []): ResponseInterface
    {
        $params = [
            "headers" => [
                "Authorization" => "token " . $this->getToken(),
            ],
        ];

        if (count($data) > 0) {
            if ($method === "GET") {
                $params["query"] = $data;
            } else {
                $params["json"] = $data;
            }
        }

        if (substr($url, 0, 5) !== "https") {
            $url = "https://api.github.com/" . trim($url, "/");
        }

        $response = $this->cache($method, $url, $params);

        return $response;
    }


    /**
     * @param string $method
     * @param string $url
     * @param array $params
     *
     * @return ResponseInterface
     */
    private function cache(string $method, string $url, array $params): ResponseInterface
    {
        if ($this->cache === null || $method !== "GET") {
            return $this->client->request($method, $url, $params);
        }

        $content = "{$method}_{$url}_";
        if (array_key_exists("query", $params)) {
            $content .= print_r($params["query"], true);
        }
        $cacheKey = sha1($content);

        $cachedResponse = $this->cache->get($cacheKey);

        if ($cachedResponse) {
            $cachedResponse = Psr7\parse_response($cachedResponse);
            $params["headers"]["If-None-Match"] = $cachedResponse->getHeaderLine("ETag");
        }

        $response = $this->client->request($method, $url, $params);

        if ($response->getStatusCode() === 304) {
            return $cachedResponse;
        }

        if ($response->hasHeader("ETag")) {
            $this->cache->set($cacheKey, Psr7\str($response));
        }

        return $response;
    }


    /**
     * Get the installation access token.
     *
     * @return string
     */
    private function getToken(): string
    {
        if ($this->token === "" && $this->cache) {
            $this->token = $this->cache->get("github-token-" . $this->getName(), "");
            $this->tokenExpires = $this->cache->get("github-token-expires-" . $this->getName(), 0);
        }

        # If we already have a token, and it's not expired yet then use it
        if ($this->token !== "" && $this->tokenExpires > time()) {
            return $this->token;
        }

        $data = $this->api->post($this->data->access_tokens_url);

        $this->token = $data->token;
        $this->tokenExpires = strtotime($data->expires_at);

        if ($this->cache) {
            $this->cache->set("github-token-" . $this->getName(), $this->token);
            $this->cache->set("github-token-expires-" . $this->getName(), $this->tokenExpires);
        }

        return $data->token;
    }


    /**
     * Get all of the repositories for this installation.
     *
     * @return RepositoryInterface[]
     */
    public function getRepositories(): iterable
    {
        $repositories = $this->getAll("installation/repositories", [], function (\stdClass $data) {
            foreach ($data->repositories as $item) {
                yield Repository::fromApiResponse($item, $this);
            }
        });

        foreach ($repositories as $repository) {
            yield $repository;
        }
    }


    /**
     * Get a repository instance.
     *
     * @param string $name The name of the repository
     *
     * @return RepositoryInterface
     */
    public function getRepository(string $name): RepositoryInterface
    {
        return Repository::fromName($this->getName(), $name, $this);
    }
}
