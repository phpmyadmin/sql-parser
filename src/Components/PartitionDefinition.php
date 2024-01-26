<?php

declare(strict_types=1);

namespace PhpMyAdmin\SqlParser\Components;

use PhpMyAdmin\SqlParser\Component;
use PhpMyAdmin\SqlParser\Parsers\PartitionDefinitions;

use function trim;

/**
 * Parses the create definition of a partition.
 *
 * Used for parsing `CREATE TABLE` statement.
 */
final class PartitionDefinition implements Component
{
    /**
     * All field options.
     *
     * @var array<string, int|array<int, int|string>>
     * @psalm-var array<string, (positive-int|array{positive-int, ('var'|'var='|'expr'|'expr=')})>
     */
    public static array $partitionOptions = [
        'STORAGE ENGINE' => [
            1,
            'var',
        ],
        'ENGINE' => [
            1,
            'var',
        ],
        'COMMENT' => [
            2,
            'var',
        ],
        'DATA DIRECTORY' => [
            3,
            'var',
        ],
        'INDEX DIRECTORY' => [
            4,
            'var',
        ],
        'MAX_ROWS' => [
            5,
            'var',
        ],
        'MIN_ROWS' => [
            6,
            'var',
        ],
        'TABLESPACE' => [
            7,
            'var',
        ],
        'NODEGROUP' => [
            8,
            'var',
        ],
    ];

    /**
     * Whether this entry is a subpartition or a partition.
     */
    public bool|null $isSubpartition = null;

    /**
     * The name of this partition.
     */
    public string|null $name = null;

    /**
     * The type of this partition (what follows the `VALUES` keyword).
     */
    public string|null $type = null;

    /**
     * The expression used to defined this partition.
     */
    public Expression|string|null $expr = null;

    /**
     * The subpartitions of this partition.
     *
     * @var PartitionDefinition[]|null
     */
    public array|null $subpartitions = null;

    /**
     * The options of this field.
     */
    public OptionsArray|null $options = null;

    public function build(): string
    {
        if ($this->isSubpartition) {
            return trim('SUBPARTITION ' . $this->name . ' ' . $this->options);
        }

        $subpartitions = empty($this->subpartitions) ? '' : ' ' . PartitionDefinitions::buildAll($this->subpartitions);

        return trim(
            'PARTITION ' . $this->name
            . (empty($this->type) ? '' : ' VALUES ' . $this->type . ' ' . $this->expr . ' ')
            . (! empty($this->options) && ! empty($this->type) ? '' : ' ')
            . $this->options . $subpartitions,
        );
    }

    public function __toString(): string
    {
        return $this->build();
    }
}
