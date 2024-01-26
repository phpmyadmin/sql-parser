<?php

declare(strict_types=1);

namespace PhpMyAdmin\SqlParser\Components;

use PhpMyAdmin\SqlParser\Component;
use PhpMyAdmin\SqlParser\Context;

use function implode;
use function trim;

/**
 * `REFERENCES` keyword parser.
 */
final class Reference implements Component
{
    /**
     * The referenced table.
     */
    public Expression|null $table = null;

    /**
     * The referenced columns.
     *
     * @var string[]
     */
    public array $columns;

    /**
     * The options of the referencing.
     */
    public OptionsArray|null $options = null;

    /**
     * @param Expression|null   $table   the name of the table referenced
     * @param string[]          $columns the columns referenced
     * @param OptionsArray|null $options the options
     */
    public function __construct(Expression|null $table = null, array $columns = [], OptionsArray|null $options = null)
    {
        $this->table = $table;
        $this->columns = $columns;
        $this->options = $options;
    }

    public function build(): string
    {
        return trim(
            $this->table
            . ' (' . implode(', ', Context::escapeAll($this->columns)) . ') '
            . $this->options,
        );
    }

    public function __toString(): string
    {
        return $this->build();
    }
}
