<?php

namespace duncan3dc\GitHub;

interface RepositoryInterface extends ApiInterface
{
    /**
     * Get the name of this repository.
     *
     * @return string
     */
    public function getName(): string;

    /**
     * Get the full name of this repository (including the owner).
     *
     * @return string
     */
    public function getFullName(): string;
}
