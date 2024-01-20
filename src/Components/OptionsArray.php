<?php

declare(strict_types=1);

namespace PhpMyAdmin\SqlParser\Components;

use PhpMyAdmin\SqlParser\Component;

use function array_merge_recursive;
use function implode;
use function is_array;
use function strcasecmp;

/**
 * Parses a list of options.
 */
final class OptionsArray implements Component
{
    /**
     * @param array<int, mixed> $options The array of options. Options that have a value
     *                       must be an array with at least two keys `name` and
     *                       `expr` or `value`.
     */
    public function __construct(public array $options = [])
    {
    }

    public function build(): string
    {
        if (empty($this->options)) {
            return '';
        }

        $options = [];
        foreach ($this->options as $option) {
            if (! is_array($option)) {
                $options[] = $option;
            } else {
                $options[] = $option['name']
                    . (! empty($option['equals']) ? '=' : ' ')
                    . (! empty($option['expr']) ? $option['expr'] : $option['value']);
            }
        }

        return implode(' ', $options);
    }

    /**
     * Checks if it has the specified option and returns it value or true.
     *
     * @param string $key     the key to be checked
     * @param bool   $getExpr Gets the expression instead of the value.
     *                        The value is the processed form of the expression.
     */
    public function has(string $key, bool $getExpr = false): mixed
    {
        foreach ($this->options as $option) {
            if (is_array($option)) {
                if (! strcasecmp($key, $option['name'])) {
                    return $getExpr ? $option['expr'] : $option['value'];
                }
            } elseif (! strcasecmp($key, $option)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Removes the option from the array.
     *
     * @param string $key the key to be removed
     *
     * @return bool whether the key was found and deleted or not
     */
    public function remove(string $key): bool
    {
        foreach ($this->options as $idx => $option) {
            if (is_array($option)) {
                if (! strcasecmp($key, $option['name'])) {
                    unset($this->options[$idx]);

                    return true;
                }
            } elseif (! strcasecmp($key, $option)) {
                unset($this->options[$idx]);

                return true;
            }
        }

        return false;
    }

    /**
     * Merges the specified options with these ones. Values with same ID will be
     * replaced.
     */
    public function merge(OptionsArray $options): void
    {
        $this->options = array_merge_recursive($this->options, $options->options);
    }

    /**
     * Checks tf there are no options set.
     */
    public function isEmpty(): bool
    {
        return empty($this->options);
    }

    public function __toString(): string
    {
        return $this->build();
    }
}
