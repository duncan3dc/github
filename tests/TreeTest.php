<?php

namespace duncan3dc\GitHubTests;

use duncan3dc\GitHub\ApiInterface;
use duncan3dc\GitHub\Exceptions\Exception;
use duncan3dc\GitHub\Exceptions\NotFoundException;
use duncan3dc\GitHub\Exceptions\TruncatedResponseException;
use duncan3dc\GitHub\Tree;
use duncan3dc\GitHub\TreeInterface;
use Mockery;
use Mockery\MockInterface;
use PHPUnit\Framework\TestCase;
use function is_array;
use function iterator_to_array;
use function json_decode;
use function json_encode;

class TreeTest extends TestCase
{
    /** @var TreeInterface */
    private $tree;

    /** @var ApiInterface|MockInterface */
    private $api;

    /** @var \stdClass */
    private $data;


    public function setUp(): void
    {
        $this->api = Mockery::mock(ApiInterface::class);

        $data = [
            "truncated" =>  false,
            "tree"    =>  [
                [
                    "type"  =>  "blob",
                    "path"  =>  "file1.txt",
                ],
                [
                    "type"  =>  "tree",
                    "path"  =>  "stuff",
                ],
                [
                    "type"  =>  "blob",
                    "path"  =>  "file3.txt",
                ],
                [
                    "type"  =>  "tree",
                    "path"  =>  "more-stuff",
                ],
            ],
        ];
        $this->data = json_decode((string) json_encode($data));

        $this->tree = Tree::fromApiResponse($this->data, $this->api);
    }


    public function tearDown(): void
    {
        Mockery::close();
    }


    public function testGetDirectories1(): void
    {
        $result = $this->tree->getDirectories();

        $directories = [];
        foreach ($result as $directory) {
            $directories[] = $directory->getName();
        }

        $this->assertSame(["stuff", "more-stuff"], $directories);
    }
    public function testGetDirectoriess2(): void
    {
        $this->data->truncated = true;

        $this->expectException(Exception::class);
        $this->expectExceptionMessage("Unable to retrieve all directories, too many files in the repository");
        $result = $this->tree->getDirectories();
        is_array($result) ? $result : iterator_to_array($result);
    }


    public function testGetDirectory1(): void
    {
        $directory = $this->tree->getDirectory("stuff");
        $this->assertSame("stuff", $directory->getName());
    }
    public function testGetDirectory2(): void
    {
        $this->expectException(NotFoundException::class);
        $this->expectExceptionMessage("The requested directory does not exist: no-stuff");
        $this->tree->getDirectory("no-stuff");
    }
    public function testGetDirectory3(): void
    {
        $this->expectException(Exception::class);
        $this->expectExceptionMessage("Unable to find the requested directory, too many files in the repository");
        $this->data->truncated = true;
        $this->tree->getDirectory("no-stuff");
    }


    public function testHasDirectory1(): void
    {
        $result = $this->tree->hasDirectory("stuff");
        $this->assertTrue($result);
    }
    public function testHasDirectory2(): void
    {
        $result = $this->tree->hasDirectory("no-stuff");
        $this->assertFalse($result);
    }
    public function testHasDirectory3(): void
    {
        $this->expectException(TruncatedResponseException::class);
        $this->expectExceptionMessage("Unable to find the requested directory, too many files in the repository");
        $this->data->truncated = true;
        $this->tree->hasDirectory("no-stuff");
    }


    public function testGetFiles1(): void
    {
        $result = $this->tree->getFiles();

        $files = [];
        foreach ($result as $file) {
            $files[] = $file->getName();
        }

        $this->assertSame(["file1.txt", "file3.txt"], $files);
    }
    public function testGetFiles2(): void
    {
        $this->data->truncated = true;

        $this->expectException(Exception::class);
        $this->expectExceptionMessage("Unable to retrieve all files, there are too many in the repository");
        $result = $this->tree->getFiles();
        is_array($result) ? $result : iterator_to_array($result);
    }


    public function testGetFile1(): void
    {
        $file = $this->tree->getFile("file1.txt");
        $this->assertSame("file1.txt", $file->getName());
    }
    public function testGetFile2(): void
    {
        $this->expectException(NotFoundException::class);
        $this->expectExceptionMessage("The requested file does not exist: file77.txt");
        $this->tree->getFile("file77.txt");
    }
    public function testGetFile3()
    {
        $this->expectException(Exception::class);
        $this->expectExceptionMessage("Unable to find the requested file, there are too many in the repository");
        $this->data->truncated = true;
        $this->tree->getFile("file77.txt");
    }


    public function testHasFile1(): void
    {
        $result = $this->tree->hasFile("file1.txt");
        $this->assertTrue($result);
    }
    public function testHasFile2(): void
    {
        $result = $this->tree->hasFile("file77.txt");
        $this->assertFalse($result);
    }
    public function testHasFile3(): void
    {
        $this->expectException(TruncatedResponseException::class);
        $this->expectExceptionMessage("Unable to find the requested file, there are too many in the repository");
        $this->data->truncated = true;
        $this->tree->hasFile("file77.txt");
    }
}
