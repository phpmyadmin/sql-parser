<?php
/**
 * Defines a context class that is later extended to define other contexts.
 *
 * A context is a collection of keywords, operators and functions used for
 * parsing.
 */

declare(strict_types=1);

namespace PhpMyAdmin\SqlParser;

use PhpMyAdmin\SqlParser\Exceptions\LoaderException;

use function class_exists;
use function constant;
use function explode;
use function in_array;
use function intval;
use function is_array;
use function is_numeric;
use function str_replace;
use function str_starts_with;
use function strlen;
use function strtoupper;
use function substr;

/**
 * Holds the configuration of the context that is currently used.
 */
abstract class Context
{
    /**
     * The maximum length of a keyword.
     */
    public const KEYWORD_MAX_LENGTH = 30;

    /**
     * The maximum length of a label.
     *
     * Ref: https://dev.mysql.com/doc/refman/5.7/en/statement-labels.html
     */
    public const LABEL_MAX_LENGTH = 16;

    /**
     * The maximum length of an operator.
     */
    public const OPERATOR_MAX_LENGTH = 4;

    /**
     * The name of the default content.
     *
     * @var string
     */
    public static $defaultContext = '\\PhpMyAdmin\\SqlParser\\Contexts\\ContextMySql50700';

    /**
     * The name of the loaded context.
     *
     * @var string
     */
    public static $loadedContext = '\\PhpMyAdmin\\SqlParser\\Contexts\\ContextMySql50700';

    /**
     * The prefix concatenated to the context name when an incomplete class name
     * is specified.
     *
     * @var string
     */
    public static $contextPrefix = '\\PhpMyAdmin\\SqlParser\\Contexts\\Context';

    /**
     * List of keywords.
     *
     * Because, PHP's associative arrays are basically hash tables, it is more
     * efficient to store keywords as keys instead of values.
     *
     * The value associated to each keyword represents its flags.
     *
     * @see Token::FLAG_KEYWORD_RESERVED Token::FLAG_KEYWORD_COMPOSED
     *      Token::FLAG_KEYWORD_DATA_TYPE Token::FLAG_KEYWORD_KEY
     *      Token::FLAG_KEYWORD_FUNCTION
     *
     * Elements are sorted by flags, length and keyword.
     *
     * @var array<string,int>
     * @phpstan-var non-empty-array<non-empty-string,Token::FLAG_KEYWORD_*|int>
     */
    public static $keywords = [];

    /**
     * List of operators and their flags.
     *
     * @var array<string, int>
     */
    public static $operators = [
        // Some operators (*, =) may have ambiguous flags, because they depend on
        // the context they are being used in.
        // For example: 1. SELECT * FROM table; # SQL specific (wildcard)
        //                 SELECT 2 * 3;        # arithmetic
        //              2. SELECT * FROM table WHERE foo = 'bar';
        //                 SET @i = 0;

        // @see Token::FLAG_OPERATOR_ARITHMETIC
        '%' => 1,
        '*' => 1,
        '+' => 1,
        '-' => 1,
        '/' => 1,

        // @see Token::FLAG_OPERATOR_LOGICAL
        '!' => 2,
        '!=' => 2,
        '&&' => 2,
        '<' => 2,
        '<=' => 2,
        '<=>' => 2,
        '<>' => 2,
        '=' => 2,
        '>' => 2,
        '>=' => 2,
        '||' => 2,

        // @see Token::FLAG_OPERATOR_BITWISE
        '&' => 4,
        '<<' => 4,
        '>>' => 4,
        '^' => 4,
        '|' => 4,
        '~' => 4,

        // @see Token::FLAG_OPERATOR_ASSIGNMENT
        ':=' => 8,

        // @see Token::FLAG_OPERATOR_SQL
        '(' => 16,
        ')' => 16,
        '.' => 16,
        ',' => 16,
        ';' => 16,
    ];

    /**
     * The mode of the MySQL server that will be used in lexing, parsing and
     * building the statements.
     *
     * @var int
     */
    public static $mode = 0;

    /*
     * Server SQL Modes
     * https://dev.mysql.com/doc/refman/5.0/en/sql-mode.html
     */

    // Compatibility mode for Microsoft's SQL server.
    // This is the equivalent of ANSI_QUOTES.
    public const SQL_MODE_COMPAT_MYSQL = 2;

    // https://dev.mysql.com/doc/refman/5.0/en/sql-mode.html#sqlmode_allow_invalid_dates
    public const SQL_MODE_ALLOW_INVALID_DATES = 1;

    // https://dev.mysql.com/doc/refman/5.0/en/sql-mode.html#sqlmode_ansi_quotes
    public const SQL_MODE_ANSI_QUOTES = 2;

