<?php

namespace duncan3dc\GitHub;

use function base64_decode;
use duncan3dc\GitHub\Exceptions\UnexpectedValueException;

final class File implements FileInterface
{
    /**
     * @var ApiInterface $api The Api instance to communicate with GitHub.
     */
    private $api;

    /**
     * @var \stdClass $data The file's data.
     */
    private $data;

    /**
     * @var string|null $contents The file's contents.
     */
    private $contents;


    /**
     * Create a new instance.
     *
     * @param \stdClass $item The tree item data from the GitHub Api
     * @param ApiInterface $api The Api instance to communicate with GitHub
     *
     * @return FileInterface
     */
    public static function fromTreeItem(\stdClass $item, ApiInterface $api): FileInterface
    {
        return new self($item, $api);
    }


    /**
     * Create a new instance.
     *
     * @param \stdClass $data The tree's data from the GitHub Api
     * @param ApiInterface $api The Api instance to communicate with GitHub
     */
    private function __construct(\stdClass $data, ApiInterface $api)
    {
        $this->data = $data;
        $this->api = $api;
    }


    /**
     * @inheritDoc
     */
    public function getName(): string
    {
        return $this->data->path;
    }


    /**
     * @inheritDoc
     */
    public function getSize(): int
    {
        return $this->data->size;
    }


    /**
     * @inheritDoc
     */
    public function getMode(): string
    {
        return $this->data->mode;
    }


    /**
     * @inheritDoc
     */
    public function getHash(): string
    {
        return $this->data->sha;
    }


    /**
     * @inheritDoc
     */
    public function getContents(): string
    {
        if ($this->contents === null) {
            $data = $this->api->get($this->data->url);
            $content = base64_decode($data->content);
            if ($content === false) {
                throw new UnexpectedValueException("Unable to decode the file contents from the GitHub API response");
            }
            $this->contents = $content;
        }

        return $this->contents;
    }
}
