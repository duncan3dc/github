<?php

namespace duncan3dc\GitHub;

use Psr\Http\Message\ResponseInterface;

use function assert;
use function is_string;
use function substr;
use function trim;

final class Repository implements RepositoryInterface
{
    use HttpTrait;

    /**
     * @var ApiInterface $api The instance to communicate with GitHub.
     */
    private $api;

    /**
     * @var \stdClass $data The repository's data.
     */
    private $data;


    /**
     * Create a new instance.
     *
     * @param string $owner The owner of this repository (organization or user)
     * @param string $name The name of the repository this instance represents
     * @param ApiInterface $api The instance to communicate with GitHub
     *
     * @return RepositoryInterface
     */
    public static function fromName(string $owner, string $name, ApiInterface $api): RepositoryInterface
    {
        $data = $api->get("repos/{$owner}/{$name}");

        return new self($data, $api);
    }


    /**
     * Create a new instance.
     *
     * @param \stdClass $data The repository's data from the GitHub Api
     * @param ApiInterface $api The instance to communicate with GitHub
     *
     * @return RepositoryInterface
     */
    public static function fromApiResponse(\stdClass $data, ApiInterface $api): RepositoryInterface
    {
        return new self($data, $api);
    }


    /**
     * Create a new instance.
     *
     * @param \stdClass $data The repository's data from the GitHub Api
     * @param ApiInterface $api The instance to communicate with GitHub
     */
    private function __construct(\stdClass $data, ApiInterface $api)
    {
        $this->api = $api;
        $this->data = $data;
    }


    public function getName(): string
    {
        return $this->data->name;
    }


    public function getFullName(): string
    {
        return $this->data->full_name;
    }


    public function getDescription(): string
    {
        return (string) $this->data->description;
    }


    public function isPrivate(): bool
    {
        return $this->data->private ?? false;
    }


    public function isPublic(): bool
    {
        return !$this->isPrivate();
    }


    public function isFork(): bool
    {
        return $this->data->fork ?? false;
    }


    public function isArchived(): bool
    {
        return $this->data->archived ?? false;
    }


    public function request(string $method, string $url, array $data = []): ResponseInterface
    {
        $url = $this->getUrl($url);
        return $this->api->request($method, $url, $data);
    }


    /**
     * Generate a url using this repository as the base.
     *
     * @param string $path The path underneath this repository to hit
     *
     * @return string
     */
    private function getUrl($path)
    {
        if (substr($path, 0, 4) === "http") {
            return $path;
        }

        if (substr($path, 0, 1) === "/") {
            return $path;
        }

        return "repos/" . $this->getFullName() . "/" . trim($path, "/");
    }


    public function getBranches(): iterable
    {
        $data = $this->getAll("branches");

        foreach ($data as $item) {
            assert($item instanceof \stdClass);
            $url = $this->getUrl("branches/{$item->name}");
            yield Branch::fromListResponse($item, $url, $this->api);
        }
    }


    public function getDefaultBranch(): BranchInterface
    {
        return $this->getBranch($this->data->default_branch);
    }


    public function getBranch(string $branch): BranchInterface
    {
        $data = $this->get("branches/{$branch}");

        return Branch::fromApiResponse($data, $this->api);
    }


    public function getPullRequests(array $options = []): iterable
    {
        $data = $this->getAll("pulls", $options);
        foreach ($data as $item) {
            assert($item instanceof \stdClass);
            yield PullRequest::fromListResponse($item, $this);
        }
    }


    public function getPullRequest(int $number): PullRequestInterface
    {
        return new PullRequest($this, $number);
    }


    public function getTags(): iterable
    {
        $data = $this->getAll("tags");

        foreach ($data as $item) {
            assert($item instanceof \stdClass);
            yield Tag::fromListResponse($item);
        }
    }
}
