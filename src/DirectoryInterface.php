<?php

namespace duncan3dc\GitHub;

interface DirectoryInterface extends TreeInterface
{
    /**
     * Get the name of this directory.
     *
     * @return string
     */
    public function getName(): string;
}
