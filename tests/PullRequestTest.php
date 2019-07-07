<?php

namespace duncan3dc\GitHubTests;

use duncan3dc\GitHub\ApiInterface;
use duncan3dc\GitHub\PullRequest;
use duncan3dc\GitHub\PullRequestInterface;
use duncan3dc\GitHub\RepositoryInterface;
use Mockery;
use Mockery\MockInterface;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface;
use function is_array;
use function iterator_to_array;

class PullRequestTest extends TestCase
{
    /** @var PullRequestInterface */
    private $pull;

    /** @var ApiInterface|MockInterface */
    private $api;

    /** @var RepositoryInterface|MockInterface */
    private $repository;


    public function setUp(): void
    {
        $this->api = Mockery::mock(ApiInterface::class);

        $this->repository = Mockery::mock(RepositoryInterface::class);
        $this->repository->shouldReceive("getFullName")->with()->andReturn("github/octocat");

        $this->pull = new PullRequest($this->repository, 27, $this->api);
    }


    public function tearDown(): void
    {
        Mockery::close();
    }


    public function urlProvider(): iterable
    {
        $data = [
            ""                  =>  "repos/github/octocat/pulls/27",
            "test"              =>  "repos/github/octocat/pulls/27/test",
            "/test"             =>  "/test",
            "test/"             =>  "repos/github/octocat/pulls/27/test",
            "test/one/two/"     =>  "repos/github/octocat/pulls/27/test/one/two",
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

        $this->api->shouldReceive("request")->with("GET", $expected, [])->andReturn($response);

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

        $this->api->shouldReceive("request")->with("GET", "repos/github/octocat/pulls/27/files", [])->andReturn($response);

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

        $this->api->shouldReceive("request")->with("GET", "repos/github/octocat/pulls/27/comments", [])->andReturn($response);

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

        $this->api->shouldReceive("request")->with("GET", "repos/github/octocat/pulls/27", [])->andReturn($response);

        $this->assertSame("def456", $this->pull->getCommit());
    }


    public function testAddComment(): void
    {
        $pull = $this->pull->withCommit("abc123");

        $response = Mockery::mock(ResponseInterface::class);
        $response->shouldReceive("getStatusCode")->once()->andReturn(200);
        $response->shouldReceive("getBody")->once()->with()->andReturn('{}');

        $this->api->shouldReceive("request")
            ->once()
            ->with("POST", "repos/github/octocat/pulls/27/comments", [
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
