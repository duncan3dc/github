<?php

namespace duncan3dc\GitHubTests;

use duncan3dc\GitHub\ApiInterface;
use duncan3dc\GitHub\Exceptions\JsonException;
use duncan3dc\GitHub\HttpTrait;
use Mockery;
use Mockery\MockInterface;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface;

class HttpTraitTest extends TestCase
{
    /** @var ApiInterface|MockInterface */
    private $api;

    /** @var ApiInterface HttpTrait */
    private $http;


    public function setUp(): void
    {
        $this->api = Mockery::mock(ApiInterface::class);

        $this->http = new class ($this->api) {
            use HttpTrait;

            /** @var ApiInterface */
            private $api;

            public function __construct(ApiInterface $api)
            {
                $this->api = $api;
            }


            /**
             * @param array<string, mixed> $data
             */
            public function request(string $method, string $url, array $data = []): ResponseInterface
            {
                return $this->api->request($method, $url, $data);
            }
        };
    }


    public function tearDown(): void
    {
        Mockery::close();
    }


    private function setupResponse(string $method, string $json): void
    {
        $response = Mockery::mock(ResponseInterface::class);
        $response->shouldReceive("getStatusCode")->once()->andReturn(200);
        $response->shouldReceive("getBody")->with()->andReturn($json);

        $this->api->shouldReceive("request")
            ->once()
            ->with($method, "http://github.innit", ["key" => "value"])
            ->andReturn($response);
    }


    public function testPost1(): void
    {
        $this->setupResponse("POST", '{"result":"yep"}');

        $result = $this->http->post("http://github.innit", ["key" => "value"]);

        $this->assertSame(["result" => "yep"], (array) $result);
    }
    public function testPost2(): void
    {
        $this->setupResponse("POST", '{"WHAT_IS_THIS}');

        $this->expectException(JsonException::class);
        $this->http->post("http://github.innit", ["key" => "value"]);
    }


    public function testPut1(): void
    {
        $this->setupResponse("PUT", '{"result":"yep"}');

        $result = $this->http->put("http://github.innit", ["key" => "value"]);

        $this->assertSame(["result" => "yep"], (array) $result);
    }
    public function testPut2(): void
    {
        $this->setupResponse("PUT", '{"WHAT_IS_THIS}');

        $this->expectException(JsonException::class);
        $this->http->put("http://github.innit", ["key" => "value"]);
    }


    public function testPatch1(): void
    {
        $this->setupResponse("PATCH", '{"result":"yep"}');

        $result = $this->http->patch("http://github.innit", ["key" => "value"]);

        $this->assertSame(["result" => "yep"], (array) $result);
    }
    public function testPatch2(): void
    {
        $this->setupResponse("PATCH", '{"WHAT_IS_THIS}');

        $this->expectException(JsonException::class);
        $this->http->patch("http://github.innit", ["key" => "value"]);
    }


    public function testDelete1(): void
    {
        $this->setupResponse("DELETE", '{"result":"yep"}');

        $result = $this->http->delete("http://github.innit", ["key" => "value"]);

        $this->assertSame(["result" => "yep"], (array) $result);
    }
    public function testDelete2(): void
    {
        $this->setupResponse("DELETE", '{"WHAT_IS_THIS}');

        $this->expectException(JsonException::class);
        $this->http->delete("http://github.innit", ["key" => "value"]);
    }


    public function testGet1(): void
    {
        $this->setupResponse("GET", '{"result":"yep"}');

        $result = $this->http->get("http://github.innit", ["key" => "value"]);

        $this->assertSame(["result" => "yep"], (array) $result);
    }
    public function testGet2(): void
    {
        $this->setupResponse("GET", '{"WHAT_IS_THIS}');

        $this->expectException(JsonException::class);
        $this->http->get("http://github.innit", ["key" => "value"]);
    }


    public function testEmptyResponse(): void
    {
        $response = Mockery::mock(ResponseInterface::class);
        $response->shouldReceive("getStatusCode")->once()->andReturn(204);

        $this->api->shouldReceive("request")
            ->once()
            ->with("GET", "http://github.innit", [])
            ->andReturn($response);

        $result = $this->http->get("http://github.innit");
        $this->assertInstanceOf(\stdClass::class, $result);
    }
}
