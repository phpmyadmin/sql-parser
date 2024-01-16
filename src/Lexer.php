<?php

declare(strict_types=1);

namespace PhpMyAdmin\SqlParser;

use Exception;
use PhpMyAdmin\SqlParser\Exceptions\LexerException;

use function in_array;
use function mb_strlen;
use function sprintf;
use function str_ends_with;
use function strlen;
use function substr;

/**
 * Defines the lexer of the library.
 *
 * This is one of the most important components, along with the parser.
 *
 * Depends on context to extract lexemes.
 *
 * Performs lexical analysis over a SQL statement and splits it in multiple tokens.
 *
 * The output of the lexer is affected by the context of the SQL statement.
 *
 * @see Context
 */
class Lexer
{
    /**
     * Whether errors should throw exceptions or just be stored.
     */
    private bool $strict = false;

    /**
     * List of errors that occurred during lexing.
     *
     * Usually, the lexing does not stop once an error occurred because that
     * error might be false positive or a partial result (even a bad one)
     * might be needed.
     *
     * @var Exception[]
     */
    public array $errors = [];

    /**
     * A list of keywords that indicate that the function keyword
     * is not used as a function
     */
    private const KEYWORD_NAME_INDICATORS = [
        'FROM',
        'SET',
        'WHERE',
    ];

    /**
     * A list of operators that indicate that the function keyword
     * is not used as a function
     */
    private const OPERATOR_NAME_INDICATORS = [
        ',',
        '.',
    ];

    /**
     * The string to be parsed.
     */
    public string|UtfString $str = '';

    /**
     * The length of `$str`.
     *
     * By storing its length, a lot of time is saved, because parsing methods
     * would call `strlen` everytime.
     */
    public int $len = 0;

    /**
     * The index of the last parsed character.
     */
    public int $last = 0;

    /**
     * Tokens extracted from given strings.
     */
    public TokensList $list;

    /**
     * The default delimiter. This is used, by default, in all new instances.
     */
    public static string $defaultDelimiter = ';';

    /**
     * Statements delimiter.
     * This may change during lexing.
     */
    public string $delimiter;

    /**
     * The length of the delimiter.
     *
     * Because `parseDelimiter` can be called a lot, it would perform a lot of
     * calls to `strlen`, which might affect performance when the delimiter is
     * big.
     */
    public int $delimiterLen;

    /**
     * @param string|UtfString $str       the query to be lexed
     * @param bool             $strict    whether strict mode should be
     *                                    enabled or not
     * @param string           $delimiter the delimiter to be used
     */
    public function __construct(string|UtfString $str, bool $strict = false, string|null $delimiter = null)
    {
        if (Context::$keywords === []) {
            Context::load();
        }

        // `strlen` is used instead of `mb_strlen` because the lexer needs to
        // parse each byte of the input.
        $len = $str instanceof UtfString ? $str->length() : strlen($str);

        // For multi-byte strings, a new instance of `UtfString` is initialized.
        if (! $str instanceof UtfString && $len !== mb_strlen($str, 'UTF-8')) {
            $str = new UtfString($str);
        }

        $this->str = $str;
        $this->len = $str instanceof UtfString ? $str->length() : $len;

        $this->strict = $strict;

        // Setting the delimiter.
        $this->setDelimiter(! empty($delimiter) ? $delimiter : static::$defaultDelimiter);

        $this->lex();
    }

    /**
     * Sets the delimiter.
     *
     * @param string $delimiter the new delimiter
     */
    public function setDelimiter(string $delimiter): void
    {
        $this->delimiter = $delimiter;
        $this->delimiterLen = strlen($delimiter);
    }

