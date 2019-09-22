<?php

namespace duncan3dc\GitHub;

interface OrganizationInterface extends ApiInterface
{
    /**
     * Get the name of this organization.
     *
     * @return string
     */
    public function getName(): string;

    /**
     * Get all of the repositories for this installation.
     *
     * @return RepositoryInterface[]
     */
    public function getRepositories(): iterable;

    /**
     * Get a repository instance.
     *
     * @param string $name The name of the repository
     *
     * @return RepositoryInterface
     */
    public function getRepository(string $name): RepositoryInterface;

    /**
     * Get the access token endpoint for this organization.
     *
     * @return string
     */
    public function getAccessTokensUrl(): string;
}
