<?php

namespace duncan3dc\GitHubTests;

use duncan3dc\GitHub\ApiInterface;
use duncan3dc\GitHub\Organization;
use duncan3dc\GitHub\RepositoryInterface;
use duncan3dc\ObjectIntruder\Intruder;
use GuzzleHttp\ClientInterface;
use GuzzleHttp\Psr7;
use Mockery;
use Mockery\MockInterface;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface;

class OrganizationTest extends TestCase
{
    /** @var Organization */
    private $organization;

    /** @var ApiInterface|MockInterface */
    private $api;

    /** @var ClientInterface|MockInterface */
    private $client;


    public function setUp()
    {
        $this->api = Mockery::mock(ApiInterface::class);
        $this->client = Mockery::mock(ClientInterface::class);

        $data = (object) [
            "id" => 789,
            "account" => (object) [
                "login" => "thephpleague",
            ],
            "access_tokens_url" => "https://api.github.com/GIVE_ME_TOKEN",
        ];
        $organization = new Organization($data, $this->api, $this->client);
        $this->organization = new Intruder($organization);
    }


    public function tearDown()
    {
        Mockery::close();
    }


    public function testGetName()
    {
        $result = $this->organization->getName();
        $this->assertSame("thephpleague", $result);
    }


    private function mockToken()
    {
        $this->organization->token = "XYZ789";
        $this->organization->tokenExpires = time() + 60;

        return [
            "headers"   =>  [
                "Authorization" =>  "token {$this->organization->token}",
            ],
        ];
    }


    public function testGetToken1()
    {
        $this->api->shouldReceive("post")->once()->with("https://api.github.com/GIVE_ME_TOKEN")->andReturn((object) [
            "token" => "ABC123",
            "expires_at" => "2017-03-29T12:00:35+00:00",
        ]);

        $this->organization->getToken();

        $this->assertSame("ABC123", $this->organization->token);
        $this->assertSame(1490788835, $this->organization->tokenExpires);
    }
    public function testGetToken2()
    {
        $this->api->shouldReceive("post")->once()->with("https://api.github.com/GIVE_ME_TOKEN")->andReturn((object) [
            "token" => "ABC123",
            "expires_at" => "2032-03-29T12:00:35+00:00",
        ]);

        $this->organization->getToken();
        $this->assertSame("ABC123", $this->organization->token);
        $this->assertSame(1964174435, $this->organization->tokenExpires);

        # Ensure that calling `getToken()` again before the token expires doesn't hit the mock client
        $this->organization->getToken();
    }
    public function testGetToken3()
    {
        $this->api->shouldReceive("post")->once()->with("https://api.github.com/GIVE_ME_TOKEN")->andReturn((object) [
            "token" => "ABC123",
            "expires_at" => "2017-03-29T12:00:35+00:00",
        ]);

        $this->organization->getToken();
        $this->assertSame("ABC123", $this->organization->token);
        $this->assertSame(1490788835, $this->organization->tokenExpires);

        # Ensure that calling `getToken()` again after the token expires requests a new token
        $this->api->shouldReceive("post")->once()->with("https://api.github.com/GIVE_ME_TOKEN")->andReturn((object) [
            "token" => "DEF456",
            "expires_at" => "2017-03-30T12:00:35+00:00",
        ]);

        $this->organization->getToken();
        $this->assertSame("DEF456", $this->organization->token);
        $this->assertSame(1490875235, $this->organization->tokenExpires);
    }


    public function urlProvider()
    {
        $data = [
            "test"              =>  "https://api.github.com/test",
            "/test"             =>  "https://api.github.com/test",
            "/test/"            =>  "https://api.github.com/test",
            "/test/one/two/"    =>  "https://api.github.com/test/one/two",
            "https://test.com/" =>  "https://test.com/",
        ];
        foreach ($data as $input => $expected) {
            yield [$input, $expected];
        }
    }
    /**
     * @dataProvider urlProvider
     */
    public function testRequest($input, $expected)
    {
        $params = $this->mockToken();

        $response = Mockery::mock(ResponseInterface::class);
        $this->client->shouldReceive("request")->once()->with("GET", $expected, $params)->andReturn($response);

        $result = $this->organization->request("GET", $input);
        $this->assertSame($response, $result);
    }


    public function testRequestWithEmptyGetData()
    {
        $params = $this->mockToken();
        $response = Mockery::mock(ResponseInterface::class);

        $this->client->shouldReceive("request")->once()->with("GET", "https://api.github.com/test", $params)->andReturn($response);

        $result = $this->organization->request("GET", "https://api.github.com/test");

        $this->assertSame($response, $result);
    }


    public function testRequestWithGetData()
    {
        $params = $this->mockToken();
        $response = Mockery::mock(ResponseInterface::class);

        $params["query"] = ["key" => "value"];

        $this->client->shouldReceive("request")->once()->with("GET", "https://api.github.com/test", $params)->andReturn($response);

        $result = $this->organization->request("GET", "https://api.github.com/test", ["key" => "value"]);

        $this->assertSame($response, $result);
    }


    public function testRequestWithEmptyPostData()
    {
        $params = $this->mockToken();
        $response = Mockery::mock(ResponseInterface::class);

        $this->client->shouldReceive("request")->once()->with("POST", "https://api.github.com/test", $params)->andReturn($response);

        $result = $this->organization->request("POST", "https://api.github.com/test");

        $this->assertSame($response, $result);
    }


    public function testRequestWithPostData()
    {
        $params = $this->mockToken();
        $response = Mockery::mock(ResponseInterface::class);

        $params["json"] = ["key" => "value"];

        $this->client->shouldReceive("request")->once()->with("POST", "https://api.github.com/test", $params)->andReturn($response);

        $result = $this->organization->request("POST", "https://api.github.com/test", ["key" => "value"]);

        $this->assertSame($response, $result);
    }


    public function testGetRepository()
    {
        $params = $this->mockToken();

        $response = Mockery::mock(ResponseInterface::class);
        $response->shouldReceive("getBody")->once()->andReturn('{"full_name": "thephpleague/octocat"}');

        $this->client->shouldReceive("request")
            ->once()
            ->with("GET", "https://api.github.com/repos/thephpleague/octocat", $params)
            ->andReturn($response);

        $repository = $this->organization->getRepository("octocat");

        $this->assertInstanceOf(RepositoryInterface::class, $repository);
        $this->assertSame("thephpleague/octocat", $repository->getFullName());
    }


    public function testGetRepositories()
    {
        $params = $this->mockToken();

        $response = Psr7\parse_response(file_get_contents(__DIR__ . "/responses/repositories.http"));

        $this->client->shouldReceive("request")
            ->once()
            ->with("GET", "https://api.github.com/installation/repositories", $params)
            ->andReturn($response);

        $repositories = $this->organization->getRepositories();
        $repositories = iterator_to_array($repositories);

        $this->assertContainsOnlyInstancesOf(RepositoryInterface::class, $repositories);

        $repository = reset($repositories);
        $this->assertSame("api", $repository->getName());
    }
}