    /**
     * Parses the string and extracts lexemes.
     */
    public function lex(): void
    {
        // TODO: Sometimes, static::parse* functions make unnecessary calls to
        // is* functions. For a better performance, some rules can be deduced
        // from context.
        // For example, in `parseBool` there is no need to compare the token
        // every time with `true` and `false`. The first step would be to
        // compare with 'true' only and just after that add another letter from
        // context and compare again with `false`.
        // Another example is `parseComment`.

        $list = new TokensList();

        /**
         * Last processed token.
         */
        $lastToken = null;

        for ($this->last = 0, $lastIdx = 0; $this->last < $this->len; $lastIdx = ++$this->last) {
            $token = $this->parse();

            if ($token === null) {
                // @assert($this->last === $lastIdx);
                $token = new Token($this->str[$this->last]);
                $this->error('Unexpected character.', $this->str[$this->last], $this->last);
            } elseif (
                $lastToken !== null
                && $token->type === TokenType::Symbol
                && $token->flags & Token::FLAG_SYMBOL_VARIABLE
                && (
                    $lastToken->type === TokenType::String
                    || (
                        $lastToken->type === TokenType::Symbol
                        && $lastToken->flags & Token::FLAG_SYMBOL_BACKTICK
                    )
                )
            ) {
                // Handles ```... FROM 'user'@'%' ...```.
                $lastToken->token .= $token->token;
                $lastToken->type = TokenType::Symbol;
                $lastToken->flags = Token::FLAG_SYMBOL_USER;
                $lastToken->value .= '@' . $token->value;
                continue;
            } elseif (
                $lastToken !== null
                && $token->type === TokenType::Keyword
                && $lastToken->type === TokenType::Operator
                && $lastToken->value === '.'
            ) {
                // Handles ```... tbl.FROM ...```. In this case, FROM is not
                // a reserved word.
                $token->type = TokenType::None;
                $token->flags = 0;
                $token->value = $token->token;
            }

            $token->position = $lastIdx;

            $list->tokens[$list->count++] = $token;

            // Handling delimiters.
            if ($token->type === TokenType::None && $token->value === 'DELIMITER') {
                if ($this->last + 1 >= $this->len) {
                    $this->error('Expected whitespace(s) before delimiter.', '', $this->last + 1);
                    continue;
                }

                // Skipping last R (from `delimiteR`) and whitespaces between
                // the keyword `DELIMITER` and the actual delimiter.
                $pos = ++$this->last;
                $token = $this->parseWhitespace();

                if ($token !== null) {
                    $token->position = $pos;
                    $list->tokens[$list->count++] = $token;
                }

                // Preparing the token that holds the new delimiter.
                if ($this->last + 1 >= $this->len) {
                    $this->error('Expected delimiter.', '', $this->last + 1);
                    continue;
                }

                $pos = $this->last + 1;

                // Parsing the delimiter.
                $this->delimiter = '';
                $delimiterLen = 0;
                while (
                    ++$this->last < $this->len
                    && ! Context::isWhitespace($this->str[$this->last])
                    && $delimiterLen < 15
                ) {
                    $this->delimiter .= $this->str[$this->last];
                    ++$delimiterLen;
                }

                if ($this->delimiter === '') {
                    $this->error('Expected delimiter.', '', $this->last);
                    $this->delimiter = ';';
                }

                --$this->last;

                // Saving the delimiter and its token.
                $this->delimiterLen = strlen($this->delimiter);
                $token = new Token($this->delimiter, TokenType::Delimiter);
                $token->position = $pos;
                $list->tokens[$list->count++] = $token;
            }

            $lastToken = $token;
        }

        // Adding a final delimiter to mark the ending.
        $list->tokens[$list->count++] = new Token('', TokenType::Delimiter);

        // Saving the tokens list.
        $this->list = $list;

        $this->solveAmbiguityOnStarOperator();
        $this->solveAmbiguityOnFunctionKeywords();
    }

    /**
     * Resolves the ambiguity when dealing with the "*" operator.
     *
     * In SQL statements, the "*" operator can be an arithmetic operator (like in 2*3) or an SQL wildcard (like in
     * SELECT a.* FROM ...). To solve this ambiguity, the solution is to find the next token, excluding whitespaces and
     * comments, right after the "*" position. The "*" is for sure an SQL wildcard if the next token found is any of:
     * - "FROM" (the FROM keyword like in "SELECT * FROM...");
     * - "USING" (the USING keyword like in "DELETE table_name.* USING...");
     * - "," (a comma separator like in "SELECT *, field FROM...");
     * - ")" (a closing parenthesis like in "COUNT(*)").
     * This methods will change the flag of the "*" tokens when any of those condition above is true. Otherwise, the
     * default flag (arithmetic) will be kept.
     */
    private function solveAmbiguityOnStarOperator(): void
    {
        $iBak = $this->list->idx;
        while (($starToken = $this->list->getNextOfTypeAndValue(TokenType::Operator, '*')) !== null) {
            // getNext() already gets rid of whitespaces and comments.
            $next = $this->list->getNext();

            if ($next === null) {
                continue;
            }

            if (
                ($next->type !== TokenType::Keyword || ! in_array($next->value, ['FROM', 'USING'], true))
                && ($next->type !== TokenType::Operator || ! in_array($next->value, [',', ')'], true))
            ) {
                continue;
            }

            $starToken->flags = Token::FLAG_OPERATOR_SQL;
        }

        $this->list->idx = $iBak;
    }

