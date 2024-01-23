<?php

declare(strict_types=1);

namespace PhpMyAdmin\SqlParser\Components;

use AllowDynamicProperties;
use PhpMyAdmin\SqlParser\Component;
use PhpMyAdmin\SqlParser\Context;

use function implode;

/**
 * Parses a reference to an expression (column, table or database name, function
 * call, mathematical expression, etc.).
 */
#[AllowDynamicProperties]
final class Expression implements Component
{
    /**
     * The name of this database.
     *
     * @var string|null
     */
    public $database;

    /**
     * The name of this table.
     *
     * @var string|null
     */
    public $table;

    /**
     * The name of the column.
     *
     * @var string|null
     */
    public $column;

    /**
     * The sub-expression.
     *
     * @var string|null
     */
    public $expr = '';

    /**
     * The alias of this expression.
     *
     * @var string|null
     */
    public $alias;

    /**
     * The name of the function.
     *
     * @var mixed
     */
    public $function;

    /**
     * The type of subquery.
     *
     * @var string|null
     */
    public $subquery;

    /**
     * Syntax:
     *     new Expression('expr')
     *     new Expression('expr', 'alias')
     *     new Expression('database', 'table', 'column')
     *     new Expression('database', 'table', 'column', 'alias')
     *
     * If the database, table or column name is not required, pass an empty
     * string.
     *
     * @param string|null $database The name of the database or the expression.
     * @param string|null $table    The name of the table or the alias of the expression.
     * @param string|null $column   the name of the column
     * @param string|null $alias    the name of the alias
     */
    public function __construct(
        string|null $database = null,
        string|null $table = null,
        string|null $column = null,
        string|null $alias = null,
    ) {
        if (($column === null) && ($alias === null)) {
            $this->expr = $database; // case 1
            $this->alias = $table; // case 2
        } else {
            $this->database = $database; // case 3
            $this->table = $table; // case 3
            $this->column = $column; // case 3
            $this->alias = $alias; // case 4
        }
    }

    public function build(): string
    {
        if ($this->expr !== '' && $this->expr !== null) {
            $ret = $this->expr;
        } else {
            $fields = [];
            if (isset($this->database) && ($this->database !== '')) {
                $fields[] = $this->database;
            }

            if (isset($this->table) && ($this->table !== '')) {
                $fields[] = $this->table;
            }

            if (isset($this->column) && ($this->column !== '')) {
                $fields[] = $this->column;
            }

            $ret = implode('.', Context::escapeAll($fields));
        }

        if (! empty($this->alias)) {
            $ret .= ' AS ' . Context::escape($this->alias);
        }

        return $ret;
    }

    public function __toString(): string
    {
        return $this->build();
    }
}
