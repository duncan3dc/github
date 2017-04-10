<?php

namespace duncan3dc\GitHubTests;

use duncan3dc\GitHub\ApiInterface;
use duncan3dc\GitHub\BranchInterface;
use duncan3dc\GitHub\PullRequest;
use duncan3dc\GitHub\Repository;
use GuzzleHttp\Psr7;
use Mockery;
use Mockery\MockInterface;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface;
use function iterator_to_array;
use function json_decode;
use function json_encode;

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


    public function testGetBranches()
    {
        $response = Psr7\parse_response(file_get_contents(__DIR__ . "/responses/branches.http"));

        $this->api->shouldReceive("request")
            ->once()
            ->with("GET", "repos/github/octocat/branches", [])
            ->andReturn($response);

        $branches = $this->repository->getBranches();
        $branches = iterator_to_array($branches);

        $this->assertContainsOnlyInstancesOf(BranchInterface::class, $branches);

        $branch = reset($branches);

        # Ensure this information is available without another API request
        $this->assertSame("master", $branch->getName());
        $this->assertSame("6dcb09b5b57875f334f61aebed695e2e4193db5e", $branch->getCommit());
        $this->assertInternalType("object", $branch->getProtection());

        # Ensure other information can be lazily loaded
        $data = [
            "commit" => [
                "sha" => "e2bd25ff3191f7ec9f353c137a114d599ac7959f",
                "commit" => [
                    "committer" => [
                        "date" => "2017-03-29T12:00:35+00:00",
                    ],
                ],
            ],
        ];
        $data = json_decode(json_encode($data));
        $this->api->shouldReceive("get")->once()->with("repos/github/octocat/branches/master")->andReturn($data);
        $this->assertSame("2017-03-29", date("Y-m-d", $branch->getDate()));
        $this->assertSame("e2bd25ff3191f7ec9f353c137a114d599ac7959f", $branch->getCommit());
    }


    public function testGetBranch()
    {
        $response = Mockery::mock(ResponseInterface::class);
        $response->shouldReceive("getBody")->with()->andReturn('{"name":"northlane"}');
        $this->api->shouldReceive("request")->once()->with("GET", "repos/github/octocat/branches/northlane", [])->andReturn($response);

        $branch = $this->repository->getBranch("northlane");

        $this->assertInstanceOf(BranchInterface::class, $branch);
        $this->assertSame("northlane", $branch->getName());
    }


    public function testGetPullRequest()
    {
        $result = $this->repository->getPullRequest("48");

        $this->assertInstanceOf(PullRequest::class, $result);
        $this->assertSame(48, $result->getNumber());
    }
}