    /**
     * Resolves the ambiguity when dealing with the functions keywords.
     *
     * In SQL statements, the function keywords might be used as table names or columns names.
     * To solve this ambiguity, the solution is to find the next token, excluding whitespaces and
     * comments, right after the function keyword position. The function keyword is for sure used
     * as column name or table name if the next token found is any of:
     *
     * - "FROM" (the FROM keyword like in "SELECT Country x, AverageSalary avg FROM...");
     * - "WHERE" (the WHERE keyword like in "DELETE FROM emp x WHERE x.salary = 20");
     * - "SET" (the SET keyword like in "UPDATE Country x, City y set x.Name=x.Name");
     * - "," (a comma separator like 'x,' in "UPDATE Country x, City y set x.Name=x.Name");
     * - "." (a dot separator like in "x.asset_id FROM (SELECT evt.asset_id FROM evt)".
     * - "NULL" (when used as a table alias like in "avg.col FROM (SELECT ev.col FROM ev) avg").
     *
     * This method will change the flag of the function keyword tokens when any of those
     * condition above is true. Otherwise, the
     * default flag (function keyword) will be kept.
     */
    private function solveAmbiguityOnFunctionKeywords(): void
    {
        $iBak = $this->list->idx;
        $keywordFunction = TokenType::Keyword->value | Token::FLAG_KEYWORD_FUNCTION;
        while (($keywordToken = $this->list->getNextOfTypeAndFlag(TokenType::Keyword, $keywordFunction)) !== null) {
            $next = $this->list->getNext();
            if (
                ($next->type !== TokenType::Keyword
                    || ! in_array($next->value, self::KEYWORD_NAME_INDICATORS, true)
                )
                && ($next->type !== TokenType::Operator
                    || ! in_array($next->value, self::OPERATOR_NAME_INDICATORS, true)
                )
                && ($next->value !== '')
            ) {
                continue;
            }

            $keywordToken->type = TokenType::None;
            $keywordToken->flags = Token::FLAG_NONE;
            $keywordToken->keyword = $keywordToken->value;
        }

        $this->list->idx = $iBak;
    }

    /**
     * Creates a new error log.
     *
     * @param string $msg  the error message
     * @param string $str  the character that produced the error
     * @param int    $pos  the position of the character
     * @param int    $code the code of the error
     *
     * @throws LexerException throws the exception, if strict mode is enabled.
     */
    public function error(string $msg, string $str = '', int $pos = 0, int $code = 0): void
    {
        $error = new LexerException(
            Translator::gettext($msg),
            $str,
            $pos,
            $code,
        );

        if ($this->strict) {
            throw $error;
        }

        $this->errors[] = $error;
    }

    /**
     * Parses a keyword.
     */
    public function parseKeyword(): Token|null
    {
        $token = '';

        /**
         * Value to be returned.
         */
        $ret = null;

        /**
         * The value of `$this->last` where `$token` ends in `$this->str`.
         */
        $iEnd = $this->last;

        /**
         * Whether last parsed character is a whitespace.
         */
        $lastSpace = false;

        for ($j = 1; $j < Context::KEYWORD_MAX_LENGTH && $this->last < $this->len; ++$j, ++$this->last) {
            // Composed keywords shouldn't have more than one whitespace between
            // keywords.
            if (Context::isWhitespace($this->str[$this->last])) {
                if ($lastSpace) {
                    --$j; // The size of the keyword didn't increase.
                    continue;
                }

                $lastSpace = true;
            } else {
                $lastSpace = false;
            }

            $token .= $this->str[$this->last];
            $flags = Context::isKeyword($token);

            if (($this->last + 1 !== $this->len && ! Context::isSeparator($this->str[$this->last + 1])) || ! $flags) {
                continue;
            }

            $ret = new Token($token, TokenType::Keyword, $flags);
            $iEnd = $this->last;

            // We don't break so we find longest keyword.
            // For example, `OR` and `ORDER` have a common prefix `OR`.
            // If we stopped at `OR`, the parsing would be invalid.
        }

        $this->last = $iEnd;

        return $ret;
    }

