<?php

namespace duncan3dc\GitHub;

final class Tag implements TagInterface
{
    /**
     * @var \stdClass The tag's data.
     */
    private $data;


    /**
     * Create a new instance from an API request for a list of tags.
     *
     * @param \stdClass $data The tag's basic data from the GitHub Api
     *
     * @return TagInterface
     */
    public static function fromListResponse(\stdClass $data): TagInterface
    {
        return new self($data);
    }


    /**
     * Create a new instance.
     *
     * @param \stdClass $data The tag's data from the GitHub Api
     */
    private function __construct(\stdClass $data)
    {
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
    public function getCommit(): string
    {
        return $this->data->commit->sha;
    }
}