    // https://dev.mysql.com/doc/refman/5.0/en/sql-mode.html#sqlmode_error_for_division_by_zero
    public const SQL_MODE_ERROR_FOR_DIVISION_BY_ZERO = 4;

    // https://dev.mysql.com/doc/refman/5.0/en/sql-mode.html#sqlmode_high_not_precedence
    public const SQL_MODE_HIGH_NOT_PRECEDENCE = 8;

    // https://dev.mysql.com/doc/refman/5.0/en/sql-mode.html#sqlmode_ignore_space
    public const SQL_MODE_IGNORE_SPACE = 16;

    // https://dev.mysql.com/doc/refman/5.0/en/sql-mode.html#sqlmode_no_auto_create_user
    public const SQL_MODE_NO_AUTO_CREATE_USER = 32;

    // https://dev.mysql.com/doc/refman/5.0/en/sql-mode.html#sqlmode_no_auto_value_on_zero
    public const SQL_MODE_NO_AUTO_VALUE_ON_ZERO = 64;

    // https://dev.mysql.com/doc/refman/5.0/en/sql-mode.html#sqlmode_no_backslash_escapes
    public const SQL_MODE_NO_BACKSLASH_ESCAPES = 128;

    // https://dev.mysql.com/doc/refman/5.0/en/sql-mode.html#sqlmode_no_dir_in_create
    public const SQL_MODE_NO_DIR_IN_CREATE = 256;

    // https://dev.mysql.com/doc/refman/5.0/en/sql-mode.html#sqlmode_no_dir_in_create
    public const SQL_MODE_NO_ENGINE_SUBSTITUTION = 512;

    // https://dev.mysql.com/doc/refman/5.0/en/sql-mode.html#sqlmode_no_field_options
    public const SQL_MODE_NO_FIELD_OPTIONS = 1024;

    // https://dev.mysql.com/doc/refman/5.0/en/sql-mode.html#sqlmode_no_key_options
    public const SQL_MODE_NO_KEY_OPTIONS = 2048;

    // https://dev.mysql.com/doc/refman/5.0/en/sql-mode.html#sqlmode_no_table_options
    public const SQL_MODE_NO_TABLE_OPTIONS = 4096;

    // https://dev.mysql.com/doc/refman/5.0/en/sql-mode.html#sqlmode_no_unsigned_subtraction
    public const SQL_MODE_NO_UNSIGNED_SUBTRACTION = 8192;

    // https://dev.mysql.com/doc/refman/5.0/en/sql-mode.html#sqlmode_no_zero_date
    public const SQL_MODE_NO_ZERO_DATE = 16384;

    // https://dev.mysql.com/doc/refman/5.0/en/sql-mode.html#sqlmode_no_zero_in_date
    public const SQL_MODE_NO_ZERO_IN_DATE = 32768;

    // https://dev.mysql.com/doc/refman/5.0/en/sql-mode.html#sqlmode_only_full_group_by
    public const SQL_MODE_ONLY_FULL_GROUP_BY = 65536;

    // https://dev.mysql.com/doc/refman/5.0/en/sql-mode.html#sqlmode_pipes_as_concat
    public const SQL_MODE_PIPES_AS_CONCAT = 131072;

    // https://dev.mysql.com/doc/refman/5.0/en/sql-mode.html#sqlmode_real_as_float
    public const SQL_MODE_REAL_AS_FLOAT = 262144;

    // https://dev.mysql.com/doc/refman/5.0/en/sql-mode.html#sqlmode_strict_all_tables
    public const SQL_MODE_STRICT_ALL_TABLES = 524288;

    // https://dev.mysql.com/doc/refman/5.0/en/sql-mode.html#sqlmode_strict_trans_tables
    public const SQL_MODE_STRICT_TRANS_TABLES = 1048576;

    // Custom modes.

    // The table and column names and any other field that must be escaped will
    // not be.
    // Reserved keywords are being escaped regardless this mode is used or not.
    public const SQL_MODE_NO_ENCLOSING_QUOTES = 1073741824;

    /*
     * Combination SQL Modes
     * https://dev.mysql.com/doc/refman/5.0/en/sql-mode.html#sql-mode-combo
     */

    // REAL_AS_FLOAT, PIPES_AS_CONCAT, ANSI_QUOTES, IGNORE_SPACE
    public const SQL_MODE_ANSI = 393234;

    // PIPES_AS_CONCAT, ANSI_QUOTES, IGNORE_SPACE, NO_KEY_OPTIONS,
    // NO_TABLE_OPTIONS, NO_FIELD_OPTIONS,
    public const SQL_MODE_DB2 = 138258;

    // PIPES_AS_CONCAT, ANSI_QUOTES, IGNORE_SPACE, NO_KEY_OPTIONS,
    // NO_TABLE_OPTIONS, NO_FIELD_OPTIONS, NO_AUTO_CREATE_USER
    public const SQL_MODE_MAXDB = 138290;

