<?php

namespace duncan3dc\GitHub;

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
     * Add a comment to the pr.
     *
     * @return void
     */
    public function addComment(string $comment, string $path, int $position): void;
}
