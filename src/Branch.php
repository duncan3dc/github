<?php

namespace duncan3dc\GitHub;

use duncan3dc\GitHub\Exceptions\LogicException;
use Psr\Http\Message\ResponseInterface;

use function strtotime;
use function substr;
use function trim;

final class Branch implements BranchInterface
{
    use HttpTrait;

    /**
     * @var ApiInterface The Api instance to communicate with GitHub.
     */
    private $api;

    /**
     * @var \stdClass The branch's data.
     */
    private $data;

    /**
     * @var bool Has the full data for this branch been loaded or not.
     */
    private $loaded = false;

    /**
     * @var string The endpoint to get the full branch data from.
     */
    private $url;

    /**
     * @var TreeInterface|null The tree object for the head of the branch.
     */
    private $tree;


    /**
     * Create a new instance from an API request for the full branch data.
     *
     * @param \stdClass $data The branch's data from the GitHub Api
     * @param ApiInterface $api The Api instance to communicate with GitHub
     *
     * @return BranchInterface
     */
    public static function fromApiResponse(\stdClass $data, ApiInterface $api): BranchInterface
    {
        $branch = new self($data, $api);
        $branch->loaded = true;
        return $branch;
    }


    /**
     * Create a new instance from an API request for a list of branches.
     *
     * @param \stdClass $data The branch's basic data from the GitHub Api
     * @param string $url The endpoint to get the full branch data from
     * @param ApiInterface $api The Api instance to communicate with GitHub
     *
     * @return BranchInterface
     */
    public static function fromListResponse(\stdClass $data, string $url, ApiInterface $api): BranchInterface
    {
        $branch = new self($data, $api);
        $branch->url = $url;
        return $branch;
    }


    /**
     * Create a new instance.
     *
     * @param \stdClass $data The branch's data from the GitHub Api
     * @param ApiInterface $api The Api instance to communicate with GitHub
     */
    private function __construct(\stdClass $data, ApiInterface $api)
    {
        $this->api = $api;
        $this->data = $data;
    }


    /**
     * Lazy load the full data for this branch.
     *
     * @return \stdClass
     */
    private function getData(): \stdClass
    {
        if ($this->loaded) {
            return $this->data;
        }

        if (!$this->url) {
            throw new LogicException("Unable to get the branch information, no URL has been provided");
        }

        $this->data = $this->api->get($this->url);
        $this->loaded = true;

        return $this->data;
    }


    /**
     * Send a request and return the response.
     *
     * @param string $method The HTTP verb to use for the request
     * @param string $url The url to issue the request to (https://api.github.com is optional)
     * @param array<string, mixed> $data The parameters to send with the request
     *
     * @return ResponseInterface
     */
    public function request(string $method, string $url, array $data = []): ResponseInterface
    {
        $url = $this->getUrl($url);
        return $this->api->request($method, $url, $data);
    }


    /**
     * Generate a url using this branch as the base.
     *
     * @param string $path The path underneath this branch to hit
     *
     * @return string
     */
    private function getUrl(string $path): string
    {
        if (substr($path, 0, 4) === "http") {
            return $path;
        }

        if (substr($path, 0, 1) === "/") {
            return $path;
        }

        $url = $this->getData()->_links->self;

        $path = trim($path, "/");
        if ($path !== "") {
            $url .= "/{$path}";
        }

        return $url;
    }


    public function getName(): string
    {
        return $this->data->name;
    }


    public function getCommit(): string
    {
        return $this->data->commit->sha;
    }


    public function getHead(): \stdClass
    {
        return $this->getData()->commit->commit;
    }


    public function getDate(): int
    {
        return strtotime($this->getHead()->committer->date);
    }


    public function getProtection(): \stdClass
    {
        if (!$this->data->protected) {
            return new \stdClass();
        }

        return $this->get("protection");
    }


    /**
     * Get a Tree instance that represents the root of the branch.
     *
     * @return TreeInterface
     */
    private function getTree(): TreeInterface
    {
        if (!$this->tree) {
            $data = $this->api->get($this->getHead()->tree->url);
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
