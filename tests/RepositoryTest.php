<?php

namespace duncan3dc\GitHubTests;

use duncan3dc\GitHub\ApiInterface;
use duncan3dc\GitHub\BranchInterface;
use duncan3dc\GitHub\PullRequest;
use duncan3dc\GitHub\PullRequestInterface;
use duncan3dc\GitHub\Repository;
use duncan3dc\GitHub\RepositoryInterface;
use duncan3dc\GitHub\TagInterface;
use GuzzleHttp\Psr7\Response;
use Mockery;
use Mockery\MockInterface;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface;

use function date;
use function is_array;
use function iterator_to_array;
use function json_decode;
use function json_encode;
use function reset;

class RepositoryTest extends TestCase
{
    /** @var RepositoryInterface */
    private $repository;

    /** @var ApiInterface|MockInterface */
    private $api;


    public function setUp(): void
    {
        $this->api = Mockery::mock(ApiInterface::class);

        $data = (object) [
            "name" => "octocat",
            "full_name" => "github/octocat",
            "description" => "Blah blah",
            "default_branch" => "south",
        ];
        $this->repository = Repository::fromApiResponse($data, $this->api);
    }


    public function tearDown(): void
    {
        Mockery::close();
    }


    public function testGetName(): void
    {
        $result = $this->repository->getName();
        $this->assertSame("octocat", $result);
    }


    public function testGetFullName(): void
    {
        $result = $this->repository->getFullName();
        $this->assertSame("github/octocat", $result);
    }


    public function testGetDescription(): void
    {
        $result = $this->repository->getDescription();
        $this->assertSame("Blah blah", $result);
    }


    public function testIsPrivate1(): void
    {
        $data = (object) [
            "private" => true,
        ];
        $repository = Repository::fromApiResponse($data, $this->api);
        $this->assertTrue($repository->isPrivate());
    }
    public function testIsPrivate2(): void
    {
        $data = (object) [
            "private" => false,
        ];
        $repository = Repository::fromApiResponse($data, $this->api);
        $this->assertFalse($repository->isPrivate());
    }
    public function testIsPrivate3(): void
    {
        $repository = Repository::fromApiResponse(new \stdClass(), $this->api);
        $this->assertFalse($repository->isPrivate());
    }


    public function testIsPublic1(): void
    {
        $repository = Repository::fromApiResponse(new \stdClass(), $this->api);
        $this->assertTrue($repository->isPublic());
    }
    public function testIsPublic2(): void
    {
        $data = (object) [
            "private" => false,
        ];
        $repository = Repository::fromApiResponse($data, $this->api);
        $this->assertTrue($repository->isPublic());
    }
    public function testIsPublic3(): void
    {
        $data = (object) [
            "private" => true,
        ];
        $repository = Repository::fromApiResponse($data, $this->api);
        $this->assertFalse($repository->isPublic());
    }


    public function testIsFork1(): void
    {
        $repository = Repository::fromApiResponse(new \stdClass(), $this->api);
        $this->assertFalse($repository->isFork());
    }
    public function testIsFork2(): void
    {
        $data = (object) [
            "fork" => true,
        ];
        $repository = Repository::fromApiResponse($data, $this->api);
        $this->assertTrue($repository->isFork());
    }
    public function testIsFork3(): void
    {
        $data = (object) [
            "fork" => false,
        ];
        $repository = Repository::fromApiResponse($data, $this->api);
        $this->assertFalse($repository->isFork());
    }


    public function testIsArchived1(): void
    {
        $repository = Repository::fromApiResponse(new \stdClass(), $this->api);
        $this->assertFalse($repository->isArchived());
    }
    public function testIsArchived2(): void
    {
        $data = (object) [
            "archived" => true,
        ];
        $repository = Repository::fromApiResponse($data, $this->api);
        $this->assertTrue($repository->isArchived());
    }
    public function testIsArchived3(): void
    {
        $data = (object) [
            "archived" => false,
        ];
        $repository = Repository::fromApiResponse($data, $this->api);
        $this->assertFalse($repository->isArchived());
    }


    public function urlProvider(): iterable
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
    public function testRequest(string $input, string $expected): void
    {
        $response = Mockery::mock(ResponseInterface::class);
        $response->shouldReceive("getStatusCode")->once()->andReturn(200);
        $response->shouldReceive("getBody")->andReturn('{"stuff": "data"}');
        $this->api->shouldReceive("request")->with("POST", $expected, ["stuff"])->andReturn($response);

        $result = $this->repository->post($input, ["stuff"]);
        $this->assertSame(["stuff" => "data"], (array) $result);
    }


