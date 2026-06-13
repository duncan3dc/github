<?php

namespace duncan3dc\GitHub;

use GuzzleHttp\Client;
use GuzzleHttp\ClientInterface;
use Psr\Http\Message\ResponseInterface;

use function count;
use function substr;
use function trim;

final class User implements UserInterface
{
    use HttpTrait;

    private string $token;

    private \stdClass $data;

    private ClientInterface $client;



    public function __construct(string $token, ?ClientInterface $client = null)
    {
        $this->token = $token;

        if ($client === null) {
            $client = new Client([
                "headers" => [
                    "Accept" => "application/vnd.github.machine-man-preview+json",
                ],
            ]);
        }
        $this->client = $client;

        $this->data = $this->get("/user");
    }


    public function getName(): string
    {
        return $this->data->login;
    }

    public function getDisplayName(): string
    {
        return $this->data->name;
    }

    public function getCompany(): string
    {
        return $this->data->company;
    }

    public function request(string $method, string $url, array $data = []): ResponseInterface
    {
        $params = [
            "headers" => [
                "Authorization" => "token " . $this->token,
            ],
        ];

        if (count($data) > 0) {
            if ($method === "GET") {
                $params["query"] = $data;
            } else {
                $params["json"] = $data;
            }
        }

        if (substr($url, 0, 5) !== "https") {
            $url = "https://api.github.com/" . trim($url, "/");
        }

        return $this->client->request($method, $url, $params);
    }


    /**
     * Generate a url using this user as the base.
     *
     * @param string $path The path underneath this user to hit
     */
    private function getUrl(string $path): string
    {
        if (substr($path, 0, 4) === "http") {
            return $path;
        }

        if (substr($path, 0, 1) === "/") {
            return $path;
        }

        $url = "users/" . $this->getName();

        $path = trim($path, "/");
        if ($path !== "") {
            $url .= "/{$path}";
        }

        return $url;
    }


    /**
     * Get all of the repositories for this user.
     *
     * @return RepositoryInterface[]
     */
    public function getRepositories(): iterable
    {
        /** @var \Traversable<Repository> $repositories */
        $repositories = $this->getAll($this->getUrl("repos"), [], function (\stdClass $data) {
            foreach ($data as $item) {
                yield Repository::fromApiResponse($item, $this);
            }
        });

        foreach ($repositories as $repository) {
            yield $repository;
        }
    }


    /**
     * Get a repository instance.
     *
     * @param string $name The name of the repository
     */
    public function getRepository(string $name): RepositoryInterface
    {
        $data = $this->get("/repos/" . $this->getName() . "/" . $name);

        return Repository::fromApiResponse($data, $this);
    }


    /**
     * @return iterable<string>
     */
    public function getFollowers(): iterable
    {
        /** @var \Traversable<string> $followers */
        $followers = $this->getAll($this->getUrl("followers"), [], function (\stdClass $data) {
            foreach ($data as $item) {
                yield $item->login;
            }
        });
        return $followers;
    }


    /**
     * @return iterable<string>
     */
    public function getFollowing(): iterable
    {
        /** @var \Traversable<string> $following */
        $following = $this->getAll($this->getUrl("following"), [], function (\stdClass $data) {
            foreach ($data as $item) {
                yield $item->login;
            }
        });
        return $following;
    }


    /**
     * @return iterable<string>
     */
    public function getStarred(): iterable
    {
        /** @var \Traversable<string> $starred */
        $starred = $this->getAll($this->getUrl("starred"), [], function (\stdClass $data) {
            foreach ($data as $item) {
                yield $item->full_name;
            }
        });
        return $starred;
    }
}
