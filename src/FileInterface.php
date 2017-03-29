<?php

namespace duncan3dc\GitHub;

interface FileInterface
{
    /**
     * Get the name of this file.
     *
     * @return string
     */
    public function getName(): string;

    /**
     * Get the size of this file.
     *
     * @return int
     */
    public function getSize(): int;

    /**
     * Get the permissions and special mode flags of this file.
     *
     * @return string
     */
    public function getMode(): string;

    /**
     * Get the hash of this file.
     *
     * @return string
     */
    public function getHash(): string;

    /**
     * Download this file from GitHub.
     *
     * @return string
     */
    public function getContents(): string;
}
