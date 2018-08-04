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


    public function setUp()
    {
        $data = (object) [
            "name" => "1.5.0",
            "commit" => (object) [
                "sha" => "2b584206c2d0e245286c41ee8c77a8e101c87a44",
            ],
        ];

        $this->tag = Tag::fromListResponse($data);
    }


    public function tearDown()
    {
        Mockery::close();
    }


    public function testGetName()
    {
        $result = $this->tag->getName();
        $this->assertSame("1.5.0", $result);
    }


    public function testGetCommit()
    {
        $result = $this->tag->getCommit();
        $this->assertSame("2b584206c2d0e245286c41ee8c77a8e101c87a44", $result);
    }
}
