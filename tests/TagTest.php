<?php

namespace duncan3dc\GitHubTests;

use duncan3dc\GitHub\Tag;
use duncan3dc\GitHub\TagInterface;
use Mockery;
use PHPUnit\Framework\TestCase;

class TagTest extends TestCase
{
    /** @var TagInterface */
    private $tag;


    public function setUp(): void
    {
        $data = (object) [
            "name" => "1.5.0",
            "commit" => (object) [
                "sha" => "2b584206c2d0e245286c41ee8c77a8e101c87a44",
            ],
        ];

        $this->tag = Tag::fromListResponse($data);
    }


    public function tearDown(): void
    {
        Mockery::close();
    }


    public function testGetName(): void
    {
        $result = $this->tag->getName();
        $this->assertSame("1.5.0", $result);
    }


    public function testGetCommit(): void
    {
        $result = $this->tag->getCommit();
        $this->assertSame("2b584206c2d0e245286c41ee8c77a8e101c87a44", $result);
    }
}
