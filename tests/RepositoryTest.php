<?php

namespace duncan3dc\GitHubTests;

use duncan3dc\GitHub\ApiInterface;
use duncan3dc\GitHub\Repository;
use Mockery;
use Mockery\MockInterface;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface;

class RepositoryTest extends TestCase
{
    /**
     * @var Repository The instance we are testing.
     */
    private $repository;

    /**
     * @var ApiInterface|MockInterface A mocked API instance to test with.
     */
    private $api;


    public function setUp()
    {
        $this->api = Mockery::mock(ApiInterface::class);

        $data = (object) [
            "name" => "octocat",
            "full_name" => "github/octocat",
        ];
        $this->repository = Repository::fromApiResponse($data, $this->api);
    }


    public function tearDown()
    {
        Mockery::close();
    }


    public function testGetName()
    {
        $result = $this->repository->getName();
        $this->assertSame("octocat", $result);
    }


    public function testGetFullName()
    {
        $result = $this->repository->getFullName();
        $this->assertSame("github/octocat", $result);
    }


    public function urlProvider()
    {
        $data = [
            "test" => "repos/github/octocat/test",
            "/test" => "/test",
            "test/" => "repos/github/octocat/test",
            "test/one/two/" => "repos/github/octocat/test/one/two",
            "https://test.com/" => "https://test.com/",
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
        $response = Mockery::mock(ResponseInterface::class);
        $response->shouldReceive("getBody")->andReturn('{"stuff": "data"}');
        $this->api->shouldReceive("request")->with("POST", $expected, ["stuff"])->andReturn($response);

        $result = $this->repository->post($input, ["stuff"]);
        $this->assertSame(["stuff" => "data"], (array) $result);
    }
}
