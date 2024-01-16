<?php

declare(strict_types=1);

namespace PhpMyAdmin\SqlParser;

use ArrayAccess;
use Exception;
use Stringable;

use function mb_check_encoding;
use function mb_strlen;
use function mb_substr;
use function ord;
use function strlen;
use function substr;

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
     * The raw, multi-byte string.
     */
    public string $str = '';

    /**
     * The index of current byte.
     *
     * For ASCII strings, the byte index is equal to the character index.
     */
    public int $byteIdx = 0;

    /**
     * The index of current character.
     *
     * For non-ASCII strings, some characters occupy more than one byte and
     * the character index will have a lower value than the byte index.
     */
    public int $charIdx = 0;

    /**
     * The length of the string (in bytes).
     */
    public int $byteLen = 0;

    /**
     * The length of the string (in characters).
     */
    public int $charLen = 0;

    /** @param string $str the string */
    public function __construct(string $str)
    {
        $this->str = $str;
        $this->byteLen = mb_strlen($str, '8bit');
        if (! mb_check_encoding($str, 'UTF-8')) {
            $this->charLen = 0;
        } else {
            $this->charLen = mb_strlen($str, 'UTF-8');
        }
    }

    /**
     * Checks if the given offset exists.
     *
     * @param int $offset the offset to be checked
     */
    public function offsetExists(mixed $offset): bool
    {
        return ($offset >= 0) && ($offset < $this->charLen);
    }

    /**
     * Gets the character at given offset.
     *
     * @param int $offset the offset to be returned
     */
    public function offsetGet(mixed $offset): string|null
    {
        // This function moves the internal byte and character pointer to the requested offset.
        // This function is part of hot code so the aim is to do the following
        // operations as efficiently as possible.
        // UTF-8 character encoding is a variable length encoding that encodes Unicode
        // characters in 1-4 bytes. Thus we fetch 4 bytes from the current offset and then use mb_substr
        // to get the first UTF-8 character in it. We then use strlen to get the character's size in bytes.
        if (($offset < 0) || ($offset >= $this->charLen)) {
            return null;
        }

        $delta = $offset - $this->charIdx;

        if ($delta > 0) {
            // Fast forwarding.
            $this->byteIdx += strlen(mb_substr(substr($this->str, $this->byteIdx, 4 * $delta), 0, $delta));
            $this->charIdx += $delta;
        } elseif ($delta < 0) {
            // Rewinding.
            while ($delta++ < 0) {
                // We rewind byte by byte and only count characters that are not continuation bytes,
                // i.e. ASCII characters and first octets of multibyte characters
                do {
                    $byte = ord($this->str[--$this->byteIdx]);
                } while (($byte >= 128) && ($byte < 192));

                --$this->charIdx;
            }
        }

        // Fetch the first Unicode character within the next 4 bytes in the string.
        return mb_substr(substr($this->str, $this->byteIdx, 4), 0, 1);
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
        return $this->charLen;
    }

    /**
     * Returns the contained string.
     */
    public function __toString(): string
    {
        return $this->str;
    }
}
