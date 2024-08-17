<?php

declare(strict_types=1);

namespace Paramon\ExampleClient\Dto;

use Paramon\ExampleClient\Serializer\SerializeGroup;

class Comment
{
    public function __construct(
        public int $id,
        #[SerializeGroup(["content"])]
        public string $name,
        #[SerializeGroup(["content"])]
        public string $text,
    ) {
    }
}
