<?php

declare(strict_types=1);

namespace PhpMyAdmin\SqlParser;

use ArrayAccess;

use function count;
use function in_array;
use function is_array;
use function is_string;

/**
 * Defines an array of tokens and utility functions to iterate through it.
 *
 * A structure representing a list of tokens.
 *
 * @implements ArrayAccess<int, Token>
 */
class TokensList implements ArrayAccess
{
    /**
     * The count of tokens.
     *
     * @var int
     */
    public $count = 0;

    /**
     * The index of the next token to be returned.
     *
     * @var int
     */
    public $idx = 0;

    /**
     * @param Token[] $tokens The array of tokens.
     */
    public function __construct(public array $tokens = [])
    {
        $this->count = count($tokens);
    }

    /**
     * Builds an array of tokens by merging their raw value.
     *
     * @param string|Token[]|TokensList $list the tokens to be built
     */
    public static function build($list): string
    {
        if (is_string($list)) {
            return $list;
        }

        if ($list instanceof self) {
            $list = $list->tokens;
        }

        $ret = '';
        if (is_array($list)) {
            foreach ($list as $tok) {
                $ret .= $tok->token;
            }
        }

        return $ret;
    }

    /**
     * Adds a new token.
     *
     * @param Token $token token to be added in list
     */
    public function add(Token $token): void
    {
        $this->tokens[$this->count++] = $token;
    }

    /**
     * Gets the next token. Skips any irrelevant token (whitespaces and
     * comments).
     */
    public function getNext(): Token|null
    {
        for (; $this->idx < $this->count; ++$this->idx) {
            if (
                ($this->tokens[$this->idx]->type !== Token::TYPE_WHITESPACE)
                && ($this->tokens[$this->idx]->type !== Token::TYPE_COMMENT)
            ) {
                return $this->tokens[$this->idx++];
            }
        }

        return null;
    }

    /**
     * Gets the previous token. Skips any irrelevant token (whitespaces and
     * comments).
     */
    public function getPrevious(): Token|null
    {
        for (; $this->idx >= 0; --$this->idx) {
            if (
                ($this->tokens[$this->idx]->type !== Token::TYPE_WHITESPACE)
                && ($this->tokens[$this->idx]->type !== Token::TYPE_COMMENT)
            ) {
                return $this->tokens[$this->idx--];
            }
        }

        return null;
    }

    /**
     * Gets the previous token.
     *
     * @param int|int[] $type the type
     */
    public function getPreviousOfType($type): Token|null
    {
        if (! is_array($type)) {
            $type = [$type];
        }

        for (; $this->idx >= 0; --$this->idx) {
            if (in_array($this->tokens[$this->idx]->type, $type, true)) {
                return $this->tokens[$this->idx--];
            }
        }

        return null;
    }

    /**
     * Gets the next token.
     *
     * @param int|int[] $type the type
     */
    public function getNextOfType($type): Token|null
    {
        if (! is_array($type)) {
            $type = [$type];
        }

        for (; $this->idx < $this->count; ++$this->idx) {
            if (in_array($this->tokens[$this->idx]->type, $type, true)) {
                return $this->tokens[$this->idx++];
            }
        }

        return null;
    }

    /**
     * Gets the next token.
     *
     * @param int    $type  the type of the token
     * @param string $value the value of the token
     */
    public function getNextOfTypeAndValue($type, $value): Token|null
    {
        for (; $this->idx < $this->count; ++$this->idx) {
            if (($this->tokens[$this->idx]->type === $type) && ($this->tokens[$this->idx]->value === $value)) {
                return $this->tokens[$this->idx++];
            }
        }

        return null;
    }

    /**
     * Gets the next token.
     *
     * @param int $type the type of the token
     * @param int $flag the flag of the token
     */
    public function getNextOfTypeAndFlag(int $type, int $flag): Token|null
    {
        for (; $this->idx < $this->count; ++$this->idx) {
            if (($this->tokens[$this->idx]->type === $type) && ($this->tokens[$this->idx]->flags === $flag)) {
                return $this->tokens[$this->idx++];
            }
        }

        return null;
    }

    /**
     * Sets an value inside the container.
     *
     * @param int|null $offset the offset to be set
     * @param Token    $value  the token to be saved
     */
    public function offsetSet($offset, $value): void
    {
        if ($offset === null) {
            $this->tokens[$this->count++] = $value;
        } else {
            $this->tokens[$offset] = $value;
        }
    }

    /**
     * Gets a value from the container.
     *
     * @param int $offset the offset to be returned
     */
    public function offsetGet($offset): Token|null
    {
        return $offset < $this->count ? $this->tokens[$offset] : null;
    }

    /**
     * Checks if an offset was previously set.
     *
     * @param int $offset the offset to be checked
     */
    public function offsetExists($offset): bool
    {
        return $offset < $this->count;
    }

    /**
     * Unsets the value of an offset.
     *
     * @param int $offset the offset to be unset
     */
    public function offsetUnset($offset): void
    {
        unset($this->tokens[$offset]);
        --$this->count;
        for ($i = $offset; $i < $this->count; ++$i) {
            $this->tokens[$i] = $this->tokens[$i + 1];
        }

        unset($this->tokens[$this->count]);
    }
}
