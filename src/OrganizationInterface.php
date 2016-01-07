<?php

namespace duncan3dc\GitHub;

interface OrganizationInterface extends ApiInterface
{
    /**
     * Get the name of this organization.
     *
     * @return string
     */
    public function getName(): string;
}
