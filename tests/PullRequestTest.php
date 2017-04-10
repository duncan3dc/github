<?php

namespace duncan3dc\GitHubTests;

use duncan3dc\GitHub\ApiInterface;
use duncan3dc\GitHub\PullRequest;
use duncan3dc\GitHub\PullRequestInterface;
use duncan3dc\GitHub\RepositoryInterface;
use function iterator_to_array;
use Mockery;
use Mockery\MockInterface;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface;

class PullRequestTest extends TestCase
{
    /** @var PullRequestInterface */
    private $pull;

    /** @var ApiInterface|MockInterface */
    private $api;

    /** @var RepositoryInterface|MockInterface */
    private $repository;


    public function setUp()
    {
        $this->api = Mockery::mock(ApiInterface::class);

        $this->repository = Mockery::mock(RepositoryInterface::class);
        $this->repository->shouldReceive("getFullName")->with()->andReturn("github/octocat");

        $this->pull = new PullRequest($this->repository, "27", $this->api);
    }


    public function tearDown()
    {
        Mockery::close();
    }


    public function urlProvider()
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
    public function testGetUrl($input, $expected)
    {
        $response = Mockery::mock(ResponseInterface::class);

        $this->api->shouldReceive("request")->with("GET", $expected, [])->andReturn($response);

        $result = $this->pull->request("GET", $input);
        $this->assertSame($response, $result);
    }


    public function testGetNumber()
    {
        $this->assertSame(27, $this->pull->getNumber());
    }


    public function testGetRepository()
    {
        $repository = $this->pull->getRepository();
        $this->assertSame($this->repository, $repository);
    }


    public function testGetFiles()
    {
        $response = Mockery::mock(ResponseInterface::class);
        $response->shouldReceive("getHeader")->once()->with("Link")->andReturn(null);
        $response->shouldReceive("getBody")->once()->with()->andReturn('["file"]');

        $this->api->shouldReceive("request")->with("GET", "repos/github/octocat/pulls/27/files", [])->andReturn($response);

        $files = $this->pull->getFiles();
        $files = iterator_to_array($files);

        $this->assertSame(["file"], $files);
    }


    public function testGetComments()
    {
        $response = Mockery::mock(ResponseInterface::class);
        $response->shouldReceive("getHeader")->once()->with("Link")->andReturn(null);
        $response->shouldReceive("getBody")->once()->with()->andReturn('["comment"]');

        $this->api->shouldReceive("request")->with("GET", "repos/github/octocat/pulls/27/comments", [])->andReturn($response);

        $comments = $this->pull->getComments();
        $comments = iterator_to_array($comments);

        $this->assertSame(["comment"], $comments);
    }


    public function testWithCommit()
    {
        $pull = $this->pull->withCommit("abc123");
        $this->assertNotSame($pull, $this->pull);
        $this->assertSame("abc123", $pull->getCommit());
    }


    public function testGetCommit()
    {
        $response = Mockery::mock(ResponseInterface::class);
        $response->shouldReceive("getBody")->once()->with()->andReturn('{"head":{"sha":"def456"}}');

        $this->api->shouldReceive("request")->with("GET", "repos/github/octocat/pulls/27", [])->andReturn($response);

        $this->assertSame("def456", $this->pull->getCommit());
    }


    public function testAddComment()
    {
        $pull = $this->pull->withCommit("abc123");

        $response = Mockery::mock(ResponseInterface::class);
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