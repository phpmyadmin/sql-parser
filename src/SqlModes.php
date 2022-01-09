<?php

declare(strict_types=1);

namespace PhpMyAdmin\SqlParser;

/**
 * Server SQL Modes
 *
 * @link https://dev.mysql.com/doc/refman/en/sql-mode.html
 * @link https://mariadb.com/kb/en/sql-mode/
 */
final class SqlModes
{
    public const NONE = 0;

    /**
     * @link https://dev.mysql.com/doc/refman/en/sql-mode.html#sqlmode_allow_invalid_dates
     * @link https://mariadb.com/kb/en/sql-mode/#allow_invalid_dates
     */
    public const ALLOW_INVALID_DATES = 1;

    /**
     * @link https://dev.mysql.com/doc/refman/en/sql-mode.html#sqlmode_ansi_quotes
     * @link https://mariadb.com/kb/en/sql-mode/#ansi_quotes
     */
    public const ANSI_QUOTES = 2;

    /** Compatibility mode for Microsoft's SQL server. This is the equivalent of {@see ANSI_QUOTES}. */
    public const COMPAT_MYSQL = 2;

    /**
     * @link https://dev.mysql.com/doc/refman/en/sql-mode.html#sqlmode_error_for_division_by_zero
     * @link https://mariadb.com/kb/en/sql-mode/#error_for_division_by_zero
     */
    public const ERROR_FOR_DIVISION_BY_ZERO = 4;

    /**
     * @link https://dev.mysql.com/doc/refman/en/sql-mode.html#sqlmode_high_not_precedence
     * @link https://mariadb.com/kb/en/sql-mode/#high_not_precedence
     */
    public const HIGH_NOT_PRECEDENCE = 8;

    /**
     * @link https://dev.mysql.com/doc/refman/en/sql-mode.html#sqlmode_ignore_space
     * @link https://mariadb.com/kb/en/sql-mode/#ignore_space
     */
    public const IGNORE_SPACE = 16;

    /**
     * @link https://dev.mysql.com/doc/refman/5.7/en/sql-mode.html#sqlmode_no_auto_create_user
     * @link https://mariadb.com/kb/en/sql-mode/#no_auto_create_user
     */
    public const NO_AUTO_CREATE_USER = 32;

    /**
     * @link https://dev.mysql.com/doc/refman/en/sql-mode.html#sqlmode_no_auto_value_on_zero
     * @link https://mariadb.com/kb/en/sql-mode/#no_auto_value_on_zero
     */
    public const NO_AUTO_VALUE_ON_ZERO = 64;

    /**
     * @link https://dev.mysql.com/doc/refman/en/sql-mode.html#sqlmode_no_backslash_escapes
     * @link https://mariadb.com/kb/en/sql-mode/#no_backslash_escapes
     */
    public const NO_BACKSLASH_ESCAPES = 128;

    /**
     * @link https://dev.mysql.com/doc/refman/en/sql-mode.html#sqlmode_no_dir_in_create
     * @link https://mariadb.com/kb/en/sql-mode/#no_dir_in_create
     */
    public const NO_DIR_IN_CREATE = 256;

    /**
     * @link https://dev.mysql.com/doc/refman/en/sql-mode.html#sqlmode_no_engine_substitution
     * @link https://mariadb.com/kb/en/sql-mode/#no_engine_substitution
     */
    public const NO_ENGINE_SUBSTITUTION = 512;

    /**
     * @link https://dev.mysql.com/doc/refman/5.7/en/sql-mode.html#sqlmode_no_field_options
     * @link https://mariadb.com/kb/en/sql-mode/#no_field_options
     */
    public const NO_FIELD_OPTIONS = 1024;

    /**
     * @link https://dev.mysql.com/doc/refman/5.7/en/sql-mode.html#sqlmode_no_key_options
     * @link https://mariadb.com/kb/en/sql-mode/#no_key_options
     */
    public const NO_KEY_OPTIONS = 2048;

    /**
     * @link https://dev.mysql.com/doc/refman/5.7/en/sql-mode.html#sqlmode_no_table_options
     * @link https://mariadb.com/kb/en/sql-mode/#no_table_options
     */
    public const NO_TABLE_OPTIONS = 4096;

    /**
     * @link https://dev.mysql.com/doc/refman/en/sql-mode.html#sqlmode_no_unsigned_subtraction
     * @link https://mariadb.com/kb/en/sql-mode/#no_unsigned_subtraction
     */
    public const NO_UNSIGNED_SUBTRACTION = 8192;

    /**
     * @link https://dev.mysql.com/doc/refman/en/sql-mode.html#sqlmode_no_zero_date
     * @link https://mariadb.com/kb/en/sql-mode/#no_zero_date
     */
    public const NO_ZERO_DATE = 16384;

    /**
     * @link https://dev.mysql.com/doc/refman/en/sql-mode.html#sqlmode_no_zero_in_date
     * @link https://mariadb.com/kb/en/sql-mode/#no_zero_in_date
     */
    public const NO_ZERO_IN_DATE = 32768;

    /**
     * @link https://dev.mysql.com/doc/refman/en/sql-mode.html#sqlmode_only_full_group_by
     * @link https://mariadb.com/kb/en/sql-mode/#only_full_group_by
     */
    public const ONLY_FULL_GROUP_BY = 65536;

