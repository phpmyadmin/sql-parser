<?php

declare(strict_types=1);

namespace PhpMyAdmin\SqlParser\Components;

use PhpMyAdmin\SqlParser\Component;

use function trim;

final class Condition implements Component
{
    /**
     * Identifiers recognized.
     *
     * @var array<int, mixed>
     */
    public $identifiers = [];

    /**
     * Whether this component is an operator.
     *
     * @var bool
     */
    public $isOperator = false;

    /**
     * The condition.
     *
     * @var string
     */
    public $expr;

    /** @param string $expr the condition or the operator */
    public function __construct(string|null $expr = null)
    {
        $this->expr = trim((string) $expr);
    }

    public function build(): string
    {
        return $this->expr;
    }

    public function __toString(): string
    {
        return $this->build();
    }
}
