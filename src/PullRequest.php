<?php

namespace duncan3dc\GitHub;

use Psr\Http\Message\ResponseInterface;

use function substr;
use function trim;

final class PullRequest implements PullRequestInterface
{
    use HttpTrait;

    /**
     * @var ApiInterface The Api instance to communicate with GitHub.
     */
    private $api;

    /**
     * @var RepositoryInterface The repository this pr is part of.
     */
    private $repository;

    /**
     * @var int The unique ID of this pr.
     */
    private $number;

    /** @var \stdClass|null */
    private $data;

    /**
     * @var string The version of this pr we are working with.
     */
    private $commit;


    /**
     * Create a new instance.
     *
     * @param RepositoryInterface $repository The repository this pr is part of
     * @param int $number The unique ID of this pr
     * @param ApiInterface $api The Api instance to communicate with GitHub.
     */
    public function __construct(RepositoryInterface $repository, int $number, ApiInterface $api)
    {
        $this->repository = $repository;
        $this->number = $number;
        $this->api = $api;
    }


    /**
     * @inheritDoc
     */
    public function request(string $method, string $url, array $data = []): ResponseInterface
    {
        $url = $this->getUrl($url);
        return $this->api->request($method, $url, $data);
    }


    /**
     * Generate a url using this pull request as the base.
     *
     * @param string $path The path underneath this pull request to hit
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

        $url = "repos/" . $this->repository->getFullName() . "/pulls/" . $this->getNumber();

        $path = trim($path, "/");
        if ($path !== "") {
            $url .= "/{$path}";
        }

        return $url;
    }


    /**
     * @inheritDoc
     */
    public function getRepository(): RepositoryInterface
    {
        return $this->repository;
    }


    /**
     * @inheritDoc
     */
    public function getNumber(): int
    {
        return $this->number;
    }


    /**
     * @inheritDoc
     */
    public function getFiles(): iterable
    {
        return $this->getAll("files");
    }


    /**
     * @inheritDoc
     */
    public function getComments(): iterable
    {
        return $this->getAll("comments");
    }


    /**
     * @inheritDoc
     */
    public function withCommit(string $commit): PullRequestInterface
    {
        $pull = clone $this;
        $pull->commit = $commit;
        return $pull;
    }


    /**
     * Lazy load the full data for this pr.
     *
     * @return \stdClass
     */
    private function getData(): \stdClass
    {
        if ($this->data === null) {
            $this->data = $this->get("");
        }

        return $this->data;
    }


    /**
     * @inheritDoc
     */
    public function getCommit(): string
    {
        if ($this->commit === null) {
            $this->commit = $this->getData()->head->sha;
        }

        return $this->commit;
    }


    /** @inheritDoc */
    public function getMergeableState(): string
    {
        return $this->getData()->mergeable_state;
    }


    /**
     * @inheritDoc
     */
    public function addComment(string $comment, string $path, int $position): void
    {
        $this->post("comments", [
            "body"      =>  $comment,
            "commit_id" =>  $this->getCommit(),
            "path"      =>  $path,
            "position"  =>  $position,
        ]);
    }
}
