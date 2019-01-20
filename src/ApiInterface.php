<?php

namespace duncan3dc\GitHub;

use Psr\Http\Message\ResponseInterface;

interface ApiInterface
{
    /**
     * Send a request and return the response.
     *
     * @param string $method The HTTP verb to use for the request
     * @param string $url The url to issue the request to (https://api.github.com is optional)
     * @param array $data The parameters to send with the request
     *
     * @return ResponseInterface
     */
    public function request(string $method, string $url, array $data = []): ResponseInterface;

    /**
     * Send a POST request and return the response.
     *
     * @param string $url The url to issue the request to (https://api.github.com is optional)
     * @param array $data The parameters to send with the request
     *
     * @return \stdClass
     */
    public function post(string $url, array $data = []): \stdClass;

    /**
     * Send a PUT request and return the response.
     *
     * @param string $url The url to issue the request to (https://api.github.com is optional)
     * @param array $data The parameters to send with the request
     *
     * @return \stdClass
     */
    public function put(string $url, array $data = []): \stdClass;

    /**
     * Send a PATCH request and return the response.
     *
     * @param string $url The url to issue the request to (https://api.github.com is optional)
     * @param array $data The parameters to send with the request
     *
     * @return \stdClass
     */
    public function patch(string $url, array $data = []): \stdClass;

    /**
     * Send a DELETE request and return the response.
     *
     * @param string $url The url to issue the request to (https://api.github.com is optional)
     * @param array $data The parameters to send with the request
     *
     * @return \stdClass
     */
    public function delete(string $url, array $data = []): \stdClass;

    /**
     * Send a GET request and return the response.
     *
     * @param string $url The url to issue the request to (https://api.github.com is optional)
     * @param array $data The parameters to send with the request
     *
     * @return \stdClass
     */
    public function get(string $url, array $data = []): \stdClass;

    /**
     * Send a GET request and return the response.
     *
     * This method will loop through all the pages until one returns no results
     *
     * @param string $url The url to issue the request to (https://api.github.com is optional)
     * @param array $data The parameters to send with the request
     * @param callable $callback An optional handler to yield items via
     *
     * @return \Traversable|\stdClass[]
     */
    public function getAll(string $url, array $data = [], callable $callback = null): \Traversable;
}
