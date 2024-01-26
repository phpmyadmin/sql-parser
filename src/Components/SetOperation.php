<?php

declare(strict_types=1);

namespace PhpMyAdmin\SqlParser\Components;

use PhpMyAdmin\SqlParser\Component;

final class SetOperation implements Component
{
    /**
     * The name of the column that is being updated.
     */
    public string $column;

    /**
     * The new value.
     */
    public string $value;

    /**
     * @param string $column Field's name..
     * @param string $value  new value
     */
    public function __construct(string $column = '', string $value = '')
    {
        $this->column = $column;
        $this->value = $value;
    }

    public function build(): string
    {
        return $this->column . ' = ' . $this->value;
    }

    public function __toString(): string
    {
        return $this->build();
    }
}
