<?php

declare(strict_types=1);

namespace PhpMyAdmin\SqlParser\Components;

use PhpMyAdmin\SqlParser\Component;

use function implode;
use function strtolower;
use function trim;

/**
 * Parses a data type.
 */
final class DataType implements Component
{
    /**
     * The name of the data type.
     */
    public string|null $name = null;

    /**
     * The parameters of this data type.
     *
     * Some data types have no parameters.
     * Numeric types might have parameters for the maximum number of digits,
     * precision, etc.
     * String types might have parameters for the maximum length stored.
     * `ENUM` and `SET` have parameters for possible values.
     *
     * For more information, check the MySQL manual.
     *
     * @var int[]|string[]
     */
    public array $parameters = [];

    /**
     * The options of this data type.
     */
    public OptionsArray|null $options = null;

    public bool $lowercase = false;

    /**
     * @param string|null       $name       the name of this data type
     * @param int[]|string[]    $parameters the parameters (size or possible values)
     * @param OptionsArray|null $options    the options of this data type
     */
    public function __construct(
        string|null $name = null,
        array $parameters = [],
        OptionsArray|null $options = null,
    ) {
        $this->name = $name;
        $this->parameters = $parameters;
        $this->options = $options;
    }

    public function build(): string
    {
        $name = $this->lowercase ? strtolower($this->name) : $this->name;

        $parameters = '';
        if ($this->parameters !== []) {
            $parameters = '(' . implode(',', $this->parameters) . ')';
        }

        return trim($name . $parameters . ' ' . $this->options);
    }

    public function __toString(): string
    {
        return $this->build();
    }
}