    // PIPES_AS_CONCAT, ANSI_QUOTES, IGNORE_SPACE, NO_KEY_OPTIONS,
    // NO_TABLE_OPTIONS, NO_FIELD_OPTIONS
    public const SQL_MODE_MSSQL = 138258;

    // PIPES_AS_CONCAT, ANSI_QUOTES, IGNORE_SPACE, NO_KEY_OPTIONS,
    // NO_TABLE_OPTIONS, NO_FIELD_OPTIONS, NO_AUTO_CREATE_USER
    public const SQL_MODE_ORACLE = 138290;

    // PIPES_AS_CONCAT, ANSI_QUOTES, IGNORE_SPACE, NO_KEY_OPTIONS,
    // NO_TABLE_OPTIONS, NO_FIELD_OPTIONS
    public const SQL_MODE_POSTGRESQL = 138258;

    // STRICT_TRANS_TABLES, STRICT_ALL_TABLES, NO_ZERO_IN_DATE, NO_ZERO_DATE,
    // ERROR_FOR_DIVISION_BY_ZERO, NO_AUTO_CREATE_USER
    public const SQL_MODE_TRADITIONAL = 1622052;

    /**
     * Checks if the given string is a keyword.
     *
     * @param bool $isReserved checks if the keyword is reserved
     */
    public static function isKeyword(string $string, bool $isReserved = false): ?int
    {
        $upperString = strtoupper($string);

        if (
            ! isset(static::$keywords[$upperString])
            || ($isReserved && ! (static::$keywords[$upperString] & Token::FLAG_KEYWORD_RESERVED))
        ) {
            return null;
        }

        return static::$keywords[$upperString];
    }

    /**
     * Checks if the given string is an operator and returns the appropriate flag for the operator.
     */
    public static function isOperator(string $string): ?int
    {
        return static::$operators[$string] ?? null;
    }

    /**
     * Checks if the given character is a whitespace.
     */
    public static function isWhitespace(string $string): bool
    {
        return $string === ' ' || $string === "\r" || $string === "\n" || $string === "\t";
    }

    /**
     * Checks if the given string is the beginning of a whitespace.
     *
     * @return int|null the appropriate flag for the comment type
     */
    public static function isComment(string $string, bool $end = false): ?int
    {
        if ($string === '') {
            return null;
        }

        // If comment is Bash style (#):
        if (str_starts_with($string, '#')) {
            return Token::FLAG_COMMENT_BASH;
        }

        // If comment is a MySQL command
        if (str_starts_with($string, '/*!')) {
            return Token::FLAG_COMMENT_MYSQL_CMD;
        }

        // If comment is opening C style (/*) or is closing C style (*/), warning, it could conflict
        // with wildcard and a real opening C style.
        // It would look like the following valid SQL statement: "SELECT */* comment */ FROM...".
        if (str_starts_with($string, '/*') || str_starts_with($string, '*/')) {
            return Token::FLAG_COMMENT_C;
        }

        // If comment is SQL style (--\s?):
        if (
            str_starts_with($string, '-- ')
            || str_starts_with($string, "--\r")
            || str_starts_with($string, "--\n")
            || str_starts_with($string, "--\t")
            || ($string === '--' && $end)
        ) {
            return Token::FLAG_COMMENT_SQL;
        }

        return null;
    }

    /**
     * Checks if the given string is a boolean value.
     * This actually check only for `TRUE` and `FALSE` because `1` or `0` are
     * actually numbers and are parsed by specific methods.
     */
    public static function isBool(string $string): bool
    {
        $upperString = strtoupper($string);

        return $upperString === 'TRUE' || $upperString === 'FALSE';
    }

    /**
     * Checks if the given character can be a part of a number.
     */
    public static function isNumber(string $string): bool
    {
        return in_array($string, ['0', '1', '2', '3', '4', '5', '6', '7', '8', '9', '.', '-', '+', 'e', 'E'], true);
    }

    /**
     * Checks if the given character is the beginning of a symbol. A symbol
     * can be either a variable or a field name.
     *
     * @return int|null the appropriate flag for the symbol type
     */
    public static function isSymbol(string $string): ?int
    {
        if ($string === '') {
            return null;
        }

        if (str_starts_with($string, '@')) {
            return Token::FLAG_SYMBOL_VARIABLE;
        }

        if (str_starts_with($string, '`')) {
            return Token::FLAG_SYMBOL_BACKTICK;
        }

        if (str_starts_with($string, ':') || str_starts_with($string, '?')) {
            return Token::FLAG_SYMBOL_PARAMETER;
        }

        return null;
    }

