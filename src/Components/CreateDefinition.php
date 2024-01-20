<?php

declare(strict_types=1);

namespace PhpMyAdmin\SqlParser\Components;

use PhpMyAdmin\SqlParser\Component;
use PhpMyAdmin\SqlParser\Context;

use function trim;

/**
 * Parses the create definition of a column or a key.
 *
 * Used for parsing `CREATE TABLE` statement.
 */
final class CreateDefinition implements Component
{
    /**
     * The name of the new column.
     *
     * @var string|null
     */
    public $name;

    /**
     * Whether this field is a constraint or not.
     *
     * @var bool|null
     */
    public $isConstraint;

    /**
     * The data type of thew new column.
     *
     * @var DataType|null
     */
    public $type;

    /**
     * The key.
     *
     * @var Key|null
     */
    public $key;

    /**
     * The table that is referenced.
     *
     * @var Reference|null
     */
    public $references;

    /**
     * The options of this field.
     *
     * @var OptionsArray|null
     */
    public $options;

    /**
     * @param string|null       $name         the name of the field
     * @param OptionsArray|null $options      the options of this field
     * @param DataType|Key|null $type         the data type of this field or the key
     * @param bool              $isConstraint whether this field is a constraint or not
     * @param Reference|null    $references   references
     */
    public function __construct(
        string|null $name = null,
        OptionsArray|null $options = null,
        DataType|Key|null $type = null,
        bool $isConstraint = false,
        Reference|null $references = null,
    ) {
        $this->name = $name;
        $this->options = $options;
        if ($type instanceof DataType) {
            $this->type = $type;
        } elseif ($type instanceof Key) {
            $this->key = $type;
            $this->isConstraint = $isConstraint;
            $this->references = $references;
        }
    }

    public function build(): string
    {
        $tmp = '';

        if ($this->isConstraint) {
            $tmp .= 'CONSTRAINT ';
        }

        if (isset($this->name) && ($this->name !== '')) {
            $tmp .= Context::escape($this->name) . ' ';
        }

        if (! empty($this->type)) {
            $this->type->lowercase = true;
            $tmp .= $this->type->build() . ' ';
        }

        if (! empty($this->key)) {
            $tmp .= $this->key . ' ';
        }

        if (! empty($this->references)) {
            $tmp .= 'REFERENCES ' . $this->references . ' ';
        }

        $tmp .= $this->options;

        return trim($tmp);
    }

    public function __toString(): string
    {
        return $this->build();
    }
}
