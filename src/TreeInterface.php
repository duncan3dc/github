<?php

namespace duncan3dc\GitHub;

interface TreeInterface
{
    /**
     * Get the subdirectories in this directory.
     *
     * @return iterable|DirectoryInterface[]
     */
    public function getDirectories(): iterable;

    /**
     * Get a directory by its name.
     *
     * @param string $name The name of the directory
     *
     * @return DirectoryInterface
     */
    public function getDirectory(string $name): DirectoryInterface;

    /**
     * Check if a directory exists.
     *
     * @param string $name The name of the directory
     *
     * @return bool
     */
    public function hasDirectory(string $name): bool;

    /**
     * Get the files in this directory.
     *
     * @return iterable|FileInterface[]
     */
    public function getFiles(): iterable;

    /**
     * Get a file by its name.
     *
     * @param string $name The name of the file
     *
     * @return FileInterface
     */
    public function getFile(string $name): FileInterface;

    /**
     * Check if a file exists.
     *
     * @param string $name The name of the file
     *
     * @return bool
     */
    public function hasFile(string $name): bool;
}