    /**
     * @link https://dev.mysql.com/doc/refman/en/sql-mode.html#sqlmode_pipes_as_concat
     * @link https://mariadb.com/kb/en/sql-mode/#pipes_as_concat
     */
    public const PIPES_AS_CONCAT = 131072;

    /**
     * @link https://dev.mysql.com/doc/refman/en/sql-mode.html#sqlmode_real_as_float
     * @link https://mariadb.com/kb/en/sql-mode/#real_as_float
     */
    public const REAL_AS_FLOAT = 262144;

    /**
     * @link https://dev.mysql.com/doc/refman/en/sql-mode.html#sqlmode_strict_all_tables
     * @link https://mariadb.com/kb/en/sql-mode/#strict_all_tables
     */
    public const STRICT_ALL_TABLES = 524288;

    /**
     * @link https://dev.mysql.com/doc/refman/en/sql-mode.html#sqlmode_strict_trans_tables
     * @link https://mariadb.com/kb/en/sql-mode/#strict_trans_tables
     */
    public const STRICT_TRANS_TABLES = 1048576;

    /**
     * Custom mode.
     * The table and column names and any other field that must be escaped will not be.
     * Reserved keywords are being escaped regardless this mode is used or not.
     */
    public const NO_ENCLOSING_QUOTES = 1073741824;

    /**
     * Equivalent to {@see REAL_AS_FLOAT}, {@see PIPES_AS_CONCAT}, {@see ANSI_QUOTES}, {@see IGNORE_SPACE}.
     *
     * @link https://dev.mysql.com/doc/refman/5.7/en/sql-mode.html#sqlmode_ansi
     * @link https://mariadb.com/kb/en/sql-mode/#ansi
     */
    public const ANSI = 393234;

    /**
     * Equivalent to {@see PIPES_AS_CONCAT}, {@see ANSI_QUOTES}, {@see IGNORE_SPACE}, {@see NO_KEY_OPTIONS},
     * {@see NO_TABLE_OPTIONS}, {@see NO_FIELD_OPTIONS}.
     *
     * @link https://dev.mysql.com/doc/refman/5.7/en/sql-mode.html#sqlmode_db2
     * @link https://mariadb.com/kb/en/sql-mode/#db2
     */
    public const DB2 = 138258;

    /**
     * Equivalent to {@see PIPES_AS_CONCAT}, {@see ANSI_QUOTES}, {@see IGNORE_SPACE}, {@see NO_KEY_OPTIONS},
     * {@see NO_TABLE_OPTIONS}, {@see NO_FIELD_OPTIONS}, {@see NO_AUTO_CREATE_USER}.
     *
     * @link https://dev.mysql.com/doc/refman/5.7/en/sql-mode.html#sqlmode_maxdb
     * @link https://mariadb.com/kb/en/sql-mode/#maxdb
     */
    public const MAXDB = 138290;

    /**
     * Equivalent to {@see PIPES_AS_CONCAT}, {@see ANSI_QUOTES}, {@see IGNORE_SPACE}, {@see NO_KEY_OPTIONS},
     * {@see NO_TABLE_OPTIONS}, {@see NO_FIELD_OPTIONS}.
     *
     * @link https://dev.mysql.com/doc/refman/5.7/en/sql-mode.html#sqlmode_mssql
     * @link https://mariadb.com/kb/en/sql-mode/#mssql
     */
    public const MSSQL = 138258;

    /**
     * Equivalent to {@see PIPES_AS_CONCAT}, {@see ANSI_QUOTES}, {@see IGNORE_SPACE}, {@see NO_KEY_OPTIONS},
     * {@see NO_TABLE_OPTIONS}, {@see NO_FIELD_OPTIONS}, {@see NO_AUTO_CREATE_USER}.
     *
     * @link https://dev.mysql.com/doc/refman/5.7/en/sql-mode.html#sqlmode_oracle
     * @link https://mariadb.com/kb/en/sql-mode/#oracle
     */
    public const ORACLE = 138290;

    /**
     * Equivalent to {@see PIPES_AS_CONCAT}, {@see ANSI_QUOTES}, {@see IGNORE_SPACE}, {@see NO_KEY_OPTIONS},
     * {@see NO_TABLE_OPTIONS}, {@see NO_FIELD_OPTIONS}.
     *
     * @link https://dev.mysql.com/doc/refman/5.7/en/sql-mode.html#sqlmode_postgresql
     * @link https://mariadb.com/kb/en/sql-mode/#postgresql
     */
    public const POSTGRESQL = 138258;

    /**
     * Equivalent to {@see STRICT_TRANS_TABLES}, {@see STRICT_ALL_TABLES}, {@see NO_ZERO_IN_DATE},
     * {@see NO_ZERO_DATE}, {@see ERROR_FOR_DIVISION_BY_ZERO}, {@see NO_AUTO_CREATE_USER}.
     *
     * @link https://dev.mysql.com/doc/refman/en/sql-mode.html#sqlmode_traditional
     * @link https://mariadb.com/kb/en/sql-mode/#traditional
     */
    public const TRADITIONAL = 1622052;
}
