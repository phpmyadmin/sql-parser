<?php

declare(strict_types=1);

namespace PhpMyAdmin\SqlParser;

use function hexdec;
use function mb_strlen;
use function mb_substr;
use function str_replace;
use function stripcslashes;
use function strtoupper;

/**
 * Defines a token along with a set of types and flags and utility functions.
 *
 * An array of tokens will result after parsing the query.
 *
 * A structure representing a lexeme that explicitly indicates its categorization for the purpose of parsing.
 */
class Token
{
    public const FLAG_NONE = 0;

    // Flags that describe the tokens in more detail.
    // All keywords must have flag 1 so `Context::isKeyword` method doesn't
    // require strict comparison.
    public const FLAG_KEYWORD_RESERVED = 2;
    public const FLAG_KEYWORD_COMPOSED = 4;
    public const FLAG_KEYWORD_DATA_TYPE = 8;
    public const FLAG_KEYWORD_KEY = 16;
    public const FLAG_KEYWORD_FUNCTION = 32;

    // Numbers related flags.
    public const FLAG_NUMBER_HEX = 1;
    public const FLAG_NUMBER_FLOAT = 2;
    public const FLAG_NUMBER_APPROXIMATE = 4;
    public const FLAG_NUMBER_NEGATIVE = 8;
    public const FLAG_NUMBER_BINARY = 16;

    // Strings related flags.
    public const FLAG_STRING_SINGLE_QUOTES = 1;
    public const FLAG_STRING_DOUBLE_QUOTES = 2;

    // Comments related flags.
    public const FLAG_COMMENT_BASH = 1;
    public const FLAG_COMMENT_C = 2;
    public const FLAG_COMMENT_SQL = 4;
    public const FLAG_COMMENT_MYSQL_CMD = 8;

    // Operators related flags.
    public const FLAG_OPERATOR_ARITHMETIC = 1;
    public const FLAG_OPERATOR_LOGICAL = 2;
    public const FLAG_OPERATOR_BITWISE = 4;
    public const FLAG_OPERATOR_ASSIGNMENT = 8;
    public const FLAG_OPERATOR_SQL = 16;

    // Symbols related flags.
    public const FLAG_SYMBOL_VARIABLE = 1;
    public const FLAG_SYMBOL_BACKTICK = 2;
    public const FLAG_SYMBOL_USER = 4;
    public const FLAG_SYMBOL_SYSTEM = 8;
    public const FLAG_SYMBOL_PARAMETER = 16;

    /**
     * The token it its raw string representation.
     */
    public string $token;

    /**
     * The value this token contains (i.e. token after some evaluation).
     */
    public bool|float|int|string $value;

    /**
     * The keyword value this token contains, always uppercase.
     */
    public string|null $keyword = null;

    /**
     * The type of this token.
     */
    public TokenType $type;

    /**
     * The flags of this token.
     */
    public int $flags;

    /**
     * The position in the initial string where this token started.
     *
     * The position is counted in chars, not bytes, so you should
     * use mb_* functions to properly handle utf-8 multibyte chars.
     */
    public int|null $position = null;

    /**
     * @param string    $token the value of the token
     * @param TokenType $type  the type of the token
     * @param int       $flags the flags of the token
     */
    public function __construct(string $token, TokenType $type = TokenType::None, int $flags = self::FLAG_NONE)
    {
        $this->token = $token;
        $this->type = $type;
        $this->flags = $flags;
        $this->value = $this->extract();
    }

    /**
     * Does a little processing to the token to extract a value.
     *
     * If no processing can be done it will return the initial string.
     */
    public function extract(): bool|float|int|string
    {
        switch ($this->type) {
            case TokenType::Keyword:
                $this->keyword = strtoupper($this->token);
                if (! ($this->flags & self::FLAG_KEYWORD_RESERVED)) {
                    // Unreserved keywords should stay the way they are because they
                    // might represent field names.
                    return $this->token;
                }

                return $this->keyword;

            case TokenType::Whitespace:
                return ' ';

            case TokenType::Bool:
                return strtoupper($this->token) === 'TRUE';

            case TokenType::Number:
                $ret = str_replace('--', '', $this->token); // e.g. ---42 === -42
                if ($this->flags & self::FLAG_NUMBER_HEX) {
                    $ret = str_replace(['-', '+'], '', $this->token);
                    if ($this->flags & self::FLAG_NUMBER_NEGATIVE) {
                        $ret = -hexdec($ret);
                    } else {
                        $ret = hexdec($ret);
                    }
                } elseif (($this->flags & self::FLAG_NUMBER_APPROXIMATE) || ($this->flags & self::FLAG_NUMBER_FLOAT)) {
                    $ret = (float) $ret;
                } elseif (! ($this->flags & self::FLAG_NUMBER_BINARY)) {
                    $ret = (int) $ret;
                }

                return $ret;

            case TokenType::String:
                // Trims quotes.
                $str = $this->token;
                $str = mb_substr($str, 1, -1, 'UTF-8');

                // Removes surrounding quotes.
                $quote = $this->token[0];
                $str = str_replace($quote . $quote, $quote, $str);

                // Finally unescapes the string.
                //
                // `stripcslashes` replaces escape sequences with their
                // representation.
                //
                // NOTE: In MySQL, `\f` and `\v` have no representation,
                // even they usually represent: form-feed and vertical tab.
                $str = str_replace('\f', 'f', $str);
                $str = str_replace('\v', 'v', $str);
                $str = stripcslashes($str);

                return $str;

            case TokenType::Symbol:
                $str = $this->token;
                if (isset($str[0]) && ($str[0] === '@')) {
                    // `mb_strlen($str)` must be used instead of `null` because
                    // in PHP 5.3- the `null` parameter isn't handled correctly.
                    $str = mb_substr(
                        $str,
                        ! empty($str[1]) && ($str[1] === '@') ? 2 : 1,
                        mb_strlen($str),
                        'UTF-8',
                    );
                }

                if (isset($str[0]) && ($str[0] === ':')) {
                    $str = mb_substr($str, 1, mb_strlen($str), 'UTF-8');
                }

                if (isset($str[0]) && (($str[0] === '`') || ($str[0] === '"') || ($str[0] === '\''))) {
                    $quote = $str[0];
                    $str = mb_substr($str, 1, -1, 'UTF-8');
                    $str = str_replace($quote . $quote, $quote, $str);
                }

                return $str;

            default:
                return $this->token;
        }
    }

    /**
     * Converts the token into an inline token by replacing tabs and new lines.
     */
    public function getInlineToken(): string
    {
        return str_replace(
            [
                "\r",
                "\n",
                "\t",
            ],
            [
                '\r',
                '\n',
                '\t',
            ],
            $this->token,
        );
    }
}
