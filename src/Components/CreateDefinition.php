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
     */
    public string|null $name = null;

    /**
     * Whether this field is a constraint or not.
     */
    public bool|null $isConstraint = null;

    /**
     * The data type of thew new column.
     */
    public DataType|null $type = null;

    /**
     * The key.
     */
    public Key|null $key = null;

    /**
     * The table that is referenced.
     */
    public Reference|null $references = null;

    /**
     * The options of this field.
     */
    public OptionsArray|null $options = null;

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
