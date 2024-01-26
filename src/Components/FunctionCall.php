<?php

declare(strict_types=1);

namespace PhpMyAdmin\SqlParser\Components;

use PhpMyAdmin\SqlParser\Component;

use function is_array;

/**
 * Parses a function call.
 */
final class FunctionCall implements Component
{
    /**
     * The name of this function.
     */
    public string|null $name = null;

    /**
     * The list of parameters.
     */
    public ArrayObj|null $parameters = null;

    /**
     * @param string|null            $name       the name of the function to be called
     * @param string[]|ArrayObj|null $parameters the parameters of this function
     */
    public function __construct(string|null $name = null, array|ArrayObj|null $parameters = null)
    {
        $this->name = $name;
        if (is_array($parameters)) {
            $this->parameters = new ArrayObj($parameters);
        } elseif ($parameters instanceof ArrayObj) {
            $this->parameters = $parameters;
        }
    }

    public function build(): string
    {
        return $this->name . $this->parameters;
    }

    public function __toString(): string
    {
        return $this->build();
    }
}