    /**
     * Parses a label.
     */
    public function parseLabel(): Token|null
    {
        $token = '';

        /**
         * Value to be returned.
         */
        $ret = null;

        /**
         * The value of `$this->last` where `$token` ends in `$this->str`.
         */
        $iEnd = $this->last;
        for ($j = 1; $j < Context::LABEL_MAX_LENGTH && $this->last < $this->len; ++$j, ++$this->last) {
            if ($this->str[$this->last] === ':' && $j > 1) {
                // End of label
                $token .= $this->str[$this->last];
                $ret = new Token($token, TokenType::Label);
                $iEnd = $this->last;
                break;
            }

            if (Context::isWhitespace($this->str[$this->last]) && $j > 1) {
                // Whitespace between label and :
                // The size of the keyword didn't increase.
                --$j;
            } elseif (Context::isSeparator($this->str[$this->last])) {
                // Any other separator
                break;
            }

            $token .= $this->str[$this->last];
        }

        $this->last = $iEnd;

        return $ret;
    }

    /**
     * Parses an operator.
     */
    public function parseOperator(): Token|null
    {
        $token = '';

        /**
         * Value to be returned.
         */
        $ret = null;

        /**
         * The value of `$this->last` where `$token` ends in `$this->str`.
         */
        $iEnd = $this->last;

        for ($j = 1; $j < Context::OPERATOR_MAX_LENGTH && $this->last < $this->len; ++$j, ++$this->last) {
            $token .= $this->str[$this->last];
            $flags = Context::isOperator($token);

            if (! $flags) {
                continue;
            }

            $ret = new Token($token, TokenType::Operator, $flags);
            $iEnd = $this->last;
        }

        $this->last = $iEnd;

        return $ret;
    }

    /**
     * Parses a whitespace.
     */
    public function parseWhitespace(): Token|null
    {
        $token = $this->str[$this->last];

        if (! Context::isWhitespace($token)) {
            return null;
        }

        while (++$this->last < $this->len && Context::isWhitespace($this->str[$this->last])) {
            $token .= $this->str[$this->last];
        }

        --$this->last;

        return new Token($token, TokenType::Whitespace);
    }

    /**
     * Parses a comment.
     */
    public function parseComment(): Token|null
    {
        $iBak = $this->last;
        $token = $this->str[$this->last];

        // Bash style comments. (#comment\n)
        if (Context::isComment($token)) {
            while (++$this->last < $this->len && $this->str[$this->last] !== "\n") {
                $token .= $this->str[$this->last];
            }

            // Include trailing \n as whitespace token
            if ($this->last < $this->len) {
                --$this->last;
            }

            return new Token($token, TokenType::Comment, Token::FLAG_COMMENT_BASH);
        }

        // C style comments. (/*comment*\/)
        if (++$this->last < $this->len) {
            $token .= $this->str[$this->last];
            if (Context::isComment($token)) {
                // There might be a conflict with "*" operator here, when string is "*/*".
                // This can occurs in the following statements:
                // - "SELECT */* comment */ FROM ..."
                // - "SELECT 2*/* comment */3 AS `six`;"
                $next = $this->last + 1;
                if (($next < $this->len) && $this->str[$next] === '*') {
                    // Conflict in "*/*": first "*" was not for ending a comment.
                    // Stop here and let other parsing method define the true behavior of that first star.
                    $this->last = $iBak;

                    return null;
                }

                $flags = Token::FLAG_COMMENT_C;

                // This comment already ended. It may be a part of a
                // previous MySQL specific command.
                if ($token === '*/') {
                    return new Token($token, TokenType::Comment, $flags);
                }

                // Checking if this is a MySQL-specific command.
                if ($this->last + 1 < $this->len && $this->str[$this->last + 1] === '!') {
                    $flags |= Token::FLAG_COMMENT_MYSQL_CMD;
                    $token .= $this->str[++$this->last];

                    while (
                        ++$this->last < $this->len
                        && $this->str[$this->last] >= '0'
                        && $this->str[$this->last] <= '9'
                    ) {
                        $token .= $this->str[$this->last];
                    }

                    --$this->last;

                    // We split this comment and parse only its beginning
                    // here.
                    return new Token($token, TokenType::Comment, $flags);
                }

                // Parsing the comment.
                while (
                    ++$this->last < $this->len
                    && (
                        $this->str[$this->last - 1] !== '*'
                        || $this->str[$this->last] !== '/'
                    )
                ) {
                    $token .= $this->str[$this->last];
                }

                // Adding the ending.
                if ($this->last < $this->len) {
                    $token .= $this->str[$this->last];
                }

                return new Token($token, TokenType::Comment, $flags);
            }
        }

        // SQL style comments. (-- comment\n)
        if (++$this->last < $this->len) {
            $token .= $this->str[$this->last];
            $end = false;
        } else {
            --$this->last;
            $end = true;
        }

        if (Context::isComment($token, $end)) {
            // Checking if this comment did not end already (```--\n```).
            if ($this->str[$this->last] !== "\n") {
                while (++$this->last < $this->len && $this->str[$this->last] !== "\n") {
                    $token .= $this->str[$this->last];
                }
            }

            // Include trailing \n as whitespace token
            if ($this->last < $this->len) {
                --$this->last;
            }

            return new Token($token, TokenType::Comment, Token::FLAG_COMMENT_SQL);
        }

        $this->last = $iBak;

        return null;
    }

