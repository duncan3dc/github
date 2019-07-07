<?php

namespace duncan3dc\GitHubTests;

use duncan3dc\GitHub\ApiInterface;
use duncan3dc\GitHub\Branch;
use duncan3dc\GitHub\BranchInterface;
use duncan3dc\GitHub\DirectoryInterface;
use duncan3dc\GitHub\Exceptions\LogicException;
use duncan3dc\GitHub\FileInterface;
use duncan3dc\GitHub\TreeInterface;
use duncan3dc\ObjectIntruder\Intruder;
use Mockery;
use Mockery\MockInterface;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface;
use function json_decode;
use function json_encode;

class BranchTest extends TestCase
{
    /** @var BranchInterface|Intruder */
    private $branch;

    /** @var ApiInterface|MockInterface */
    private $api;


    public function setUp(): void
    {
        $this->api = Mockery::mock(ApiInterface::class);

        $data = [
            "name" => "master",
            "commit" => [
                "sha" => "e2bd25ff3191f7ec9f353c137a114d599ac7959f",
                "commit" => [
                    "message" => "Example commit message",
                    "committer" => [
                        "date" => "2017-03-29T12:00:35+00:00",
                    ],
                    "tree" => [
                        "url" => "http://branch/tree",
                    ],
                ],
            ],
            "_links" => [
                "self" => "https://api.github.com/repos/duncan3dc/test/branches/master",
                "html" => "https://github.com/duncan3dc/test/tree/master",
            ],
            "protected" => 1,
        ];
        $data = json_decode((string) json_encode($data));
        $branch = Branch::fromApiResponse($data, $this->api);

        $this->branch = new Intruder($branch);
    }


    public function tearDown(): void
    {
        Mockery::close();
    }


    public function testFromListResponse(): void
    {
        $branch = Branch::fromListResponse(new \stdClass(), "", $this->api);

        $this->expectException(LogicException::class);
        $this->expectExceptionMessage("Unable to get the branch information, no URL has been provided");
        $branch->getDate();
    }


    public function urlProvider(): iterable
    {
        $data = [
            "" => "https://api.github.com/repos/duncan3dc/test/branches/master",
            "test" => "https://api.github.com/repos/duncan3dc/test/branches/master/test",
            "/test" => "/test",
            "test/" => "https://api.github.com/repos/duncan3dc/test/branches/master/test",
            "test/one/two/" => "https://api.github.com/repos/duncan3dc/test/branches/master/test/one/two",
            "https://test.com/" => "https://test.com/",
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
        $result = $this->branch->getUrl($input);
        $this->assertSame($expected, $result);
    }


    public function testGetName(): void
    {
        $result = $this->branch->getName();
        $this->assertSame("master", $result);
    }


    public function testGetCommit(): void
    {
        $result = $this->branch->getCommit();
        $this->assertSame("e2bd25ff3191f7ec9f353c137a114d599ac7959f", $result);
    }


    public function testGetHead(): void
    {
        $result = $this->branch->getHead()->message;
        $this->assertSame("Example commit message", $result);
    }


    public function testGetDate(): void
    {
        $date = $this->branch->getDate();
        $this->assertSame(1490788835, $date);
    }


    public function testGetProtection1(): void
    {
        $response = Mockery::mock(ResponseInterface::class);
        $response->shouldReceive("getStatusCode")->once()->andReturn(200);
        $response->shouldReceive("getBody")->with()->andReturn('{"settings":"protected"}');

        $this->api->shouldReceive("request")
            ->once()
            ->with("GET", "https://api.github.com/repos/duncan3dc/test/branches/master/protection", null)
            ->andReturn($response);

        $result = $this->branch->getProtection();
        $this->assertSame(["settings" => "protected"], (array) $result);
    }
    public function testGetProtection2(): void
    {
        $data = (object) [
            "protected" => 0,
        ];
        $branch = Branch::fromApiResponse($data, $this->api);

        $result = $branch->getProtection();
        $this->assertEmpty((array) $result);
    }


    public function testGetTree(): void
    {
        $this->api->shouldReceive("get")->with("http://branch/tree")->andReturn(new \stdClass());

        $tree = $this->branch->getTree();
        $this->assertInstanceOf(TreeInterface::class, $tree);
    }


    public function testGetDirectories(): void
    {
        $passthru = Mockery::mock(DirectoryInterface::class);
        $this->branch->tree = Mockery::mock(TreeInterface::class);
        $this->branch->tree->shouldReceive("getDirectories")->with()->andReturn([$passthru]);

        $result = $this->branch->getDirectories();
        $this->assertSame([$passthru], $result);
    }


    public function testGetDirectory(): void
    {
        $directory = Mockery::mock(DirectoryInterface::class);

        $this->branch->tree = Mockery::mock(TreeInterface::class);
        $this->branch->tree->shouldReceive("getDirectory")->with("stuff")->andReturn($directory);

        $result = $this->branch->getDirectory("stuff");
        $this->assertSame($directory, $result);
    }


    public function testHasDirectory1(): void
    {
        $this->branch->tree = Mockery::mock(TreeInterface::class);
        $this->branch->tree->shouldReceive("hasDirectory")->with("stuff")->andReturn(true);

        $result = $this->branch->hasDirectory("stuff");
        $this->assertSame(true, $result);
    }
    public function testHasDirectory2(): void
    {
        $this->branch->tree = Mockery::mock(TreeInterface::class);
        $this->branch->tree->shouldReceive("hasDirectory")->with("stuff")->andReturn(false);

        $result = $this->branch->hasDirectory("stuff");
        $this->assertSame(false, $result);
    }


    public function testGetFiles(): void
    {
        $passthru = Mockery::mock(FileInterface::class);
        $this->branch->tree = Mockery::mock(TreeInterface::class);
        $this->branch->tree->shouldReceive("getFiles")->with()->andReturn([$passthru]);

        $result = $this->branch->getFiles();
        $this->assertSame([$passthru], $result);
    }


    public function testGetFile(): void
    {
        $file = Mockery::mock(FileInterface::class);

        $this->branch->tree = Mockery::mock(TreeInterface::class);
        $this->branch->tree->shouldReceive("getFile")->with("thing")->andReturn($file);

        $result = $this->branch->getFile("thing");
        $this->assertSame($file, $result);
    }


    public function testHasFile1(): void
    {
        $this->branch->tree = Mockery::mock(TreeInterface::class);
        $this->branch->tree->shouldReceive("hasFile")->with("thing")->andReturn(true);

        $result = $this->branch->hasFile("thing");
        $this->assertSame(true, $result);
    }
    public function testHasFile2(): void
    {
        $this->branch->tree = Mockery::mock(TreeInterface::class);
        $this->branch->tree->shouldReceive("hasFile")->with("thing")->andReturn(false);

        $result = $this->branch->hasFile("thing");
        $this->assertSame(false, $result);
    }
}
