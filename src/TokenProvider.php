<?php

namespace duncan3dc\GitHub;

use Psr\SimpleCache\CacheInterface;
use function strtotime;
use function time;

final class TokenProvider implements TokenProviderInterface
{
    /** @var OrganizationInterface */
    private $organization;

    /** @var CacheInterface|null */
    private $cache;

    /** @var string */
    private $token = "";

    /** @var int $tokenExpires */
    private $tokenExpires = 0;


    /**
     * @param OrganizationInterface $organization
     * @param CacheInterface $cache
     */
    public function __construct(OrganizationInterface $organization, CacheInterface $cache = null)
    {
        $this->organization = $organization;
        $this->cache = $cache;
    }


    /**
     * @return string
     */
    public function getToken(): string
    {
        $name = $this->organization->getName();

        if ($this->token === "" && $this->cache) {
            $this->token = $this->cache->get("github-token-{$name}", "");
            $this->tokenExpires = $this->cache->get("github-token-expires-{$name}", 0);
        }

        # If we already have a token, and it's not expired yet then use it
        if ($this->token !== "" && $this->tokenExpires > time()) {
            return $this->token;
        }

        $data = $this->organization->post($this->organization->getAccessTokensUrl());

        $this->token = $data->token;
        $this->tokenExpires = strtotime($data->expires_at);

        if ($this->cache) {
            $this->cache->set("github-token-{$name}", $this->token);
            $this->cache->set("github-token-expires-{$name}", $this->tokenExpires);
        }

        return $data->token;
    }
}
