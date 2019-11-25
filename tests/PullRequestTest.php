<?php

namespace duncan3dc\GitHubTests;

use duncan3dc\GitHub\BranchInterface;
use duncan3dc\GitHub\Issues\Label;
use duncan3dc\GitHub\PullRequest;
use duncan3dc\GitHub\PullRequestInterface;
use duncan3dc\GitHub\RepositoryInterface;
use Mockery;
use Mockery\MockInterface;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface;

use function is_array;
use function iterator_to_array;
use function json_decode;
use function reset;

class PullRequestTest extends TestCase
{
    /** @var PullRequestInterface */
    private $pull;

    /** @var RepositoryInterface|MockInterface */
    private $repository;


    public function setUp(): void
    {
        $this->repository = Mockery::mock(RepositoryInterface::class);
        $this->repository->shouldReceive("getFullName")->with()->andReturn("github/octocat");

        $this->pull = new PullRequest($this->repository, 27);
    }


    public function tearDown(): void
    {
        Mockery::close();
    }


    public function urlProvider(): iterable
    {
        $data = [
            ""                  =>  "pulls/27",
            "test"              =>  "pulls/27/test",
            "/test"             =>  "/test",
            "test/"             =>  "pulls/27/test",
            "test/one/two/"     =>  "pulls/27/test/one/two",
            "https://test.com/" =>  "https://test.com/",
        ];
        foreach ($data as $input => $expected) {
            yield [$input, $expected];
        }
    }
    /**
     * @dataProvider urlProvider
     */
    public function testGetUrl(string $input, string $expected): void
    {
        $response = Mockery::mock(ResponseInterface::class);

        $this->repository->shouldReceive("request")->with("GET", $expected, [])->andReturn($response);

        $result = $this->pull->request("GET", $input);
        $this->assertSame($response, $result);
    }


    public function testGetNumber(): void
    {
        $this->assertSame(27, $this->pull->getNumber());
    }


    public function testGetRepository(): void
    {
        $repository = $this->pull->getRepository();
        $this->assertSame($this->repository, $repository);
    }


    public function testGetFiles(): void
    {
        $response = Mockery::mock(ResponseInterface::class);
        $response->shouldReceive("getStatusCode")->once()->andReturn(200);
        $response->shouldReceive("getHeader")->once()->with("Link")->andReturn(null);
        $response->shouldReceive("getBody")->once()->with()->andReturn('["file"]');

        $this->repository->shouldReceive("request")->with("GET", "pulls/27/files", [])->andReturn($response);

        $files = $this->pull->getFiles();
        $files = is_array($files) ? $files : iterator_to_array($files);

        $this->assertSame(["file"], $files);
    }


    public function testGetComments(): void
    {
        $response = Mockery::mock(ResponseInterface::class);
        $response->shouldReceive("getStatusCode")->once()->andReturn(200);
        $response->shouldReceive("getHeader")->once()->with("Link")->andReturn(null);
        $response->shouldReceive("getBody")->once()->with()->andReturn('["comment"]');

        $this->repository->shouldReceive("request")->with("GET", "pulls/27/comments", [])->andReturn($response);

        $comments = $this->pull->getComments();
        $comments = is_array($comments) ? $comments : iterator_to_array($comments);

        $this->assertSame(["comment"], $comments);
    }


    public function testWithCommit(): void
    {
        $pull = $this->pull->withCommit("abc123");
        $this->assertNotSame($pull, $this->pull);
        $this->assertSame("abc123", $pull->getCommit());
    }


    public function testGetCommit(): void
    {
        $response = Mockery::mock(ResponseInterface::class);
        $response->shouldReceive("getStatusCode")->once()->andReturn(200);
        $response->shouldReceive("getBody")->once()->with()->andReturn('{"head":{"sha":"def456"}}');

        $this->repository->shouldReceive("request")->with("GET", "pulls/27", [])->andReturn($response);

        $this->assertSame("def456", $this->pull->getCommit());
    }


    public function testGetBranch1(): void
    {
        $response = Mockery::mock(ResponseInterface::class);
        $response->shouldReceive("getStatusCode")->once()->andReturn(200);
        $response->shouldReceive("getBody")->once()->with()->andReturn('{"head":{"ref":"my-special-feature"}}');

        $this->repository->shouldReceive("request")->with("GET", "pulls/27", [])->andReturn($response);

        $branch = Mockery::mock(BranchInterface::class);
        $this->repository->shouldReceive("getBranch")->once()->with("my-special-feature")->andReturn($branch);

        $this->assertSame($branch, $this->pull->getBranch());
    }


    public function testGetMergeableState1(): void
    {
        $response = Mockery::mock(ResponseInterface::class);
        $response->shouldReceive("getStatusCode")->once()->andReturn(200);
        $response->shouldReceive("getBody")->once()->with()->andReturn('{"mergeable_state":"blocked"}');

        $this->repository->shouldReceive("request")->with("GET", "pulls/27", [])->andReturn($response);

        $this->assertSame("blocked", $this->pull->getMergeableState());
    }


    public function testGetMergeableState2(): void
    {
        $response = Mockery::mock(ResponseInterface::class);
        $response->shouldReceive("getStatusCode")->once()->andReturn(200);
        $response->shouldReceive("getBody")->once()->with()->andReturn('{"mergeable_state":"dirty"}');

        $this->repository->shouldReceive("request")->with("GET", "pulls/27", [])->andReturn($response);

        $this->assertSame("dirty", $this->pull->getMergeableState());

        # Ensure a second call doesn't trigger another API request
        $this->assertSame("dirty", $this->pull->getMergeableState());
    }


    public function testGetLabels1(): void
    {
        $response = Helper::getResponse("pull_requests");
        $pulls = json_decode($response->getBody());
        $data = reset($pulls);
        $pull = PullRequest::fromListResponse($data, $this->repository);

        $labels = $pull->getLabels();
        $labels = is_array($labels) ? $labels : iterator_to_array($labels);
        $this->assertCount(1, $labels);

        /** @var Label $label */
        $label = reset($labels);

        $this->assertSame(208045946, $label->getId());
        $this->assertSame("bug", $label->getName());
        $this->assertSame("Something isn't working", $label->getDescription());
        $this->assertSame("f29513", $label->getColor());
    }


    public function testAddComment(): void
    {
        $pull = $this->pull->withCommit("abc123");

        $response = Mockery::mock(ResponseInterface::class);
        $response->shouldReceive("getStatusCode")->once()->andReturn(200);
        $response->shouldReceive("getBody")->once()->with()->andReturn('{}');

        $this->repository->shouldReceive("request")
            ->once()
            ->with("POST", "pulls/27/comments", [
                "body"      =>  "Some words",
                "commit_id" =>  "abc123",
                "path"      =>  "README.md",
                "position"  =>  4,
            ])
            ->andReturn($response);

        $pull->addComment("Some words", "README.md", 4);
        $this->assertTrue(true);
    }
}
