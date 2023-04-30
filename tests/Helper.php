<?php

namespace duncan3dc\GitHubTests;

use GuzzleHttp\Psr7\Message;
use Psr\Http\Message\ResponseInterface;

class Helper
{
    public static function getResponse(string $name): ResponseInterface
    {
        $path = __DIR__ . "/responses/{$name}.http";

        $data = (string) file_get_contents($path);

        return Message::parseResponse($data);
    }
}
