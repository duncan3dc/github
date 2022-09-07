<?php

namespace duncan3dc\GitHub;

use duncan3dc\GitHub\Exceptions\JsonException;
use Psr\Http\Message\ResponseInterface;

use function GuzzleHttp\Psr7\parse_header;
use function is_array;
use function json_decode;
use function json_last_error;
use function json_last_error_msg;

trait HttpTrait
{
    /**
     * Send a request and return the response.
     *
     * @param string $method The HTTP verb to use for the request
     * @param string $url The url to issue the request to (https://api.github.com is optional)
     * @param array<string, mixed> $data The parameters to send with the request
     *
     * @return ResponseInterface
     */
    abstract public function request(string $method, string $url, array $data = []): ResponseInterface;


    /**
     * Convert a response into a simple object.
     *
     * @param ResponseInterface $response The response to convert
     *
     * @return \stdClass
     */
    private function response(ResponseInterface $response): \stdClass
    {
        if ($response->getStatusCode() === 204) {
            return new \stdClass();
        }

        $data = json_decode($response->getBody());

        $error = json_last_error();
        if ($error !== \JSON_ERROR_NONE) {
            throw new JsonException("JSON Error: " . json_last_error_msg(), $error);
        }

        if (is_array($data)) {
            $data = (object) $data;
        }

        return $data;
    }


    /**
     * Send a POST request and return the response.
     *
     * @param string $url The url to issue the request to
     * @param array<string, mixed> $data The parameters to send with the request
     *
     * @return \stdClass
     */
    public function post(string $url, array $data = []): \stdClass
    {
        $response = $this->request("POST", $url, $data);
        return $this->response($response);
    }


    /**
     * Send a PUT request and return the response.
     *
     * @param string $url The url to issue the request to
     * @param array<string, mixed> $data The parameters to send with the request
     *
     * @return \stdClass
     */
    public function put(string $url, array $data = []): \stdClass
    {
        $response = $this->request("PUT", $url, $data);
        return $this->response($response);
    }


    /**
     * Send a PATCH request and return the response.
     *
     * @param string $url The url to issue the request to
     * @param array<string, mixed> $data The parameters to send with the request
     *
     * @return \stdClass
     */
    public function patch(string $url, array $data = []): \stdClass
    {
        $response = $this->request("PATCH", $url, $data);
        return $this->response($response);
    }


    /**
     * Send a DELETE request and return the response.
     *
     * @param string $url The url to issue the request to
     * @param array<string, mixed> $data The parameters to send with the request
     *
     * @return \stdClass
     */
    public function delete(string $url, array $data = []): \stdClass
    {
        $response = $this->request("DELETE", $url, $data);
        return $this->response($response);
    }


    /**
     * Send a GET request and return the response.
     *
     * @param string $url The url to issue the request to
     * @param array<string, mixed> $data The parameters to send with the request
     *
     * @return \stdClass
     */
    public function get(string $url, array $data = []): \stdClass
    {
        $response = $this->request("GET", $url, $data);
        return $this->response($response);
    }


    /**
     * Send a GET request and return the response.
     *
     * This method will loop through all the pages until one returns no results
     *
     * @param string $url The url to issue the request to
     * @param array<string, mixed> $data The parameters to send with the request
     * @param callable $callback An optional handler to yield items via
     *
     * @return \Traversable<\stdClass|mixed> Based on the return type of the callback
     */
    public function getAll(string $url, array $data = [], callable $callback = null): \Traversable
    {
        while (true) {
            $response = $this->request("GET", $url, $data);

            $items = $this->response($response);

            if ($callback) {
                $items = $callback($items);
            }
            foreach ($items as $item) {
                yield $item;
            }

            # Get the url for the next page of results
            $url = null;
            $links = parse_header($response->getHeader("Link"));
            foreach ($links as $link) {
                if ($link["rel"] === "next") {
                    /**
                     * GitHub has prepared a query string for our next page request which
                     * contains the page number and our original data. Clear this data
                     * array so that it doesn't overwrite the prepared query string.
                     */
                    $url = trim($link[0], "<>");
                    $data = [];
                    break;
                }
            }

            # If we didn't find a URL for the next page then assume we're on the last page
            if (!$url) {
                break;
            }
        }
    }
}
