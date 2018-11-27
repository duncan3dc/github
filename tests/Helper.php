<?php

namespace duncan3dc\GitHubTests;

use GuzzleHttp\Psr7;

class Helper
{
    public static function getResponse(string $name): Psr7\Response
    {
        $path = __DIR__ . "/responses/{$name}.http";

        $data = (string) file_get_contents($path);

        return Psr7\parse_response($data);
    }
}
