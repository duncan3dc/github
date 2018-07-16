<?php

namespace duncan3dc\GitHub;

use Psr\Http\Message\ResponseInterface;
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


    /**
     * @inheritDoc
     */
    public function getName(): string
    {
        return $this->data->name;
    }


    /**
     * @inheritDoc
     */
    public function getFullName(): string
    {
        return $this->data->full_name;
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


    /**
     * @inheritDoc
     */
    public function getBranches(): iterable
    {
        $data = $this->getAll("branches");

        foreach ($data as $item) {
            $url = $this->getUrl("branches/{$item->name}");
            yield Branch::fromListResponse($item, $url, $this->api);
        }
    }


    /**
     * @inheritDoc
     */
    public function getBranch(string $branch): BranchInterface
    {
        $data = $this->get("branches/{$branch}");

        return Branch::fromApiResponse($data, $this->api);
    }


    /**
     * @inheritDoc
     */
    public function getPullRequest(int $number): PullRequestInterface
    {
        return new PullRequest($this, $number, $this->api);
    }
}
