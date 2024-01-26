<?php

declare(strict_types=1);

namespace PhpMyAdmin\SqlParser\Components;

use PhpMyAdmin\SqlParser\Component;

/**
 * `ORDER BY` keyword parser.
 */
final class OrderKeyword implements Component
{
    /**
     * The expression that is used for ordering.
     */
    public Expression|null $expr = null;

    /**
     * The order type.
     */
    public string $type;

    /**
     * @param Expression|null $expr the expression that we are sorting by
     * @param string          $type the sorting type
     */
    public function __construct(Expression|null $expr = null, string $type = 'ASC')
    {
        $this->expr = $expr;
        $this->type = $type;
    }

    public function build(): string
    {
        return $this->expr . ' ' . $this->type;
    }

    public function __toString(): string
    {
        return $this->build();
    }
}
