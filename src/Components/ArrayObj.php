<?php

declare(strict_types=1);

namespace PhpMyAdmin\SqlParser\Components;

use PhpMyAdmin\SqlParser\Component;

use function implode;

/**
 * Parses an array.
 */
final class ArrayObj implements Component
{
    /**
     * The array that contains the unprocessed value of each token.
     *
     * @var string[]
     */
    public array $raw = [];

    /**
     * The array that contains the processed value of each token.
     *
     * @var string[]
     */
    public array $values = [];

    /**
     * @param string[] $raw    the unprocessed values
     * @param string[] $values the processed values
     */
    public function __construct(array $raw = [], array $values = [])
    {
        $this->raw = $raw;
        $this->values = $values;
    }

    public function build(): string
    {
        if ($this->raw !== []) {
            return '(' . implode(', ', $this->raw) . ')';
        }

        return '(' . implode(', ', $this->values) . ')';
    }

    public function __toString(): string
    {
        return $this->build();
    }
}
