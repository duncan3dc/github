<?php

namespace duncan3dc\GitHub\Issues;

final class Label
{
    /** @var \stdClass */
    private $data;


    public function __construct(\stdClass $data)
    {
        $this->data = $data;
    }


    /**
     * Get the unique ID of this label.
     *
     * @return int
     */
    public function getId(): int
    {
        return $this->data->id;
    }


    /**
     * Get the name of this label.
     *
     * @return string
     */
    public function getName(): string
    {
        return $this->data->name;
    }


    /**
     * Get the color of this label.
     *
     * @return string
     */
    public function getColor(): string
    {
        return $this->data->color;
    }


    /**
     * Get the description of this label.
     *
     * @return string
     */
    public function getDescription(): string
    {
        return $this->data->description;
    }
}
