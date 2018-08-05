<?php

namespace duncan3dc\GitHubTests;

use duncan3dc\GitHub\ApiInterface;
use duncan3dc\GitHub\Directory;
use duncan3dc\GitHub\DirectoryInterface;
use duncan3dc\GitHub\FileInterface;
use duncan3dc\GitHub\Tree;
use duncan3dc\GitHub\TreeInterface;
use duncan3dc\ObjectIntruder\Intruder;
use Mockery;
use Mockery\MockInterface;
use PHPUnit\Framework\TestCase;

class DirectoryTest extends TestCase
{
    /** @var DirectoryInterface */
    private $directory;

    /** @var ApiInterface|MockInterface */
    private $api;


    public function setUp()
    {
        $this->api = Mockery::mock(ApiInterface::class);

        $data = (object) [
            "type" => "tree",
            "path" => "stuff",
            "url" => "http://directory/tree",
        ];

        $directory = Directory::fromTreeItem($data, $this->api);

        $this->directory = new Intruder($directory);
    }


    public function tearDown()
    {
        Mockery::close();
    }


    public function testGetName()
    {
        $result = $this->directory->getName();
        $this->assertSame("stuff", $result);
    }


    public function testGetTree()
    {
        $this->api->shouldReceive("get")->with("http://directory/tree")->andReturn(new \stdClass());

        $tree = $this->directory->getTree();
        $this->assertInstanceOf(Tree::class, $tree);
    }


    public function testGetDirectories()
    {
        $this->directory->tree = Mockery::mock(TreeInterface::class);
        $this->directory->tree->shouldReceive("getDirectories")->with()->andReturn(["passthru"]);

        $result = $this->directory->getDirectories();
        $this->assertSame(["passthru"], $result);
    }


    public function testGetDirectory()
    {
        $directory = Mockery::mock(DirectoryInterface::class);

        $this->directory->tree = Mockery::mock(TreeInterface::class);
        $this->directory->tree->shouldReceive("getDirectory")->with("stuff")->andReturn($directory);

        $result = $this->directory->getDirectory("stuff");
        $this->assertSame($directory, $result);
    }


    public function testHasDirectory1()
    {
        $this->directory->tree = Mockery::mock(TreeInterface::class);
        $this->directory->tree->shouldReceive("hasDirectory")->with("thing")->andReturn(true);

        $result = $this->directory->hasDirectory("thing");
        $this->assertSame(true, $result);
    }
    public function testHasDirectory2()
    {
        $this->directory->tree = Mockery::mock(TreeInterface::class);
        $this->directory->tree->shouldReceive("hasDirectory")->with("thing")->andReturn(false);

        $result = $this->directory->hasDirectory("thing");
        $this->assertSame(false, $result);
    }


    public function testGetFiles()
    {
        $this->directory->tree = Mockery::mock(TreeInterface::class);
        $this->directory->tree->shouldReceive("getFiles")->with()->andReturn(["passthru"]);

        $result = $this->directory->getFiles();
        $this->assertSame(["passthru"], $result);
    }


    public function testGetFile()
    {
        $file = Mockery::mock(FileInterface::class);

        $this->directory->tree = Mockery::mock(TreeInterface::class);
        $this->directory->tree->shouldReceive("getFile")->with("thing")->andReturn($file);

        $result = $this->directory->getFile("thing");
        $this->assertSame($file, $result);
    }


    public function testHasFile1()
    {
        $this->directory->tree = Mockery::mock(TreeInterface::class);
        $this->directory->tree->shouldReceive("hasFile")->with("thing")->andReturn(true);

        $result = $this->directory->hasFile("thing");
        $this->assertSame(true, $result);
    }
    public function testHasFile2()
    {
        $this->directory->tree = Mockery::mock(TreeInterface::class);
        $this->directory->tree->shouldReceive("hasFile")->with("thing")->andReturn(false);

        $result = $this->directory->hasFile("thing");
        $this->assertSame(false, $result);
    }
}
