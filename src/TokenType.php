<?php

declare(strict_types=1);

namespace PhpMyAdmin\SqlParser;

enum TokenType: int
{
    /**
     * This type is used when the token is invalid or its type cannot be
     * determined because of the ambiguous context. Further analysis might be
     * required to detect its type.
     */
    case None = 0;

    /**
     * SQL specific keywords: SELECT, UPDATE, INSERT, etc.
     */
    case Keyword = 1;

    /**
     * Any type of legal operator.
     *
     * Arithmetic operators: +, -, *, /, etc.
     * Logical operators: ===, <>, !==, etc.
     * Bitwise operators: &, |, ^, etc.
     * Assignment operators: =, +=, -=, etc.
     * SQL specific operators: . (e.g. .. WHERE database.table ..),
     *                         * (e.g. SELECT * FROM ..)
     */
    case Operator = 2;

    /**
     * Spaces, tabs, new lines, etc.
     */
    case Whitespace = 3;

    /**
     * Any type of legal comment.
     *
     * Bash (#), C (/* *\/) or SQL (--) comments:
     *
     *      -- SQL-comment
     *
     *      #Bash-like comment
     *
     *      /*C-like comment*\/
     *
     * or:
     *
     *      /*C-like
     *        comment*\/
     *
     * Backslashes were added to respect PHP's comments syntax.
     */
    case Comment = 4;

    /**
     * Boolean values: true or false.
     */
    case Bool = 5;

    /**
     * Numbers: 4, 0x8, 15.16, 23e42, etc.
     */
    case Number = 6;

    /**
     * Literal strings: 'string', "test".
     * Some of these strings are actually symbols.
     */
    case String = 7;

    /**
     * Database, table names, variables, etc.
     * For example: ```SELECT `foo`, `bar` FROM `database`.`table`;```.
     */
    case Symbol = 8;

    /**
     * Delimits an unknown string.
     * For example: ```SELECT * FROM test;```, `test` is a delimiter.
     */
    case Delimiter = 9;

    /**
     * Labels in LOOP statement, ITERATE statement etc.
     * For example (only for begin label):
     *  begin_label: BEGIN [statement_list] END [end_label]
     *  begin_label: LOOP [statement_list] END LOOP [end_label]
     *  begin_label: REPEAT [statement_list] ... END REPEAT [end_label]
     *  begin_label: WHILE ... DO [statement_list] END WHILE [end_label].
     */
    case Label = 10;
}
