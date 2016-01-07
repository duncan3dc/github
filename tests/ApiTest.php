<?php

namespace duncan3dc\GitHubTests;

use duncan3dc\GitHub\Api;
use duncan3dc\GitHub\Exceptions\NotFoundException;
use duncan3dc\GitHub\OrganizationInterface;
use duncan3dc\ObjectIntruder\Intruder;
use GuzzleHttp\ClientInterface;
use GuzzleHttp\Exception\ClientException;
use GuzzleHttp\Psr7;
use Mockery;
use Mockery\MockInterface;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface;
use function file_get_contents;
use function iterator_to_array;
use function openssl_pkey_export;
use function openssl_pkey_new;

class ApiTest extends TestCase
{
    /**
     * @var Api The instance we are testing
     */
    private $api;

    /**
     * @var ClientInterface|MockInterface
     */
    private $client;

    public function setUp()
    {
        # Generate a valid private key for testing
        $ssl = openssl_pkey_new();
        openssl_pkey_export($ssl, $key);

        $this->client = Mockery::mock(ClientInterface::class);
        $api = new Api(345, $key, $this->client);
        $this->api = new Intruder($api);
    }


    public function tearDown()
    {
        Mockery::close();
    }


    public function testConstructor()
    {
        $ssl = openssl_pkey_new();
        openssl_pkey_export($ssl, $key);
        $api = new Api(999, $key);

        $this->expectException(ClientException::class);
        $this->expectExceptionMessage("Client error: `GET https://api.github.com` resulted in a `401 Unauthorized` response");
        $api->request("GET", "https://api.github.com");
    }


    public function testPost()
    {
        $response = Mockery::mock(ResponseInterface::class);
        $response->shouldReceive("getBody")->once()->andReturn('{"name": "yep", "date": "today"}');
        $this->client->shouldReceive("request")->once()->with("POST", "https://test.com", Mockery::on(function (array $params) {
            $this->assertSame(["ok"], $params["json"]);
            return true;
        }))->andReturn($response);

        $result = $this->api->post("https://test.com", ["ok"]);

        $this->assertSame("yep", $result->name);
        $this->assertSame("today", $result->date);
    }


    public function testGet()
    {
        $response = Mockery::mock(ResponseInterface::class);
        $response->shouldReceive("getBody")->once()->andReturn('{"name": "yep", "date": "today"}');
        $this->client->shouldReceive("request")->once()->with("GET", "https://test.com", Mockery::on(function (array $params) {
            $this->assertSame(["ok"], $params["query"]);
            return true;
        }))->andReturn($response);

        $result = $this->api->get("https://test.com", ["ok"]);

        $this->assertSame("yep", $result->name);
        $this->assertSame("today", $result->date);
    }


    public function testGetAll()
    {
        $response = Psr7\parse_response(file_get_contents(__DIR__ . "/responses/get_all_page1.http"));
        $this->client->shouldReceive("request")->once()->with("GET", "https://api.github.com/pages", Mockery::on(function (array $params) {
            $this->assertSame(["key" => "value"], $params["query"]);
            return true;
        }))->andReturn($response);

        $response = Psr7\parse_response(file_get_contents(__DIR__ . "/responses/get_all_page2.http"));
        $this->client->shouldReceive("request")->once()->with("GET", "https://test.com/?key=value&page=2", Mockery::on(function (array $params) {
            $this->assertNotContains("query", $params);
            return true;
        }))->andReturn($response);

        $result = $this->api->getAll("pages", ["key" => "value"]);
        $data = [];
        foreach ($result as $row) {
            $data[] = (array) $row;
        }

        $this->assertSame([
            ["id" => 12083245, "name" => "scheduler"],
            ["id" => 12896602, "name" => "orderpad"],
            ["id" => 54459620, "name" => "procedures"],
            ["id" => 54634039, "name" => "sql-mock"],
        ], $data);
    }
    public function testGetAll2()
    {
        $response = Psr7\parse_response(file_get_contents(__DIR__ . "/responses/get_all.http"));
        $this->client->shouldReceive("request")->once()->with("GET", "https://api.github.com/pages", Mockery::on(function (array $params) {
            $this->assertSame(["key" => "value"], $params["query"]);
            return true;
        }))->andReturn($response);

        $result = $this->api->getAll("pages", ["key" => "value"], function ($items) {
            foreach ($items as $item) {
                yield $item->name;
            }
        });

        $this->assertSame(["scheduler", "orderpad"], iterator_to_array($result));
    }


    public function testPut()
    {
        $response = Mockery::mock(ResponseInterface::class);
        $response->shouldReceive("getBody")->once()->andReturn('{"name": "new", "date": "tomorrow"}');
        $this->client->shouldReceive("request")->once()->with("PUT", "https://test.com", Mockery::on(function (array $params) {
            $this->assertSame(["create" => "please"], $params["json"]);
            return true;
        }))->andReturn($response);

        $result = $this->api->put("https://test.com", ["create" => "please"]);

        $this->assertSame("new", $result->name);
        $this->assertSame("tomorrow", $result->date);
    }


    public function testDelete()
    {
        $response = Mockery::mock(ResponseInterface::class);
        $response->shouldReceive("getBody")->once()->andReturn('{"name": "yep", "date": "today"}');
        $this->client->shouldReceive("request")->once()->with("DELETE", "https://test.com", Mockery::on(function (array $params) {
            $this->assertSame(["ok"], $params["json"]);
            return true;
        }))->andReturn($response);

        $result = $this->api->delete("https://test.com", ["ok"]);

        $this->assertSame("yep", $result->name);
        $this->assertSame("today", $result->date);
    }


    public function testGetOrganizations()
    {
        $response = Psr7\parse_response(file_get_contents(__DIR__ . "/responses/organizations.http"));

        $this->client->shouldReceive("request")
            ->once()
            ->with("GET", "https://api.github.com/app/installations", \Mockery::any())
            ->andReturn($response);

        $organizations = $this->api->getOrganizations();

        $this->assertContainsOnlyInstancesOf(OrganizationInterface::class, $organizations);

        $organization = reset($organizations);
        $this->assertSame("thephpleague", $organization->getName());
    }


    public function testGetOrganization1()
    {
        $response = Psr7\parse_response(file_get_contents(__DIR__ . "/responses/organizations.http"));

        $this->client->shouldReceive("request")
            ->once()
            ->with("GET", "https://api.github.com/app/installations", \Mockery::any())
            ->andReturn($response);

        $organization = $this->api->getOrganization("duncan3dc");

        $this->assertInstanceOf(OrganizationInterface::class, $organization);
        $this->assertSame("duncan3dc", $organization->getName());
    }
    public function testGetOrganization2()
    {
        $response = Psr7\parse_response(file_get_contents(__DIR__ . "/responses/organizations.http"));

        $this->client->shouldReceive("request")
            ->once()
            ->with("GET", "https://api.github.com/app/installations", \Mockery::any())
            ->andReturn($response);

        $this->expectException(NotFoundException::class);
        $this->expectExceptionMessage("Unable to find this organization (who?), is this app installed there?");
        $this->api->getOrganization("who?");
    }
}
