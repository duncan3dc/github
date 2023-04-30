<?php

namespace duncan3dc\GitHub;

final class Directory implements DirectoryInterface
{
    /**
     * @var ApiInterface The Api instance to communicate with GitHub.
     */
    private $api;

    /**
     * @var \stdClass The directory's data.
     */
    private $data;

    /**
     * @var TreeInterface|null The tree that this directory represents.
     */
    private $tree;


    /**
     * Create a new instance.
     *
     * @param \stdClass $item The tree item data from the GitHub Api
     * @param ApiInterface $api The Api instance to communicate with GitHub
     *
     * @return DirectoryInterface
     */
    public static function fromTreeItem(\stdClass $item, ApiInterface $api): DirectoryInterface
    {
        return new self($item, $api);
    }


    /**
     * Create a new instance.
     *
     * @param \stdClass $data The directory's data from the GitHub Api
     * @param ApiInterface $api The Api instance to communicate with GitHub
     */
    private function __construct(\stdClass $data, ApiInterface $api)
    {
        $this->api = $api;
        $this->data = $data;
    }


    public function getName(): string
    {
        return $this->data->path;
    }


    /**
     * Get the tree instance for this directory.
     *
     * @return TreeInterface
     */
    private function getTree(): TreeInterface
    {
        if (!$this->tree) {
            $data = $this->api->get($this->data->url);
            $this->tree = Tree::fromApiResponse($data, $this->api);
        }

        return $this->tree;
    }


    public function getDirectories(): iterable
    {
        return $this->getTree()->getDirectories();
    }


    public function getDirectory(string $name): DirectoryInterface
    {
        return $this->getTree()->getDirectory($name);
    }


    public function hasDirectory(string $name): bool
    {
        return $this->getTree()->hasDirectory($name);
    }


    public function getFiles(): iterable
    {
        return $this->getTree()->getFiles();
    }


    public function getFile(string $name): FileInterface
    {
        return $this->getTree()->getFile($name);
    }


    public function hasFile(string $name): bool
    {
        return $this->getTree()->hasFile($name);
    }
}
