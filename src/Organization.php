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
     * @var ClientInterface The HTTP client to communicate via.
     */
    private $client;

    /** @var CacheInterface|null */
    private $cache;

    /** @var TokenProviderInterface */
    private $token;


    /**
     * @param string $name The name of the organization or user
     * @param ClientInterface $client The HTTP client to communicate via
     * @param CacheInterface $cache
     * @param TokenProviderInterface $token
     *
     * @return OrganizationInterface
     */
    public static function fromName(string $name, ClientInterface $client, CacheInterface $cache = null, TokenProviderInterface $token = null): OrganizationInterface
    {
        $org = new self(new \stdClass(), $client, $cache, $token);

        $data = $org->get("/orgs/{$name}");

        return new self($data, $client, $cache, $token);
    }


    /**
     * @var \stdClass $data This organization's data returned from the API
     * @param ClientInterface $client The HTTP client to communicate via
     * @param CacheInterface $cache
     * @param TokenProviderInterface $token
     *
     * @return OrganizationInterface
     */
    public static function fromApiResponse(\stdClass $data, ClientInterface $client, CacheInterface $cache = null, TokenProviderInterface $token = null): OrganizationInterface
    {
        return new self($data, $client, $cache, $token);
    }


    /**
     * @var \stdClass $data This organization's data returned from the API
     * @param ClientInterface $client The HTTP client to communicate via
     * @param CacheInterface $cache
     * @param TokenProviderInterface $token
     */
    private function __construct(\stdClass $data, ClientInterface $client, CacheInterface $cache = null, TokenProviderInterface $token = null)
    {
        $this->data = $data;
        $this->client = $client;
        $this->cache = $cache;

        if ($token === null) {
            $token = new TokenProvider($this, $this->cache);
        }
        $this->token = $token;
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
                "Authorization" => "token " . $this->token->getToken(),
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


    /**
     * @inheritDoc
     */
    public function getAccessTokensUrl(): string
    {
        return $this->data->access_tokens_url;
    }
}
