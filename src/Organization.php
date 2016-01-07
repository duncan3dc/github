<?php

namespace duncan3dc\GitHub;

use GuzzleHttp\ClientInterface;
use Psr\Http\Message\ResponseInterface;
use function count;
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

    /**
     * @var string The current installation access token.
     */
    private $token;

    /**
     * @var int $tokenExpires When the current installation access token expires.
     */
    private $tokenExpires;


    /**
     * Create a new instance.
     *
     * @var \stdClass $data This organization's data returned from the API
     * @param ApiInterface $api The GitHub app this installation is for
     * @param ClientInterface $client The HTTP client to communicate via
     */
    public function __construct(\stdClass $data, ApiInterface $api, ClientInterface $client)
    {
        $this->data = $data;
        $this->api = $api;
        $this->client = $client;
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

        return $this->client->request($method, $url, $params);
    }


    /**
     * Get the installation access token.
     *
     * @return string
     */
    private function getToken(): string
    {
        # If we already have a token, and it's not expired yet then use it
        if ($this->token && $this->tokenExpires > time()) {
            return $this->token;
        }

        $data = $this->api->post($this->data->access_tokens_url);

        $this->token = $data->token;
        $this->tokenExpires = strtotime($data->expires_at);

        return $data->token;
    }
}
