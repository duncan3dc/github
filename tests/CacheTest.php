<?php

namespace duncan3dc\GitHubTests;

use duncan3dc\GitHub\ApiInterface;
use duncan3dc\GitHub\Organization;
use duncan3dc\GitHub\OrganizationInterface;
use GuzzleHttp\ClientInterface;
use GuzzleHttp\Psr7;
use Mockery;
use Mockery\MockInterface;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface;
use Psr\SimpleCache\CacheInterface;

class CacheTest extends TestCase
{
    /** @var OrganizationInterface */
    private $organization;

    /** @var ClientInterface|MockInterface */
    private $client;

    /** @var CacheInterface|MockInterface */
    private $cache;


    /**
     * @inheritdoc
     */
    public function setUp(): void
    {
        $api = Mockery::mock(ApiInterface::class);
        $this->client = Mockery::mock(ClientInterface::class);

        $data = (object) [
            "id" => 40,
            "account" => (object) [
                "login" => "user1",
            ],
            "access_tokens_url" => "https://api.github.com/GIVE_ME_TOKEN",
        ];

        $this->cache = Mockery::mock(CacheInterface::class);
        $this->cache->shouldReceive("get")->once()->with("github-token-user1", "")->andReturn("TOKEN123");
        $this->cache->shouldReceive("get")->once()->with("github-token-expires-user1", "")->andReturn(time() + 600);

        $this->organization = Organization::fromApiResponse($data, $api, $this->client, $this->cache);
    }


    /**
     * @inheritdoc
     */
    public function tearDown(): void
    {
        Mockery::close();
    }


    /**
     * Ensure a cached response is returned.
     */
    public function testRequest1(): void
    {
        $cached = Psr7\str(Helper::getResponse("repositories"));
        $this->cache->shouldReceive("get")->once()->with("6641c0c9d99863ff412874d15a542d81c5a30b40")->andReturn($cached);

        $response = Mockery::mock(ResponseInterface::class);
        $response->shouldReceive("getStatusCode")->once()->with()->andReturn(304);

        $this->client->shouldReceive("request")->once()->with("GET", "https://api.github.com/repositories", [
            "headers" => [
                "Authorization" => "token TOKEN123",
                "If-None-Match" => '"48893eec72c2b2fa8fdf3549a4eeb97a"',
            ],
        ])->andReturn($response);

        $result = $this->organization->request("GET", "repositories");
        $this->assertNotSame($response, $result);
        $this->assertStringContainsString("repositories", $result->getBody()->getContents());
    }


    /**
     * If the server rejects our etag ensure we return and cache the new response.
     */
    public function testRequest2(): void
    {
        $response = Helper::getResponse("repositories");
        $this->cache->shouldReceive("get")->once()->with("76f27edcfd3da781134266b248da5ba5d40b366d")->andReturn(Psr7\str($response));

        $this->client->shouldReceive("request")->once()->with("GET", "https://api.github.com/repositories", [
            "headers" => [
                "Authorization" => "token TOKEN123",
                "If-None-Match" => '"48893eec72c2b2fa8fdf3549a4eeb97a"',
            ],
            "query" => [
                "max" => 2,
            ],
        ])->andReturn($response);

        $this->cache->shouldReceive("set")->once()->with("76f27edcfd3da781134266b248da5ba5d40b366d", Psr7\str($response));

        $result = $this->organization->request("GET", "repositories", ["max" => 2]);
        $this->assertSame($response, $result);
    }


    /**
     * If we don't have a cached version get the new response and cache it.
     */
    public function testRequest3(): void
    {
        $response = Helper::getResponse("repositories");
        $this->cache->shouldReceive("get")->once()->with("6641c0c9d99863ff412874d15a542d81c5a30b40")->andReturn(null);

        $this->client->shouldReceive("request")->once()->with("GET", "https://api.github.com/repositories", [
            "headers" => [
                "Authorization" => "token TOKEN123",
            ],
        ])->andReturn($response);

        $this->cache->shouldReceive("set")->once()->with("6641c0c9d99863ff412874d15a542d81c5a30b40", Psr7\str($response));

        $result = $this->organization->request("GET", "repositories");
        $this->assertSame($response, $result);
    }


    /**
     * Don't try and cache any non GET requests.
     */
    public function testRequest4(): void
    {
        $response = Helper::getResponse("repositories");

        $this->client->shouldReceive("request")->once()->with("POST", "https://api.github.com/repositories", [
            "headers" => [
                "Authorization" => "token TOKEN123",
            ],
        ])->andReturn($response);

        $result = $this->organization->request("POST", "repositories");
        $this->assertSame($response, $result);
    }
}
