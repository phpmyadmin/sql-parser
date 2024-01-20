<?php

declare(strict_types=1);

namespace PhpMyAdmin\SqlParser\Components;

use PhpMyAdmin\SqlParser\Component;
use PhpMyAdmin\SqlParser\Context;
use PhpMyAdmin\SqlParser\Parsers\Conditions;

use function count;

/**
 * Parses a reference to a CASE expression.
 */
final class CaseExpression implements Component
{
    /**
     * The value to be compared.
     *
     * @var Expression|null
     */
    public $value;

    /**
     * The conditions in WHEN clauses.
     *
     * @var Condition[][]
     */
    public $conditions = [];

    /**
     * The results matching with the WHEN clauses.
     *
     * @var Expression[]
     */
    public $results = [];

    /**
     * The values to be compared against.
     *
     * @var Expression[]
     */
    public $compareValues = [];

    /**
     * The result in ELSE section of expr.
     *
     * @var Expression|null
     */
    public $elseResult;

    /**
     * The alias of this CASE statement.
     *
     * @var string|null
     */
    public $alias;

    /**
     * The sub-expression.
     *
     * @var string
     */
    public $expr = '';

    public function build(): string
    {
        $ret = 'CASE ';
        if (isset($this->value)) {
            // Syntax type 0
            $ret .= $this->value . ' ';
            $valuesCount = count($this->compareValues);
            $resultsCount = count($this->results);
            for ($i = 0; $i < $valuesCount && $i < $resultsCount; ++$i) {
                $ret .= 'WHEN ' . $this->compareValues[$i] . ' ';
                $ret .= 'THEN ' . $this->results[$i] . ' ';
            }
        } else {
            // Syntax type 1
            $valuesCount = count($this->conditions);
            $resultsCount = count($this->results);
            for ($i = 0; $i < $valuesCount && $i < $resultsCount; ++$i) {
                $ret .= 'WHEN ' . Conditions::buildAll($this->conditions[$i]) . ' ';
                $ret .= 'THEN ' . $this->results[$i] . ' ';
            }
        }

        if (isset($this->elseResult)) {
            $ret .= 'ELSE ' . $this->elseResult . ' ';
        }

        $ret .= 'END';

        if ($this->alias) {
            $ret .= ' AS ' . Context::escape($this->alias);
        }

        return $ret;
    }

    public function __toString(): string
    {
        return $this->build();
    }
}