    /**
     * Parses a boolean.
     */
    public function parseBool(): Token|null
    {
        if ($this->last + 3 >= $this->len) {
            // At least `min(strlen('TRUE'), strlen('FALSE'))` characters are
            // required.
            return null;
        }

        $iBak = $this->last;
        $token = $this->str[$this->last] . $this->str[++$this->last]
        . $this->str[++$this->last] . $this->str[++$this->last]; // _TRUE_ or _FALS_e

        if (Context::isBool($token)) {
            return new Token($token, TokenType::Bool);
        }

        if (++$this->last < $this->len) {
            $token .= $this->str[$this->last]; // fals_E_
            if (Context::isBool($token)) {
                return new Token($token, TokenType::Bool, 1);
            }
        }

        $this->last = $iBak;

        return null;
    }

    /**
     * Parses a number.
     */
    public function parseNumber(): Token|null
    {
        // A rudimentary state machine is being used to parse numbers due to
        // the various forms of their notation.
        //
        // Below are the states of the machines and the conditions to change
        // the state.
        //
        //      1 --------------------[ + or - ]-------------------> 1
        //      1 -------------------[ 0x or 0X ]------------------> 2
        //      1 --------------------[ 0 to 9 ]-------------------> 3
        //      1 -----------------------[ . ]---------------------> 4
        //      1 -----------------------[ b ]---------------------> 7
        //
        //      2 --------------------[ 0 to F ]-------------------> 2
        //
        //      3 --------------------[ 0 to 9 ]-------------------> 3
        //      3 -----------------------[ . ]---------------------> 4
        //      3 --------------------[ e or E ]-------------------> 5
        //
        //      4 --------------------[ 0 to 9 ]-------------------> 4
        //      4 --------------------[ e or E ]-------------------> 5
        //
        //      5 ---------------[ + or - or 0 to 9 ]--------------> 6
        //
        //      7 -----------------------[ ' ]---------------------> 8
        //
        //      8 --------------------[ 0 or 1 ]-------------------> 8
        //      8 -----------------------[ ' ]---------------------> 9
        //
        // State 1 may be reached by negative numbers.
        // State 2 is reached only by hex numbers.
        // State 4 is reached only by float numbers.
        // State 5 is reached only by numbers in approximate form.
        // State 7 is reached only by numbers in bit representation.
        //
        // Valid final states are: 2, 3, 4 and 6. Any parsing that finished in a
        // state other than these is invalid.
        // Also, negative states are invalid states.
        $iBak = $this->last;
        $token = '';
        $flags = 0;
        $state = 1;
        for (; $this->last < $this->len; ++$this->last) {
            if ($state === 1) {
                if ($this->str[$this->last] === '-') {
                    $flags |= Token::FLAG_NUMBER_NEGATIVE;
                } elseif (
                    $this->last + 1 < $this->len
                    && $this->str[$this->last] === '0'
                    && $this->str[$this->last + 1] === 'x'
                ) {
                    $token .= $this->str[$this->last++];
                    $state = 2;
                } elseif ($this->str[$this->last] >= '0' && $this->str[$this->last] <= '9') {
                    $state = 3;
                } elseif ($this->str[$this->last] === '.') {
                    $state = 4;
                } elseif ($this->str[$this->last] === 'b') {
                    $state = 7;
                } elseif ($this->str[$this->last] !== '+') {
                    // `+` is a valid character in a number.
                    break;
                }
            } elseif ($state === 2) {
                $flags |= Token::FLAG_NUMBER_HEX;
                if (
                    ! (
                        ($this->str[$this->last] >= '0' && $this->str[$this->last] <= '9')
                        || ($this->str[$this->last] >= 'A' && $this->str[$this->last] <= 'F')
                        || ($this->str[$this->last] >= 'a' && $this->str[$this->last] <= 'f')
                    )
                ) {
                    break;
                }
            } elseif ($state === 3) {
                if ($this->str[$this->last] === '.') {
                    $state = 4;
                } elseif ($this->str[$this->last] === 'e' || $this->str[$this->last] === 'E') {
                    $state = 5;
                } elseif (
                    ($this->str[$this->last] >= 'a' && $this->str[$this->last] <= 'z')
                    || ($this->str[$this->last] >= 'A' && $this->str[$this->last] <= 'Z')
                ) {
                    // A number can't be directly followed by a letter
                    $state = -$state;
                } elseif ($this->str[$this->last] < '0' || $this->str[$this->last] > '9') {
                    // Just digits and `.`, `e` and `E` are valid characters.
                    break;
                }
            } elseif ($state === 4) {
                $flags |= Token::FLAG_NUMBER_FLOAT;
                if ($this->str[$this->last] === 'e' || $this->str[$this->last] === 'E') {
                    $state = 5;
                } elseif (
                    ($this->str[$this->last] >= 'a' && $this->str[$this->last] <= 'z')
                    || ($this->str[$this->last] >= 'A' && $this->str[$this->last] <= 'Z')
                ) {
                    // A number can't be directly followed by a letter
                    $state = -$state;
                } elseif ($this->str[$this->last] < '0' || $this->str[$this->last] > '9') {
                    // Just digits, `e` and `E` are valid characters.
                    break;
                }
            } elseif ($state === 5) {
                $flags |= Token::FLAG_NUMBER_APPROXIMATE;
                if (
                    $this->str[$this->last] === '+' || $this->str[$this->last] === '-'
                    || ($this->str[$this->last] >= '0' && $this->str[$this->last] <= '9')
                ) {
                    $state = 6;
                } elseif (
                    ($this->str[$this->last] >= 'a' && $this->str[$this->last] <= 'z')
                    || ($this->str[$this->last] >= 'A' && $this->str[$this->last] <= 'Z')
                ) {
                    // A number can't be directly followed by a letter
                    $state = -$state;
                } else {
                    break;
                }
            } elseif ($state === 6) {
                if ($this->str[$this->last] < '0' || $this->str[$this->last] > '9') {
                    // Just digits are valid characters.
                    break;
                }
            } elseif ($state === 7) {
                $flags |= Token::FLAG_NUMBER_BINARY;
                if ($this->str[$this->last] !== '\'') {
                    break;
                }

                $state = 8;
            } elseif ($state === 8) {
                if ($this->str[$this->last] === '\'') {
                    $state = 9;
                } elseif ($this->str[$this->last] !== '0' && $this->str[$this->last] !== '1') {
                    break;
                }
            } elseif ($state === 9) {
                break;
            }

            $token .= $this->str[$this->last];
        }

        if ($state === 2 || $state === 3 || ($token !== '.' && $state === 4) || $state === 6 || $state === 9) {
            --$this->last;

            return new Token($token, TokenType::Number, $flags);
        }

        $this->last = $iBak;

        return null;
    }

