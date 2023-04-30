<?php

namespace duncan3dc\GitHubTests;

use duncan3dc\GitHub\TokenApi;
use GuzzleHttp\ClientInterface;
use Mockery;
use Mockery\MockInterface;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface;

class TokenApiTest extends TestCase
{
    /** @var TokenApi */
    private $api;

    /** @var ClientInterface&MockInterface */
    private $client;


    public function setUp(): void
    {
        $this->client = Mockery::mock(ClientInterface::class);
        $this->api = new TokenApi("secret_token_thing", $this->client);
    }


    public function tearDown(): void
    {
        Mockery::close();
    }


    /**
     * Ensure we can send GET requests.
     * To a specific hostname we wish to use.
     */
    public function testRequest1(): void
    {
        $response = Mockery::mock(ResponseInterface::class);
        $response->shouldReceive("getStatusCode")->once()->andReturn(200);
        $response->shouldReceive("getBody")->once()->andReturn('{"name": "yep", "date": "today"}');
        $this->client->shouldReceive("request")->once()->with("GET", "https://test.com", Mockery::on(function (array $params) {
            $this->assertSame(["arg" => "ok"], $params["query"]);
            return true;
        }))->andReturn($response);

        $result = $this->api->get("https://test.com", ["arg" => "ok"]);

        $this->assertSame("yep", $result->name);
        $this->assertSame("today", $result->date);
    }


    /**
     * Ensure we can send POST requests.
     * Automatically prefixing the GitHub API host.
     */
    public function testRequest2(): void
    {
        $response = Mockery::mock(ResponseInterface::class);
        $response->shouldReceive("getStatusCode")->once()->andReturn(200);
        $response->shouldReceive("getBody")->once()->andReturn('{"name": "yep", "date": "today"}');
        $this->client->shouldReceive("request")->once()->with("POST", "https://api.github.com/endpoint", Mockery::on(function (array $params) {
            $this->assertSame(["arg" => "ok"], $params["json"]);
            return true;
        }))->andReturn($response);

        $result = $this->api->post("/endpoint", ["arg" => "ok"]);

        $this->assertSame("yep", $result->name);
        $this->assertSame("today", $result->date);
    }
}
