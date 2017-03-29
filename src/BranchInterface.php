<?php

namespace duncan3dc\GitHub;

interface BranchInterface
{
    /**
     * Get the name of this branch.
     *
     * @return string
     */
    public function getName(): string;

    /**
     * Get the hash of the commit at the HEAD of the branch.
     *
     * @return string
     */
    public function getCommit(): string;

    /**
     * Get the details for the HEAD of a branch.
     *
     * @return \stdClass
     */
    public function getHead(): \stdClass;

    /**
     * Get the date this branch was last changed.
     *
     * @return int A unix timestamp
     */
    public function getDate(): int;

    /**
     * Get the protection details.
     *
     * @return \stdClass
     */
    public function getProtection(): \stdClass;
}
