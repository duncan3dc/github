<?php

namespace duncan3dc\GitHub;

use duncan3dc\GitHub\Issues\Label;

interface PullRequestInterface extends ApiInterface
{
    /**
     * Get the repository this pr is from.
     *
     * @return RepositoryInterface
     */
    public function getRepository(): RepositoryInterface;

    /**
     * Get the number of this pr.
     *
     * @return int
     */
    public function getNumber(): int;

    /**
     * Get all the files that are touched by this pr.
     *
     * @return iterable&\stdClass[]
     */
    public function getFiles(): iterable;

    /**
     * Get all the comments on this pr.
     *
     * @return iterable&\stdClass[]
     */
    public function getComments(): iterable;

    /**
     * Set the version of this pr we are working with.
     *
     * @param string $commit The full sha of the commit in the pr
     *
     * @return PullRequestInterface
     */
    public function withCommit(string $commit): PullRequestInterface;

    /**
     * Get the version of this pr we are working with.
     *
     * @return string
     */
    public function getCommit(): string;

    /**
     * Get the branch this PR is for.
     *
     * @return BranchInterface
     */
    public function getBranch(): BranchInterface;

    /**
     * Get the base branch this PR is targeting.
     *
     * @return BranchInterface
     */
    public function getBaseBranch(): BranchInterface;

    /**
     * Get the mergeable state of this PR.
     *
     * The specific values returned by this function are undefined by GitHub
     *
     * @return string
     */
    public function getMergeableState(): string;

    /**
     * Get the labels attached to this pr.
     *
     * @return Label[]
     */
    public function getLabels(): iterable;

    /**
     * Add a comment to the pr.
     *
     * @return void
     */
    public function addComment(string $comment, string $path, int $position): void;
}
