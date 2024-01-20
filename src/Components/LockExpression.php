<?php

declare(strict_types=1);

namespace PhpMyAdmin\SqlParser\Components;

use PhpMyAdmin\SqlParser\Component;

use function implode;

/**
 * Parses a reference to a LOCK expression.
 */
final class LockExpression implements Component
{
    /**
     * The table to be locked.
     *
     * @var Expression
     */
    public $table;

    /**
     * The type of lock to be applied.
     *
     * @var string
     */
    public $type;

    public function build(): string
    {
        return $this->table . ' ' . $this->type;
    }

    /** @param LockExpression[] $component the component to be built */
    public static function buildAll(array $component): string
    {
        return implode(', ', $component);
    }

    public function __toString(): string
    {
        return $this->build();
    }
}
