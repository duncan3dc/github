<?php

namespace duncan3dc\GitHub;

use duncan3dc\GitHub\Issues\Label;
use Psr\Http\Message\ResponseInterface;

use function substr;
use function trim;

final class PullRequest implements PullRequestInterface
{
    use HttpTrait;

    /** @var int If this pr has been created with just a number */
    private const NOT_LOADED = 0;

    /** @var int If this pr was created from a list response */
    private const LIST_LOADED = 1;

    /** @var int If've fully loaded all of the pr data */
    private const FULL_LOADED = 2;

    /**
     * @var RepositoryInterface The repository this pr is part of.
     */
    private $repository;

    /**
     * @var int The unique ID of this pr.
     */
    private $number;

    /** @var int The state of the $data property */
    private $loaded = self::NOT_LOADED;

    /** @var \stdClass|null */
    private $data;

    /** @var BranchInterface */
    private $branch;

    /** @var BranchInterface */
    private $base;

    /**
     * @var string The version of this pr we are working with.
     */
    private $commit;


    /**
     * Create a new instance from an API request for a list of prs.
     *
     * @param \stdClass $data The basic data from the GitHub Api
     * @param RepositoryInterface $repository The Repository this pr is part of
     *
     * @return PullRequestInterface
     */
    public static function fromListResponse(\stdClass $data, RepositoryInterface $repository): PullRequestInterface
    {
        $pull = new self($repository, $data->number);
        $pull->data = $data;
        $pull->loaded = self::LIST_LOADED;
        return $pull;
    }



    /**
     * Create a new instance.
     *
     * @param RepositoryInterface $repository The repository this pr is part of
     * @param int $number The unique ID of this pr
     */
    public function __construct(RepositoryInterface $repository, int $number)
    {
        $this->repository = $repository;
        $this->number = $number;
    }


    /**
     * @inheritDoc
     */
    public function request(string $method, string $url, array $data = []): ResponseInterface
    {
        $url = $this->getUrl($url);
        return $this->repository->request($method, $url, $data);
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

        $url = "pulls/" . $this->getNumber();

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
     * Lazy load the basic data for this pr.
     *
     * @return \stdClass
     */
    private function getListData(): \stdClass
    {
        if ($this->loaded === self::LIST_LOADED && $this->data !== null) {
            return $this->data;
        }

        return $this->getFullData();
    }


    /**
     * Lazy load the full data for this pr.
     *
     * @return \stdClass
     */
    private function getFullData(): \stdClass
    {
        if ($this->loaded !== self::FULL_LOADED || $this->data === null) {
            $this->data = $this->get("");
            $this->loaded = self::FULL_LOADED;
        }

        return $this->data;
    }


    /**
     * @inheritDoc
     */
    public function getCommit(): string
    {
        if ($this->commit === null) {
            $this->commit = $this->getListData()->head->sha;
        }

        return $this->commit;
    }


    /** @inheritDoc */
    public function getBranch(): BranchInterface
    {
        if ($this->branch === null) {
            $name = $this->getListData()->head->ref;
            $this->branch = $this->repository->getBranch($name);
        }

        return $this->branch;
    }


    public function getBaseBranch(): BranchInterface
    {
        if ($this->base === null) {
            $name = $this->getListData()->base->ref;
            $this->base = $this->repository->getBranch($name);
        }

        return $this->base;
    }


    /** @inheritDoc */
    public function getMergeableState(): string
    {
        return $this->getFullData()->mergeable_state;
    }


    /** @inheritDoc */
    public function getLabels(): iterable
    {
        foreach ($this->getListData()->labels as $data) {
            yield new Label($data);
        }
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
