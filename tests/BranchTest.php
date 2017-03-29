<?php

namespace duncan3dc\GitHubTests;

use duncan3dc\GitHub\ApiInterface;
use duncan3dc\GitHub\Branch;
use duncan3dc\GitHub\BranchInterface;
use duncan3dc\GitHub\Exceptions\LogicException;
use duncan3dc\ObjectIntruder\Intruder;
use Mockery;
use Mockery\MockInterface;
use PHPUnit\Framework\TestCase;
use function json_decode;
use function json_encode;
use Psr\Http\Message\ResponseInterface;

class BranchTest extends TestCase
{
    /** @var BranchInterface */
    private $branch;

    /** @var ApiInterface|MockInterface */
    private $api;


    public function setUp()
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
        $data = json_decode(json_encode($data));
        $branch = Branch::fromApiResponse($data, $this->api);

        $this->branch = new Intruder($branch);
    }


    public function tearDown()
    {
        Mockery::close();
    }


    public function testFromListResponse()
    {
        $branch = Branch::fromListResponse(new \stdClass, "", $this->api);

        $this->expectException(LogicException::class);
        $this->expectExceptionMessage("Unable to get the branch information, no URL has been provided");
        $branch->getDate();
    }


    public function urlProvider()
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
    public function testGetUrl($input, $expected)
    {
        $result = $this->branch->getUrl($input);
        $this->assertSame($expected, $result);
    }


    public function testGetName()
    {
        $result = $this->branch->getName();
        $this->assertSame("master", $result);
    }


    public function testGetCommit()
    {
        $result = $this->branch->getCommit();
        $this->assertSame("e2bd25ff3191f7ec9f353c137a114d599ac7959f", $result);
    }


    public function testGetHead()
    {
        $result = $this->branch->getHead()->message;
        $this->assertSame("Example commit message", $result);
    }


    public function testGetDate()
    {
        $date = $this->branch->getDate();
        $this->assertSame(1490788835, $date);
    }


    public function testGetProtection1()
    {
        $response = Mockery::mock(ResponseInterface::class);
        $response->shouldReceive("getBody")->with()->andReturn('{"settings":"protected"}');

        $this->api->shouldReceive("request")
            ->once()
            ->with("GET", "https://api.github.com/repos/duncan3dc/test/branches/master/protection", null)
            ->andReturn($response);

        $result = $this->branch->getProtection();
        $this->assertSame(["settings" => "protected"], (array) $result);
    }
    public function testGetProtection2()
    {
        $data = (object) [
            "protected" => 0,
        ];
        $branch = Branch::fromApiResponse($data, $this->api);

        $result = $branch->getProtection();
        $this->assertEmpty((array) $result);
    }
}
