<?php

namespace duncan3dc\GitHub;

use duncan3dc\GitHub\Exceptions\NotFoundException;
use duncan3dc\GitHub\Exceptions\TruncatedResponseException;

final class Tree implements TreeInterface
{
    /**
     * @var ApiInterface The Api instance to communicate with GitHub.
     */
    private $api;

    /**
     * @var \stdClass The tree's data.
     */
    private $data;


    /**
     * Create a new instance.
     *
     * @param \stdClass $data The tree's data from the GitHub Api
     * @param ApiInterface $api The Api instance to communicate with GitHub
     *
     * @return TreeInterface
     */
    public static function fromApiResponse(\stdClass $data, ApiInterface $api): TreeInterface
    {
        return new self($data, $api);
    }


    /**
     * Create a new instance.
     *
     * @param \stdClass $data The tree's data from the GitHub Api
     * @param ApiInterface $api The Api instance to communicate with GitHub
     */
    private function __construct(\stdClass $data, ApiInterface $api)
    {
        $this->api = $api;
        $this->data = $data;
    }


    public function getDirectories(): iterable
    {
        if ($this->data->truncated) {
            throw new TruncatedResponseException("Unable to retrieve all directories, too many files in the repository");
        }

        foreach ($this->data->tree as $item) {
            if ($item->type !== "tree") {
                continue;
            }

            yield Directory::fromTreeItem($item, $this->api);
        }
    }


    public function getDirectory(string $name): DirectoryInterface
    {
        foreach ($this->data->tree as $item) {
            if ($item->type !== "tree") {
                continue;
            }

            if ($item->path !== $name) {
                continue;
            }

            return Directory::fromTreeItem($item, $this->api);
        }

        if ($this->data->truncated) {
            throw new TruncatedResponseException("Unable to find the requested directory, too many files in the repository");
        }

        throw new NotFoundException("The requested directory does not exist: {$name}");
    }


    public function hasDirectory(string $name): bool
    {
        try {
            $this->getDirectory($name);
        } catch (NotFoundException $e) {
            return false;
        }

        return true;
    }


    public function getFiles(): iterable
    {
        if ($this->data->truncated) {
            throw new TruncatedResponseException("Unable to retrieve all files, there are too many in the repository");
        }

        foreach ($this->data->tree as $item) {
            if ($item->type !== "blob") {
                continue;
            }

            yield File::fromTreeItem($item, $this->api);
        }
    }


    public function getFile(string $name): FileInterface
    {
        foreach ($this->data->tree as $item) {
            if ($item->type !== "blob") {
                continue;
            }

            if ($item->path !== $name) {
                continue;
            }

            return File::fromTreeItem($item, $this->api);
        }

        if ($this->data->truncated) {
            throw new TruncatedResponseException("Unable to find the requested file, there are too many in the repository");
        }

        throw new NotFoundException("The requested file does not exist: {$name}");
    }


    public function hasFile(string $name): bool
    {
        try {
            $this->getFile($name);
        } catch (NotFoundException $e) {
            return false;
        }

        return true;
    }
}
