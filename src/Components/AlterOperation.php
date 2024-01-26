<?php

declare(strict_types=1);

namespace PhpMyAdmin\SqlParser\Components;

use PhpMyAdmin\SqlParser\Component;
use PhpMyAdmin\SqlParser\Parsers\PartitionDefinitions;
use PhpMyAdmin\SqlParser\Token;
use PhpMyAdmin\SqlParser\TokensList;

use function trim;

/**
 * Parses an alter operation.
 */
final class AlterOperation implements Component
{
    /**
     * Options of this operation.
     */
    public OptionsArray|null $options = null;

    /**
     * The altered field.
     */
    public Expression|string|null $field = null;

    /**
     * The partitions.
     *
     * @var PartitionDefinition[]|null
     */
    public array|null $partitions = null;

    /**
     * @param OptionsArray|null          $options    options of alter operation
     * @param Expression|string|null     $field      altered field
     * @param PartitionDefinition[]|null $partitions partitions definition found in the operation
     * @param Token[]                    $unknown    unparsed tokens found at the end of operation
     */
    public function __construct(
        OptionsArray|null $options = null,
        Expression|string|null $field = null,
        array|null $partitions = null,
        public array $unknown = [],
    ) {
        $this->partitions = $partitions;
        $this->options = $options;
        $this->field = $field;
        $this->unknown = $unknown;
    }

    public function build(): string
    {
        // Specific case of RENAME COLUMN that insert the field between 2 options.
        $afterFieldsOptions = new OptionsArray();
        if ($this->options->has('RENAME') && $this->options->has('COLUMN')) {
            $afterFieldsOptions = clone $this->options;
            $afterFieldsOptions->remove('RENAME');
            $afterFieldsOptions->remove('COLUMN');
            $this->options->remove('TO');
        }

        $ret = $this->options . ' ';
        if (isset($this->field) && ($this->field !== '')) {
            $ret .= $this->field . ' ';
        }

        $ret .= $afterFieldsOptions . TokensList::buildFromArray($this->unknown);

        if (isset($this->partitions)) {
            $ret .= PartitionDefinitions::buildAll($this->partitions);
        }

        return trim($ret);
    }

    public function __toString(): string
    {
        return $this->build();
    }
}
