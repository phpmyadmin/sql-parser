<?php

declare(strict_types=1);

namespace PhpMyAdmin\SqlParser;

use ArrayAccess;
use Exception;
use Stringable;

use function count;
use function implode;
use function mb_check_encoding;
use function mb_str_split;

/**
 * Implementation for UTF-8 strings.
 *
 * The subscript operator in PHP, when used with string will return a byte and not a character. Because in UTF-8
 * strings a character may occupy more than one byte, the subscript operator may return an invalid character.
 *
 * Because the lexer relies on the subscript operator this class had to be implemented.
 *
 * Implements array-like access for UTF-8 strings.
 *
 * In this library, this class should be used to parse UTF-8 queries.
 *
 * @implements ArrayAccess<int, string>
 */
class UtfString implements ArrayAccess, Stringable
{
    /**
     * The multi-byte characters.
     *
     * @var list<string>
     */
    public array $characters;

    /** @param string $str the string */
    public function __construct(string $str)
    {
        if (mb_check_encoding($str, 'UTF-8')) {
            $this->characters = mb_str_split($str, 1, 'UTF-8');
        } else {
            $this->characters = [];
        }
    }

    /**
     * Checks if the given offset exists.
     *
     * @param int $offset the offset to be checked
     */
    public function offsetExists(mixed $offset): bool
    {
        return $offset >= 0 && $offset < count($this->characters);
    }

    /**
     * Gets the character at given offset.
     *
     * @param int $offset the offset to be returned
     */
    public function offsetGet(mixed $offset): string|null
    {
        return $this->characters[$offset] ?? null;
    }

    /**
     * Sets the value of a character.
     *
     * @param int    $offset the offset to be set
     * @param string $value  the value to be set
     *
     * @throws Exception not implemented.
     */
    public function offsetSet(mixed $offset, mixed $value): void
    {
        throw new Exception('Not implemented.');
    }

    /**
     * Unsets an index.
     *
     * @param int $offset the value to be unset
     *
     * @throws Exception not implemented.
     */
    public function offsetUnset(mixed $offset): void
    {
        throw new Exception('Not implemented.');
    }

    /**
     * Returns the length in characters of the string.
     */
    public function length(): int
    {
        return count($this->characters);
    }

    /**
     * Returns the contained string.
     */
    public function __toString(): string
    {
        return implode('', $this->characters);
    }
}
