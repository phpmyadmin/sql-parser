<?php

declare(strict_types=1);

namespace PhpMyAdmin\SqlParser;

use JsonSerializable;

use function array_merge;
use function get_mangled_object_vars;

abstract class SerializableComponent implements Component, JsonSerializable
{
    public function __toString(): string
    {
        return $this->build();
    }

    /** @return array<mixed> */
    public function jsonSerialize(): array
    {
        return array_merge(['@type' => static::class], get_mangled_object_vars($this));
    }
}
