<?php

declare(strict_types=1);

namespace Paramon\ExampleClient\Serializer;

use Attribute;

#[Attribute(Attribute::TARGET_PROPERTY)]
class SerializeGroup
{
    private array $groups = [];

    public function __construct(array $groups)
    {
        $this->groups = $groups;
    }

    public function getGroups(): array
    {
        return $this->groups;
    }
}