    /**
     * Parses a string.
     *
     * @param string $quote additional starting symbol
     *
     * @throws LexerException
     */
    public function parseString(string $quote = ''): Token|null
    {
        $token = $this->str[$this->last];
        $flags = Context::isString($token);

        if (! $flags && $token !== $quote) {
            return null;
        }

        $quote = $token;

        while (++$this->last < $this->len) {
            if (
                $this->last + 1 < $this->len
                && (
                    ($this->str[$this->last] === $quote && $this->str[$this->last + 1] === $quote)
                    || ($this->str[$this->last] === '\\' && $quote !== '`')
                )
            ) {
                $token .= $this->str[$this->last] . $this->str[++$this->last];
            } else {
                if ($this->str[$this->last] === $quote) {
                    break;
                }

                $token .= $this->str[$this->last];
            }
        }

        if ($this->last >= $this->len || $this->str[$this->last] !== $quote) {
            $this->error(
                sprintf(
                    Translator::gettext('Ending quote %1$s was expected.'),
                    $quote,
                ),
                '',
                $this->last,
            );
        } else {
            $token .= $this->str[$this->last];
        }

        return new Token($token, TokenType::String, $flags ?? Token::FLAG_NONE);
    }

    /**
     * Parses a symbol.
     *
     * @throws LexerException
     */
    public function parseSymbol(): Token|null
    {
        $token = $this->str[$this->last];
        $flags = Context::isSymbol($token);

        if (! $flags) {
            return null;
        }

        if ($flags & Token::FLAG_SYMBOL_VARIABLE) {
            if ($this->last + 1 < $this->len && $this->str[++$this->last] === '@') {
                // This is a system variable (e.g. `@@hostname`).
                $token .= $this->str[$this->last++];
                $flags |= Token::FLAG_SYMBOL_SYSTEM;
            }
        } elseif ($flags & Token::FLAG_SYMBOL_PARAMETER) {
            if ($token !== '?' && $this->last + 1 < $this->len) {
                ++$this->last;
            }
        } else {
            $token = '';
        }

        $str = null;

        if ($this->last < $this->len) {
            $str = $this->parseString('`');

            if ($str === null) {
                $str = $this->parseUnknown();

                if ($str === null && ! ($flags & Token::FLAG_SYMBOL_PARAMETER)) {
                    $this->error('Variable name was expected.', $this->str[$this->last], $this->last);
                }
            }
        }

        if ($str !== null) {
            $token .= $str->token;
        }

        return new Token($token, TokenType::Symbol, $flags);
    }

