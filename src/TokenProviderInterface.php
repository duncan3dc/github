<?php

namespace duncan3dc\GitHub;

interface TokenProviderInterface
{


    /**
     * @return string
     */
    public function getToken(): string;
}
