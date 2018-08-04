<?php

namespace duncan3dc\GitHub;

interface TagInterface
{
    /**
     * Get the name of this tag.
     *
     * @return string
     */
    public function getName(): string;

    /**
     * Get the hash of the commit this tag references.
     *
     * @return string
     */
    public function getCommit(): string;
}
