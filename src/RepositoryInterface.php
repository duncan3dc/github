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

    /**
     * Check if this repository is private.
     *
     * @return bool
     */
    public function isPrivate(): bool;

    /**
     * Check if this repository is public.
     *
     * @return bool
     */
    public function isPublic(): bool;

    /**
     * Get all the branches in this repository.
     *
     * @return iterable|BranchInterface[]
     */
    public function getBranches(): iterable;

    /**
     * Get a branch from this repository.
     *
     * @param string $branch The name of the branch
     *
     * @return BranchInterface
     */
    public function getBranch(string $branch): BranchInterface;

    /**
     * Get the default branch for this repository.
     *
     * @return BranchInterface
     */
    public function getDefaultBranch(): BranchInterface;

    /**
     * Get a pull request from this repository.
     *
     * @param int $number The unique ID of the pr
     *
     * @return PullRequestInterface
     */
    public function getPullRequest(int $number): PullRequestInterface;
}