    public function testGetBranches(): void
    {
        $response = Helper::getResponse("branches");

        $this->api->shouldReceive("request")
            ->once()
            ->with("GET", "repos/github/octocat/branches", [])
            ->andReturn($response);

        $branches = $this->repository->getBranches();
        $branches = is_array($branches) ? $branches : iterator_to_array($branches);

        $this->assertContainsOnlyInstancesOf(BranchInterface::class, $branches);

        $branch = reset($branches);

        # Ensure this information is available without another API request
        $this->assertSame("master", $branch->getName());
        $this->assertSame("6dcb09b5b57875f334f61aebed695e2e4193db5e", $branch->getCommit());
        $this->assertIsObject($branch->getProtection());

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
        $data = json_decode((string) json_encode($data));
        $this->api->shouldReceive("get")->once()->with("repos/github/octocat/branches/master")->andReturn($data);
        $this->assertSame("2017-03-29", date("Y-m-d", $branch->getDate()));
        $this->assertSame("e2bd25ff3191f7ec9f353c137a114d599ac7959f", $branch->getCommit());
    }


    public function testGetBranch(): void
    {
        $response = Mockery::mock(ResponseInterface::class);
        $response->shouldReceive("getStatusCode")->once()->andReturn(200);
        $response->shouldReceive("getBody")->with()->andReturn('{"name":"northlane"}');
        $this->api->shouldReceive("request")->once()->with("GET", "repos/github/octocat/branches/northlane", [])->andReturn($response);

        $branch = $this->repository->getBranch("northlane");

        $this->assertInstanceOf(BranchInterface::class, $branch);
        $this->assertSame("northlane", $branch->getName());
    }


    public function testGetDefaultBranch(): void
    {
        $response = Mockery::mock(ResponseInterface::class);
        $response->shouldReceive("getStatusCode")->once()->andReturn(200);
        $response->shouldReceive("getBody")->with()->andReturn('{"name":"south"}');
        $this->api->shouldReceive("request")->once()->with("GET", "repos/github/octocat/branches/south", [])->andReturn($response);

        $branch = $this->repository->getDefaultBranch();

        $this->assertInstanceOf(BranchInterface::class, $branch);
        $this->assertSame("south", $branch->getName());
    }


    public function testGetPullRequests1(): void
    {
        $response = Helper::getResponse("pull_requests");

        $this->api->shouldReceive("request")
            ->once()
            ->with("GET", "repos/github/octocat/pulls", [])
            ->andReturn($response);

        $pulls = $this->repository->getPullRequests();
        $pulls = is_array($pulls) ? $pulls : iterator_to_array($pulls);

        $this->assertContainsOnlyInstancesOf(PullRequestInterface::class, $pulls);

        /** @var PullRequestInterface $pull */
        $pull = reset($pulls);

        # Ensure this information is available without another API request
        $this->assertSame(1347, $pull->getNumber());
        $this->assertSame("6dcb09b5b57875f334f61aebed695e2e4193db5e", $pull->getCommit());

        # Ensure other information can be lazily loaded
        $response = Mockery::mock(ResponseInterface::class);
        $response->shouldReceive("getStatusCode")->andReturn(200);
        $response->shouldReceive("getBody")->andReturn('{"mergeable_state":"stale"}');
        $this->api->shouldReceive("request")->once()->with("GET", "repos/github/octocat/pulls/1347", [])->andReturn($response);
        $this->assertSame("stale", $pull->getMergeableState());
    }


    public function testGetPullRequest(): void
    {
        $result = $this->repository->getPullRequest(48);

        $this->assertInstanceOf(PullRequest::class, $result);
        $this->assertSame(48, $result->getNumber());
    }


    public function testGetTags(): void
    {
        $response = Helper::getResponse("tags");

        $this->api->shouldReceive("request")
            ->once()
            ->with("GET", "repos/github/octocat/tags", [])
            ->andReturn($response);

        $tags = $this->repository->getTags();
        $tags = is_array($tags) ? $tags : iterator_to_array($tags);

        $this->assertContainsOnlyInstancesOf(TagInterface::class, $tags);

        $tag = reset($tags);
        $this->assertSame("0.1.0", $tag->getName());
        $this->assertSame("252920787ec2cb36e5d14b3209873082d1995374", $tag->getCommit());
    }
}
