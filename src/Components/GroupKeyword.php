<?php

declare(strict_types=1);

namespace PhpMyAdmin\SqlParser\Components;

use PhpMyAdmin\SqlParser\Component;

use function trim;

/**
 * `GROUP BY` keyword parser.
 */
final class GroupKeyword implements Component
{
    /** @var 'ASC'|'DESC'|null */
    public string|null $type = null;

    /**
     * The expression that is used for grouping.
     */
    public Expression|null $expr = null;

    /** @param Expression|null $expr the expression that we are sorting by */
    public function __construct(Expression|null $expr = null)
    {
        $this->expr = $expr;
    }

    public function build(): string
    {
        return trim((string) $this->expr);
    }

    public function __toString(): string
    {
        return $this->build();
    }
}
