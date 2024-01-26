<?php

declare(strict_types=1);

namespace PhpMyAdmin\SqlParser\Components;

use PhpMyAdmin\SqlParser\Component;
use PhpMyAdmin\SqlParser\Context;

use function implode;
use function trim;

/**
 * Parses the definition of a key.
 *
 * Used for parsing `CREATE TABLE` statement.
 */
final class Key implements Component
{
    /**
     * The name of this key.
     */
    public string|null $name = null;

    /**
     * The key columns
     *
     * @var array<int, array<string, int|string>>
     * @phpstan-var array{name?: string, length?: int, order?: string}[]
     */
    public array $columns;

    /**
     * The type of this key.
     */
    public string|null $type = null;

    /**
     * The expression if the Key is not using column names
     */
    public string|null $expr = null;

    /**
     * The options of this key or null if none where found.
     */
    public OptionsArray|null $options = null;

    /**
     * @param string|null                           $name    the name of the key
     * @param array<int, array<string, int|string>> $columns the columns covered by this key
     * @param string|null                           $type    the type of this key
     * @param OptionsArray|null                     $options the options of this key
     * @phpstan-param array{name?: string, length?: int, order?: string}[] $columns
     */
    public function __construct(
        string|null $name = null,
        array $columns = [],
        string|null $type = null,
        OptionsArray|null $options = null,
    ) {
        $this->name = $name;
        $this->columns = $columns;
        $this->type = $type;
        $this->options = $options;
    }

    public function build(): string
    {
        $ret = $this->type . ' ';
        if (! empty($this->name)) {
            $ret .= Context::escape($this->name) . ' ';
        }

        if ($this->expr !== null) {
            return $ret . '(' . $this->expr . ') ' . $this->options;
        }

        $columns = [];
        foreach ($this->columns as $column) {
            $tmp = '';
            if (isset($column['name'])) {
                $tmp .= Context::escape($column['name']);
            }

            if (isset($column['length'])) {
                $tmp .= '(' . $column['length'] . ')';
            }

            if (isset($column['order'])) {
                $tmp .= ' ' . $column['order'];
            }

            $columns[] = $tmp;
        }

        $ret .= '(' . implode(',', $columns) . ') ' . $this->options;

        return trim($ret);
    }

    public function __toString(): string
    {
        return $this->build();
    }
}
