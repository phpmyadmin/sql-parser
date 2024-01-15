<?php

declare(strict_types=1);

namespace PhpMyAdmin\SqlParser;

use PhpMyAdmin\SqlParser\Contexts\ContextMySql50700;

use function class_exists;
use function explode;
use function in_array;
use function intval;
use function is_int;
use function is_numeric;
use function preg_match;
use function str_replace;
use function str_starts_with;
use function strlen;
use function strtoupper;
use function substr;

/**
 * Defines a context class that is later extended to define other contexts.
 *
 * A context is a collection of keywords, operators and functions used for parsing.
 *
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
     * The name of the loaded context.
     */
    public static string $loadedContext = ContextMySql50700::class;

    /**
     * The prefix concatenated to the context name when an incomplete class name
     * is specified.
     */
    public static string $contextPrefix = 'PhpMyAdmin\\SqlParser\\Contexts\\Context';

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
     * @psalm-var non-empty-array<string,Token::FLAG_KEYWORD_*|int>
     * @phpstan-var non-empty-array<non-empty-string,Token::FLAG_KEYWORD_*|int>
     */
    public static array $keywords = [];

    /**
     * List of operators and their flags.
     *
     * @var array<string, int>
     */
    public static array $operators = [
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
     * The mode of the MySQL server that will be used in lexing, parsing and building the statements.
     *
     * @internal use the {@see Context::getMode()} method instead.
     *
     * @link https://dev.mysql.com/doc/refman/en/sql-mode.html
     * @link https://mariadb.com/kb/en/sql-mode/
     */
    public static int $mode = self::SQL_MODE_NONE;

    public const SQL_MODE_NONE = 0;

    /**
     * @link https://dev.mysql.com/doc/refman/en/sql-mode.html#sqlmode_allow_invalid_dates
     * @link https://mariadb.com/kb/en/sql-mode/#allow_invalid_dates
     */
    public const SQL_MODE_ALLOW_INVALID_DATES = 1;

    /**
     * @link https://dev.mysql.com/doc/refman/en/sql-mode.html#sqlmode_ansi_quotes
     * @link https://mariadb.com/kb/en/sql-mode/#ansi_quotes
     */
    public const SQL_MODE_ANSI_QUOTES = 2;

    /** Compatibility mode for Microsoft's SQL server. This is the equivalent of {@see SQL_MODE_ANSI_QUOTES}. */
    public const SQL_MODE_COMPAT_MYSQL = 2;

    /**
     * @link https://dev.mysql.com/doc/refman/en/sql-mode.html#sqlmode_error_for_division_by_zero
     * @link https://mariadb.com/kb/en/sql-mode/#error_for_division_by_zero
     */
    public const SQL_MODE_ERROR_FOR_DIVISION_BY_ZERO = 4;

    /**
     * @link https://dev.mysql.com/doc/refman/en/sql-mode.html#sqlmode_high_not_precedence
     * @link https://mariadb.com/kb/en/sql-mode/#high_not_precedence
     */
    public const SQL_MODE_HIGH_NOT_PRECEDENCE = 8;

    /**
     * @link https://dev.mysql.com/doc/refman/en/sql-mode.html#sqlmode_ignore_space
     * @link https://mariadb.com/kb/en/sql-mode/#ignore_space
     */
    public const SQL_MODE_IGNORE_SPACE = 16;

    /**
     * @link https://dev.mysql.com/doc/refman/5.7/en/sql-mode.html#sqlmode_no_auto_create_user
     * @link https://mariadb.com/kb/en/sql-mode/#no_auto_create_user
     */
    public const SQL_MODE_NO_AUTO_CREATE_USER = 32;

    /**
     * @link https://dev.mysql.com/doc/refman/en/sql-mode.html#sqlmode_no_auto_value_on_zero
     * @link https://mariadb.com/kb/en/sql-mode/#no_auto_value_on_zero
     */
    public const SQL_MODE_NO_AUTO_VALUE_ON_ZERO = 64;

    /**
     * @link https://dev.mysql.com/doc/refman/en/sql-mode.html#sqlmode_no_backslash_escapes
     * @link https://mariadb.com/kb/en/sql-mode/#no_backslash_escapes
     */
    public const SQL_MODE_NO_BACKSLASH_ESCAPES = 128;

    /**
     * @link https://dev.mysql.com/doc/refman/en/sql-mode.html#sqlmode_no_dir_in_create
     * @link https://mariadb.com/kb/en/sql-mode/#no_dir_in_create
     */
    public const SQL_MODE_NO_DIR_IN_CREATE = 256;

    /**
     * @link https://dev.mysql.com/doc/refman/en/sql-mode.html#sqlmode_no_engine_substitution
     * @link https://mariadb.com/kb/en/sql-mode/#no_engine_substitution
     */
    public const SQL_MODE_NO_ENGINE_SUBSTITUTION = 512;

    /**
     * @link https://dev.mysql.com/doc/refman/5.7/en/sql-mode.html#sqlmode_no_field_options
     * @link https://mariadb.com/kb/en/sql-mode/#no_field_options
     */
    public const SQL_MODE_NO_FIELD_OPTIONS = 1024;

    /**
     * @link https://dev.mysql.com/doc/refman/5.7/en/sql-mode.html#sqlmode_no_key_options
     * @link https://mariadb.com/kb/en/sql-mode/#no_key_options
     */
    public const SQL_MODE_NO_KEY_OPTIONS = 2048;

    /**
     * @link https://dev.mysql.com/doc/refman/5.7/en/sql-mode.html#sqlmode_no_table_options
     * @link https://mariadb.com/kb/en/sql-mode/#no_table_options
     */
    public const SQL_MODE_NO_TABLE_OPTIONS = 4096;

    /**
     * @link https://dev.mysql.com/doc/refman/en/sql-mode.html#sqlmode_no_unsigned_subtraction
     * @link https://mariadb.com/kb/en/sql-mode/#no_unsigned_subtraction
     */
    public const SQL_MODE_NO_UNSIGNED_SUBTRACTION = 8192;

    /**
     * @link https://dev.mysql.com/doc/refman/en/sql-mode.html#sqlmode_no_zero_date
     * @link https://mariadb.com/kb/en/sql-mode/#no_zero_date
     */
    public const SQL_MODE_NO_ZERO_DATE = 16384;

    /**
     * @link https://dev.mysql.com/doc/refman/en/sql-mode.html#sqlmode_no_zero_in_date
     * @link https://mariadb.com/kb/en/sql-mode/#no_zero_in_date
     */
    public const SQL_MODE_NO_ZERO_IN_DATE = 32768;

    /**
     * @link https://dev.mysql.com/doc/refman/en/sql-mode.html#sqlmode_only_full_group_by
     * @link https://mariadb.com/kb/en/sql-mode/#only_full_group_by
     */
    public const SQL_MODE_ONLY_FULL_GROUP_BY = 65536;

    /**
     * @link https://dev.mysql.com/doc/refman/en/sql-mode.html#sqlmode_pipes_as_concat
     * @link https://mariadb.com/kb/en/sql-mode/#pipes_as_concat
     */
    public const SQL_MODE_PIPES_AS_CONCAT = 131072;

    /**
     * @link https://dev.mysql.com/doc/refman/en/sql-mode.html#sqlmode_real_as_float
     * @link https://mariadb.com/kb/en/sql-mode/#real_as_float
     */
    public const SQL_MODE_REAL_AS_FLOAT = 262144;

    /**
     * @link https://dev.mysql.com/doc/refman/en/sql-mode.html#sqlmode_strict_all_tables
     * @link https://mariadb.com/kb/en/sql-mode/#strict_all_tables
     */
    public const SQL_MODE_STRICT_ALL_TABLES = 524288;

    /**
     * @link https://dev.mysql.com/doc/refman/en/sql-mode.html#sqlmode_strict_trans_tables
     * @link https://mariadb.com/kb/en/sql-mode/#strict_trans_tables
     */
    public const SQL_MODE_STRICT_TRANS_TABLES = 1048576;

    /**
     * Custom mode.
     * The table and column names and any other field that must be escaped will not be.
     * Reserved keywords are being escaped regardless this mode is used or not.
     */
    public const SQL_MODE_NO_ENCLOSING_QUOTES = 1073741824;

    /**
     * Equivalent to {@see SQL_MODE_REAL_AS_FLOAT}, {@see SQL_MODE_PIPES_AS_CONCAT}, {@see SQL_MODE_ANSI_QUOTES},
     * {@see SQL_MODE_IGNORE_SPACE}.
     *
     * @link https://dev.mysql.com/doc/refman/5.7/en/sql-mode.html#sqlmode_ansi
     * @link https://mariadb.com/kb/en/sql-mode/#ansi
     */
    public const SQL_MODE_ANSI = 393234;

    /**
     * Equivalent to {@see SQL_MODE_PIPES_AS_CONCAT}, {@see SQL_MODE_ANSI_QUOTES}, {@see SQL_MODE_IGNORE_SPACE},
     * {@see SQL_MODE_NO_KEY_OPTIONS}, {@see SQL_MODE_NO_TABLE_OPTIONS}, {@see SQL_MODE_NO_FIELD_OPTIONS}.
     *
     * @link https://dev.mysql.com/doc/refman/5.7/en/sql-mode.html#sqlmode_db2
     * @link https://mariadb.com/kb/en/sql-mode/#db2
     */
    public const SQL_MODE_DB2 = 138258;

    /**
     * Equivalent to {@see SQL_MODE_PIPES_AS_CONCAT}, {@see SQL_MODE_ANSI_QUOTES}, {@see SQL_MODE_IGNORE_SPACE},
     * {@see SQL_MODE_NO_KEY_OPTIONS}, {@see SQL_MODE_NO_TABLE_OPTIONS}, {@see SQL_MODE_NO_FIELD_OPTIONS},
     * {@see SQL_MODE_NO_AUTO_CREATE_USER}.
     *
     * @link https://dev.mysql.com/doc/refman/5.7/en/sql-mode.html#sqlmode_maxdb
     * @link https://mariadb.com/kb/en/sql-mode/#maxdb
     */
    public const SQL_MODE_MAXDB = 138290;

    /**
     * Equivalent to {@see SQL_MODE_PIPES_AS_CONCAT}, {@see SQL_MODE_ANSI_QUOTES}, {@see SQL_MODE_IGNORE_SPACE},
     * {@see SQL_MODE_NO_KEY_OPTIONS}, {@see SQL_MODE_NO_TABLE_OPTIONS}, {@see SQL_MODE_NO_FIELD_OPTIONS}.
     *
     * @link https://dev.mysql.com/doc/refman/5.7/en/sql-mode.html#sqlmode_mssql
     * @link https://mariadb.com/kb/en/sql-mode/#mssql
     */
    public const SQL_MODE_MSSQL = 138258;

    /**
     * Equivalent to {@see SQL_MODE_PIPES_AS_CONCAT}, {@see SQL_MODE_ANSI_QUOTES}, {@see SQL_MODE_IGNORE_SPACE},
     * {@see SQL_MODE_NO_KEY_OPTIONS}, {@see SQL_MODE_NO_TABLE_OPTIONS}, {@see SQL_MODE_NO_FIELD_OPTIONS},
     * {@see SQL_MODE_NO_AUTO_CREATE_USER}.
     *
     * @link https://dev.mysql.com/doc/refman/5.7/en/sql-mode.html#sqlmode_oracle
     * @link https://mariadb.com/kb/en/sql-mode/#oracle
     */
    public const SQL_MODE_ORACLE = 138290;

    /**
     * Equivalent to {@see SQL_MODE_PIPES_AS_CONCAT}, {@see SQL_MODE_ANSI_QUOTES}, {@see SQL_MODE_IGNORE_SPACE},
     * {@see SQL_MODE_NO_KEY_OPTIONS}, {@see SQL_MODE_NO_TABLE_OPTIONS}, {@see SQL_MODE_NO_FIELD_OPTIONS}.
     *
     * @link https://dev.mysql.com/doc/refman/5.7/en/sql-mode.html#sqlmode_postgresql
     * @link https://mariadb.com/kb/en/sql-mode/#postgresql
     */
    public const SQL_MODE_POSTGRESQL = 138258;

    /**
     * Equivalent to {@see SQL_MODE_STRICT_TRANS_TABLES}, {@see SQL_MODE_STRICT_ALL_TABLES},
     * {@see SQL_MODE_NO_ZERO_IN_DATE}, {@see SQL_MODE_NO_ZERO_DATE}, {@see SQL_MODE_ERROR_FOR_DIVISION_BY_ZERO},
     * {@see SQL_MODE_NO_AUTO_CREATE_USER}.
     *
     * @link https://dev.mysql.com/doc/refman/en/sql-mode.html#sqlmode_traditional
     * @link https://mariadb.com/kb/en/sql-mode/#traditional
     */
    public const SQL_MODE_TRADITIONAL = 1622052;

    /**
     * Checks if the given string is a keyword.
     *
     * @param bool $isReserved checks if the keyword is reserved
     */
    public static function isKeyword(string $string, bool $isReserved = false): int|null
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
    public static function isOperator(string $string): int|null
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
    public static function isComment(string $string, bool $end = false): int|null
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
    public static function isSymbol(string $string): int|null
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
    public static function isString(string $string): int|null
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
     * @return bool true if the context was loaded, false otherwise
     */
    public static function load(string $context = ''): bool
    {
        if ($context === '') {
            $context = ContextMySql50700::class;
        }

        if (! class_exists($context)) {
            if (! class_exists(self::$contextPrefix . $context)) {
                return false;
            }

            // Short context name (must be formatted into class name).
            $context = self::$contextPrefix . $context;
        }

        self::$loadedContext = $context;
        self::$keywords = $context::$keywords;

        return true;
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
    public static function loadClosest(string $context = ''): string|null
    {
        $length = strlen($context);
        for ($i = $length; $i > 0;) {
            /* Trying to load the new context */
            if (static::load($context)) {
                return $context;
            }

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
     * Gets the SQL mode.
     */
    public static function getMode(): int
    {
        return static::$mode;
    }

    /**
     * Sets the SQL mode.
     */
    public static function setMode(int|string $mode = self::SQL_MODE_NONE): void
    {
        if (is_int($mode)) {
            static::$mode = $mode;

            return;
        }

        static::$mode = self::SQL_MODE_NONE;
        if ($mode === '') {
            return;
        }

        $modes = explode(',', $mode);
        foreach ($modes as $sqlMode) {
            static::$mode |= self::getModeFromString($sqlMode);
        }
    }

    /** @psalm-suppress MixedReturnStatement, MixedInferredReturnType Is caused by the LSB of the constants */
    private static function getModeFromString(string $mode): int
    {
        return match ($mode) {
            'ALLOW_INVALID_DATES' => self::SQL_MODE_ALLOW_INVALID_DATES,
            'ANSI_QUOTES' => self::SQL_MODE_ANSI_QUOTES,
            'COMPAT_MYSQL' => self::SQL_MODE_COMPAT_MYSQL,
            'ERROR_FOR_DIVISION_BY_ZERO' => self::SQL_MODE_ERROR_FOR_DIVISION_BY_ZERO,
            'HIGH_NOT_PRECEDENCE' => self::SQL_MODE_HIGH_NOT_PRECEDENCE,
            'IGNORE_SPACE' => self::SQL_MODE_IGNORE_SPACE,
            'NO_AUTO_CREATE_USER' => self::SQL_MODE_NO_AUTO_CREATE_USER,
            'NO_AUTO_VALUE_ON_ZERO' => self::SQL_MODE_NO_AUTO_VALUE_ON_ZERO,
            'NO_BACKSLASH_ESCAPES' => self::SQL_MODE_NO_BACKSLASH_ESCAPES,
            'NO_DIR_IN_CREATE' => self::SQL_MODE_NO_DIR_IN_CREATE,
            'NO_ENGINE_SUBSTITUTION' => self::SQL_MODE_NO_ENGINE_SUBSTITUTION,
            'NO_FIELD_OPTIONS' => self::SQL_MODE_NO_FIELD_OPTIONS,
            'NO_KEY_OPTIONS' => self::SQL_MODE_NO_KEY_OPTIONS,
            'NO_TABLE_OPTIONS' => self::SQL_MODE_NO_TABLE_OPTIONS,
            'NO_UNSIGNED_SUBTRACTION' => self::SQL_MODE_NO_UNSIGNED_SUBTRACTION,
            'NO_ZERO_DATE' => self::SQL_MODE_NO_ZERO_DATE,
            'NO_ZERO_IN_DATE' => self::SQL_MODE_NO_ZERO_IN_DATE,
            'ONLY_FULL_GROUP_BY' => self::SQL_MODE_ONLY_FULL_GROUP_BY,
            'PIPES_AS_CONCAT' => self::SQL_MODE_PIPES_AS_CONCAT,
            'REAL_AS_FLOAT' => self::SQL_MODE_REAL_AS_FLOAT,
            'STRICT_ALL_TABLES' => self::SQL_MODE_STRICT_ALL_TABLES,
            'STRICT_TRANS_TABLES' => self::SQL_MODE_STRICT_TRANS_TABLES,
            'NO_ENCLOSING_QUOTES' => self::SQL_MODE_NO_ENCLOSING_QUOTES,
            'ANSI' => self::SQL_MODE_ANSI,
            'DB2' => self::SQL_MODE_DB2,
            'MAXDB' => self::SQL_MODE_MAXDB,
            'MSSQL' => self::SQL_MODE_MSSQL,
            'ORACLE' => self::SQL_MODE_ORACLE,
            'POSTGRESQL' => self::SQL_MODE_POSTGRESQL,
            'TRADITIONAL' => self::SQL_MODE_TRADITIONAL,
            default => self::SQL_MODE_NONE,
        };
    }

    /**
     * Escapes the symbol by adding surrounding backticks.
     *
     * @param string $str   the string to be escaped
     * @param string $quote quote to be used when escaping
     */
    public static function escape(string $str, string $quote = '`'): string
    {
        if (
            (static::$mode & self::SQL_MODE_NO_ENCLOSING_QUOTES) && ! (
                static::isKeyword($str, true) || self::doesIdentifierRequireQuoting($str)
            )
        ) {
            return $str;
        }

        if (static::$mode & self::SQL_MODE_ANSI_QUOTES) {
            $quote = '"';
        }

        return $quote . str_replace($quote, $quote . $quote, $str) . $quote;
    }

    /**
     * Escapes the symbol by adding surrounding backticks.
     *
     * @param string[] $strings the string to be escaped
     *
     * @return string[]
     */
    public static function escapeAll(array $strings): array
    {
        foreach ($strings as $key => $value) {
            $strings[$key] = static::escape($value);
        }

        return $strings;
    }

    /**
     * Function verifies that given SQL Mode constant is currently set
     *
     * @param int $flag for example {@see Context::SQL_MODE_ANSI_QUOTES}
     *
     * @return bool false on empty param, true/false on given constant/int value
     */
    public static function hasMode(int|null $flag = null): bool
    {
        if (empty($flag)) {
            return false;
        }

        return (self::$mode & $flag) === $flag;
    }

    private static function doesIdentifierRequireQuoting(string $identifier): bool
    {
        return preg_match('/^[$]|^\d+$|[^0-9a-zA-Z$_\x80-\xffff]/', $identifier) === 1;
    }
}