    /**
     * Checks if the given character is the beginning of a string.
     *
     * @param string $string string to be checked
     *
     * @return int|null the appropriate flag for the string type
     */
    public static function isString(string $string): ?int
    {
        if ($string === '') {
            return null;
        }

        if (str_starts_with($string, '\'')) {
            return Token::FLAG_STRING_SINGLE_QUOTES;
        }

        if (str_starts_with($string, '"')) {
            return Token::FLAG_STRING_DOUBLE_QUOTES;
        }

        return null;
    }

    /**
     * Checks if the given character can be a separator for two lexeme.
     *
     * @param string $string string to be checked
     */
    public static function isSeparator(string $string): bool
    {
        // NOTES:   Only non-alphanumeric ASCII characters may be separators.
        //          `~` is the last printable ASCII character.
        return $string <= '~'
            && $string !== '_'
            && $string !== '$'
            && ($string < '0' || $string > '9')
            && ($string < 'a' || $string > 'z')
            && ($string < 'A' || $string > 'Z');
    }

    /**
     * Loads the specified context.
     *
     * Contexts may be used by accessing the context directly.
     *
     * @param string $context name of the context or full class name that defines the context
     *
     * @throws LoaderException if the specified context doesn't exist.
     */
    public static function load(string $context = ''): void
    {
        if (empty($context)) {
            $context = self::$defaultContext;
        }

        if ($context[0] !== '\\') {
            // Short context name (must be formatted into class name).
            $context = self::$contextPrefix . $context;
        }

        if (! class_exists($context)) {
            throw @new LoaderException('Specified context ("' . $context . '") does not exist.', $context);
        }

        self::$loadedContext = $context;
        self::$keywords = $context::$keywords;
    }

    /**
     * Loads the context with the closest version to the one specified.
     *
     * The closest context is found by replacing last digits with zero until one
     * is loaded successfully.
     *
     * @see Context::load()
     *
     * @param string $context name of the context or full class name that defines the context
     *
     * @return string|null The loaded context. `null` if no context was loaded.
     */
    public static function loadClosest(string $context = ''): ?string
    {
        $length = strlen($context);
        for ($i = $length; $i > 0;) {
            try {
                /* Trying to load the new context */
                static::load($context);

                return $context;
            } catch (LoaderException $e) {
                /* Replace last two non zero digits by zeroes */
                do {
                    $i -= 2;
                    $part = substr($context, $i, 2);
                    /* No more numeric parts to strip */
                    if (! is_numeric($part)) {
                        break 2;
                    }
                } while (intval($part) === 0 && $i > 0);

                $context = substr($context, 0, $i) . '00' . substr($context, $i + 2);
            }
        }

        /* Fallback to loading at least matching engine */
        if (str_starts_with($context, 'MariaDb')) {
            return static::loadClosest('MariaDb100300');
        }

        if (str_starts_with($context, 'MySql')) {
            return static::loadClosest('MySql50700');
        }

        return null;
    }

    /**
     * Sets the SQL mode.
     *
     * @param string $mode The list of modes. If empty, the mode is reset.
     */
    public static function setMode(string $mode = ''): void
    {
        static::$mode = 0;
        if (empty($mode)) {
            return;
        }

        $mode = explode(',', $mode);
        foreach ($mode as $m) {
            static::$mode |= constant('static::SQL_MODE_' . $m);
        }
    }

    /**
     * Escapes the symbol by adding surrounding backticks.
     *
     * @param string[]|string $str   the string to be escaped
     * @param string          $quote quote to be used when escaping
     *
     * @return string|string[]
     */
    public static function escape($str, string $quote = '`')
    {
        if (is_array($str)) {
            foreach ($str as $key => $value) {
                $str[$key] = static::escape($value);
            }

            return $str;
        }

        if ((static::$mode & self::SQL_MODE_NO_ENCLOSING_QUOTES) && (! static::isKeyword($str, true))) {
            return $str;
        }

        if (static::$mode & self::SQL_MODE_ANSI_QUOTES) {
            $quote = '"';
        }

        return $quote . str_replace($quote, $quote . $quote, $str) . $quote;
    }

    /**
     * Returns char used to quote identifiers based on currently set SQL Mode (ie. standard or ANSI_QUOTES)
     *
     * @return string either " (double quote, ansi_quotes mode) or ` (backtick, standard mode)
     */
    public static function getIdentifierQuote(): string
    {
        return self::hasMode(self::SQL_MODE_ANSI_QUOTES) ? '"' : '`';
    }

    /**
     * Function verifies that given SQL Mode constant is currently set
     *
     * @param int|null $flag for example Context::SQL_MODE_ANSI_QUOTES
     *
     * @return bool false on empty param, true/false on given constant/int value
     */
    public static function hasMode(?int $flag = null): bool
    {
        if (empty($flag)) {
            return false;
        }

        return (self::$mode & $flag) === $flag;
    }
}