    /**
     * Parses unknown parts of the query.
     */
    public function parseUnknown(): Token|null
    {
        $token = $this->str[$this->last];
        if (Context::isSeparator($token)) {
            return null;
        }

        while (++$this->last < $this->len && ! Context::isSeparator($this->str[$this->last])) {
            $token .= $this->str[$this->last];

            // Test if end of token equals the current delimiter. If so, remove it from the token.
            if (str_ends_with($token, $this->delimiter)) {
                $token = substr($token, 0, -$this->delimiterLen);
                $this->last -= $this->delimiterLen - 1;
                break;
            }
        }

        --$this->last;

        return new Token($token);
    }

    /**
     * Parses the delimiter of the query.
     */
    public function parseDelimiter(): Token|null
    {
        $idx = 0;

        while ($idx < $this->delimiterLen && $this->last + $idx < $this->len) {
            if ($this->delimiter[$idx] !== $this->str[$this->last + $idx]) {
                return null;
            }

            ++$idx;
        }

        $this->last += $this->delimiterLen - 1;

        return new Token($this->delimiter, TokenType::Delimiter);
    }

    private function parse(): Token|null
    {
        // It is best to put the parsers in order of their complexity
        // (ascending) and their occurrence rate (descending).
        //
        // Conflicts:
        //
        // 1. `parseDelimiter`, `parseUnknown`, `parseKeyword`, `parseNumber`
        // They fight over delimiter. The delimiter may be a keyword, a
        // number or almost any character which makes the delimiter one of
        // the first tokens that must be parsed.
        //
        // 1. `parseNumber` and `parseOperator`
        // They fight over `+` and `-`.
        //
        // 2. `parseComment` and `parseOperator`
        // They fight over `/` (as in ```/*comment*/``` or ```a / b```)
        //
        // 3. `parseBool` and `parseKeyword`
        // They fight over `TRUE` and `FALSE`.
        //
        // 4. `parseKeyword` and `parseUnknown`
        // They fight over words. `parseUnknown` does not know about
        // keywords.

        return $this->parseDelimiter()
            ?? $this->parseWhitespace()
            ?? $this->parseNumber()
            ?? $this->parseComment()
            ?? $this->parseOperator()
            ?? $this->parseBool()
            ?? $this->parseString()
            ?? $this->parseSymbol()
            ?? $this->parseKeyword()
            ?? $this->parseLabel()
            ?? $this->parseUnknown();
    }
}
