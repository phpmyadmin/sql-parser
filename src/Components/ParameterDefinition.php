<?php

declare(strict_types=1);

namespace PhpMyAdmin\SqlParser\Components;

use PhpMyAdmin\SqlParser\Component;
use PhpMyAdmin\SqlParser\Context;

use function trim;

/**
 * The definition of a parameter of a function or procedure.
 */
final class ParameterDefinition implements Component
{
    /**
     * The name of the new column.
     *
     * @var string
     */
    public $name;

    /**
     * Parameter's direction (IN, OUT or INOUT).
     *
     * @var string
     */
    public $inOut;

    /**
     * The data type of thew new column.
     *
     * @var DataType
     */
    public $type;

    /**
     * @param string   $name  parameter's name
     * @param string   $inOut parameter's directional type (IN / OUT or None)
     * @param DataType $type  parameter's type
     */
    public function __construct(string|null $name = null, string|null $inOut = null, DataType|null $type = null)
    {
        $this->name = $name;
        $this->inOut = $inOut;
        $this->type = $type;
    }

    public function build(): string
    {
        $tmp = '';
        if (! empty($this->inOut)) {
            $tmp .= $this->inOut . ' ';
        }

        return trim(
            $tmp . Context::escape($this->name) . ' ' . $this->type,
        );
    }

    public function __toString(): string
    {
        return $this->build();
    }
}
