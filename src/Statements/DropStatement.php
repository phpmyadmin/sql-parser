<?php

declare(strict_types=1);

namespace PhpMyAdmin\SqlParser\Statements;

use PhpMyAdmin\SqlParser\Components\Expression;
use PhpMyAdmin\SqlParser\Statement;

/**
 * `DROP` statement.
 */
class DropStatement extends Statement
{
    /**
     * Options of this statement.
     *
     * @var array<string, int|array<int, int|string>>
     * @psalm-var array<string, (positive-int|array{positive-int, ('var'|'var='|'expr'|'expr=')})>
     */
    public static array $statementOptions = [
        'DATABASE' => 1,
        'EVENT' => 1,
        'FUNCTION' => 1,
        'INDEX' => 1,
        'LOGFILE' => 1,
        'PROCEDURE' => 1,
        'SCHEMA' => 1,
        'SERVER' => 1,
        'TABLE' => 1,
        'VIEW' => 1,
        'TABLESPACE' => 1,
        'TRIGGER' => 1,
        'USER' => 1,

        'TEMPORARY' => 2,
        'IF EXISTS' => 3,
    ];

    /**
     * The clauses of this statement, in order.
     *
     * @see Statement::$clauses
     *
     * @var array<string, array{non-empty-string, int-mask-of<self::ADD_*>}>
     */
    public static array $clauses = [
        'DROP' => [
            'DROP',
            Statement::ADD_KEYWORD,
        ],
        // Used for options.
        '_OPTIONS' => [
            '_OPTIONS',
            Statement::ADD_CLAUSE,
        ],
        // Used for select expressions.
        'DROP_' => [
            'DROP',
            Statement::ADD_CLAUSE,
        ],
        'ON' => [
            'ON',
            Statement::ADD_CLAUSE | Statement::ADD_KEYWORD,
        ],
    ];

    /**
     * Dropped elements.
     *
     * @var Expression[]|null
     */
    public array|null $fields = null;

    /**
     * Table of the dropped index.
     */
    public Expression|null $table = null;
}
