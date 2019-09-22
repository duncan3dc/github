<?php

namespace duncan3dc\GitHub;

use duncan3dc\GitHub\Exceptions\NotFoundException;
use GuzzleHttp\Client;
use GuzzleHttp\ClientInterface;
use Lcobucci\JWT\Builder;
use Lcobucci\JWT\Signer\Key;
use Lcobucci\JWT\Signer\Rsa\Sha256;
use Psr\Http\Message\ResponseInterface;
use Psr\SimpleCache\CacheInterface;

use function count;
use function iterator_to_array;
use function substr;
use function time;
use function trim;

final class Api implements ApiInterface
{
    use HttpTrait;

    /**
     * @var int The App ID to access the GitHub API via.
     */
    private $app;

    /**
     * @var string The private key for the app.
     */
    private $key;

    /**
     * @var ClientInterface The HTTP client to communicate via.
     */
    private $client;

    /** @Var CacheInterface|null */
    private $cache;

    /**
     * @var OrganizationInterface[] The organizations this app is installed under.
     */
    private $organizations;


    /**
     * Create a new instance.
     *
     * @param int $app The App ID to access the GitHub API via.
     * @param string $key The app key (.pem file contents)
     * @param ClientInterface $client The HTTP client to communicate via
     * @param CacheInterface $cache
     */
    public function __construct(int $app, string $key, ClientInterface $client = null, CacheInterface $cache = null)
    {
        $this->app = $app;
        $this->key = $key;

        if ($client === null) {
            $client = new Client([
                "headers" => [
                    "Accept" => "application/vnd.github.machine-man-preview+json",
                ],
            ]);
        }
        $this->client = $client;

        $this->cache = $cache;
    }


    /**
     * Send a request and return the response.
     *
     * @param string $method The HTTP verb to use for the request
     * @param string $url The url to issue the request to (https://api.github.com is optional)
     * @param array $data The parameters to send with the request
     *
     * @return ResponseInterface
     */
    public function request(string $method, string $url, array $data = []): ResponseInterface
    {
        $token = (new Builder())
            ->setIssuer((string) $this->app)
            ->setIssuedAt(time())
            ->setExpiration(time() + 300)
            ->sign(new Sha256(), new Key($this->key))
            ->getToken();

        $params = [
            "headers" => [
                "Authorization" => "Bearer {$token}",
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

        return $this->client->request($method, $url, $params);
    }


    /**
     * Get all the organizations this app is installed under.
     *
     * @return OrganizationInterface[]
     */
    public function getOrganizations(): iterable
    {
        if ($this->organizations === null) {
            $this->organizations = [];

            $organizations = $this->getAll("/app/installations", [], function ($items) {
                foreach ($items as $data) {
                    $token = new TokenProvider()
                    yield Organization::fromApiResponse($data, $this->client, $this->token, $this->cache);
                }
            });

            $this->organizations = iterator_to_array($organizations);
        }

        return $this->organizations;
    }


    /**
     * Get an organization this app is installed under.
     *
     * @param string $name The name of the organisation
     *
     * @return OrganizationInterface
     */
    public function getOrganization(string $name): OrganizationInterface
    {
        foreach ($this->getOrganizations() as $organization) {
            if ($organization->getName() === $name) {
                return $organization;
            }
        }

        throw new NotFoundException("Unable to find this organization ({$name}), is this app installed there?");
    }
}
