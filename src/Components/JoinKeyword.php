<?php

declare(strict_types=1);

namespace PhpMyAdmin\SqlParser\Components;

use PhpMyAdmin\SqlParser\Component;
use PhpMyAdmin\SqlParser\Parsers\Conditions;

use function array_search;

/**
 * `JOIN` keyword parser.
 */
final class JoinKeyword implements Component
{
    /**
     * Types of join.
     */
    public const JOINS = [
        'CROSS JOIN' => 'CROSS',
        'FULL JOIN' => 'FULL',
        'FULL OUTER JOIN' => 'FULL',
        'INNER JOIN' => 'INNER',
        'JOIN' => 'JOIN',
        'LEFT JOIN' => 'LEFT',
        'LEFT OUTER JOIN' => 'LEFT',
        'RIGHT JOIN' => 'RIGHT',
        'RIGHT OUTER JOIN' => 'RIGHT',
        'NATURAL JOIN' => 'NATURAL',
        'NATURAL LEFT JOIN' => 'NATURAL LEFT',
        'NATURAL RIGHT JOIN' => 'NATURAL RIGHT',
        'NATURAL LEFT OUTER JOIN' => 'NATURAL LEFT OUTER',
        'NATURAL RIGHT OUTER JOIN' => 'NATURAL RIGHT OUTER',
        'STRAIGHT_JOIN' => 'STRAIGHT',
    ];

    /**
     * Type of this join.
     *
     * @see JoinKeyword::JOINS
     */
    public string|null $type = null;

    /**
     * Join expression.
     */
    public Expression|null $expr = null;

    /**
     * Join conditions.
     *
     * @var Condition[]|null
     */
    public array|null $on = null;

    /**
     * Columns in Using clause.
     */
    public ArrayObj|null $using = null;

    /**
     * @see JoinKeyword::JOINS
     *
     * @param string|null      $type  Join type
     * @param Expression|null  $expr  join expression
     * @param Condition[]|null $on    join conditions
     * @param ArrayObj|null    $using columns joined
     */
    public function __construct(
        string|null $type = null,
        Expression|null $expr = null,
        array|null $on = null,
        ArrayObj|null $using = null,
    ) {
        $this->type = $type;
        $this->expr = $expr;
        $this->on = $on;
        $this->using = $using;
    }

    public function build(): string
    {
        return array_search($this->type, self::JOINS) . ' ' . $this->expr
            . (! empty($this->on) ? ' ON ' . Conditions::buildAll($this->on) : '')
            . (! empty($this->using) ? ' USING ' . $this->using->build() : '');
    }

    public function __toString(): string
    {
        return $this->build();
    }
}
