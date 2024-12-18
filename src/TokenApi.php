<?php

namespace duncan3dc\GitHub;

use GuzzleHttp\Client;
use GuzzleHttp\ClientInterface;
use Psr\Http\Message\ResponseInterface;

use function count;
use function substr;
use function trim;

final class TokenApi implements ApiInterface
{
    use HttpTrait;

    /** @var string */
    private $token;

    /** @var ClientInterface */
    private $client;


    /**
     * @param string $token
     * @param ?ClientInterface $client
     */
    public function __construct(string $token, ?ClientInterface $client = null)
    {
        $this->token = $token;

        if ($client === null) {
            $client = new Client([
                "headers" => [
                    "Accept" => "application/vnd.github.machine-man-preview+json",
                ],
            ]);
        }
        $this->client = $client;
    }


    /**
     * Send a request and return the response.
     *
     * @param string $method The HTTP verb to use for the request
     * @param string $url The url to issue the request to (https://api.github.com is optional)
     * @param array<string, mixed> $data The parameters to send with the request
     *
     * @return ResponseInterface
     */
    public function request(string $method, string $url, array $data = []): ResponseInterface
    {
        $params = [
            "headers" => [
                "Authorization" => "token {$this->token}",
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
}
