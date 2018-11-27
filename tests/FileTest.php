<?php

namespace duncan3dc\GitHubTests;

use duncan3dc\GitHub\ApiInterface;
use duncan3dc\GitHub\Exceptions\UnexpectedValueException;
use duncan3dc\GitHub\File;
use duncan3dc\GitHub\FileInterface;
use Mockery;
use Mockery\MockInterface;
use PHPUnit\Framework\TestCase;

class FileTest extends TestCase
{
    /** @var FileInterface */
    private $file;

    /** @var ApiInterface|MockInterface */
    private $api;


    public function setUp()
    {
        $this->api = Mockery::mock(ApiInterface::class);

        $data = (object) [
            "type" => "blob",
            "path" => "README.md",
            "url" => "http://test.com/",
            "mode" => "100644",
            "size" => 30,
            "sha" => "44b4fc6d56897b048c772eb4087f854f46256132",
        ];

        $this->file = File::fromTreeItem($data, $this->api);
    }


    public function tearDown()
    {
        Mockery::close();
    }


    public function testGetName()
    {
        $result = $this->file->getName();
        $this->assertSame("README.md", $result);
    }


    public function testGetSize()
    {
        $result = $this->file->getSize();
        $this->assertSame(30, $result);
    }


    public function testGetMode()
    {
        $result = $this->file->getMode();
        $this->assertSame("100644", $result);
    }


    public function testGetHash()
    {
        $result = $this->file->getHash();
        $this->assertSame("44b4fc6d56897b048c772eb4087f854f46256132", $result);
    }


    public function testGetContents1()
    {
        $this->api->shouldReceive("get")->once()->with("http://test.com/")->andReturn((object) ["content" => "aGVsbG8gd29ybGQK"]);

        $contents = $this->file->getContents();
        $this->assertSame("hello world\n", $contents);
    }
    public function testGetContents2()
    {
        $this->api->shouldReceive("get")->once()->with("http://test.com/")->andReturn((object) ["content" => "aGVsbG8gd29ybGQK"]);

        $contents = $this->file->getContents();
        $this->assertSame("hello world\n", $contents);

        # Ensure a second call doesn't hit the api, and still returns the contents
        $contents = $this->file->getContents();
        $this->assertSame("hello world\n", $contents);
    }
    public function testGetContents3()
    {
        $this->api->shouldReceive("get")->once()->with("http://test.com/")->andReturn((object) ["content" => "@"]);

        $this->expectException(UnexpectedValueException::class);
        $this->expectExceptionMessage("Unable to decode the file contents from the GitHub API response");
        $this->file->getContents();
    }
}
