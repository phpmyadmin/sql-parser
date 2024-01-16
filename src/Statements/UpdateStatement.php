<?php

declare(strict_types=1);

namespace PhpMyAdmin\SqlParser\Statements;

use PhpMyAdmin\SqlParser\Components\Condition;
use PhpMyAdmin\SqlParser\Components\Expression;
use PhpMyAdmin\SqlParser\Components\JoinKeyword;
use PhpMyAdmin\SqlParser\Components\Limit;
use PhpMyAdmin\SqlParser\Components\OrderKeyword;
use PhpMyAdmin\SqlParser\Components\SetOperation;
use PhpMyAdmin\SqlParser\Statement;

/**
 * `UPDATE` statement.
 *
 * UPDATE [LOW_PRIORITY] [IGNORE] table_reference
 *     [INNER JOIN | LEFT JOIN | JOIN] T1 ON T1.C1 = T2.C1
 *     SET col_name1={expr1|DEFAULT} [, col_name2={expr2|DEFAULT}] ...
 *     [WHERE where_condition]
 *     [ORDER BY ...]
 *     [LIMIT row_count]
 *
 * or
 *
 * UPDATE [LOW_PRIORITY] [IGNORE] table_references
 *     SET col_name1={expr1|DEFAULT} [, col_name2={expr2|DEFAULT}] ...
 *     [WHERE where_condition]
 */
class UpdateStatement extends Statement
{
    /**
     * Options for `UPDATE` statements and their slot ID.
     *
     * @var array<string, int|array<int, int|string>>
     * @psalm-var array<string, (positive-int|array{positive-int, ('var'|'var='|'expr'|'expr=')})>
     */
    public static array $statementOptions = [
        'LOW_PRIORITY' => 1,
        'IGNORE' => 2,
    ];

    /**
     * The clauses of this statement, in order.
     *
     * @see Statement::$clauses
     *
     * @var array<string, array{non-empty-string, int-mask-of<self::ADD_*>}>
     */
    public static array $clauses = [
        'UPDATE' => [
            'UPDATE',
            Statement::ADD_KEYWORD,
        ],
        // Used for options.
        '_OPTIONS' => [
            '_OPTIONS',
            Statement::ADD_CLAUSE,
        ],
        // Used for updated tables.
        '_UPDATE' => [
            'UPDATE',
            Statement::ADD_CLAUSE,
        ],
        'JOIN' => [
            'JOIN',
            Statement::ADD_CLAUSE,
        ],
        'LEFT JOIN' => [
            'LEFT JOIN',
            Statement::ADD_CLAUSE,
        ],
        'INNER JOIN' => [
            'INNER JOIN',
            Statement::ADD_CLAUSE,
        ],
        'SET' => [
            'SET',
            Statement::ADD_CLAUSE | Statement::ADD_KEYWORD,
        ],
        'WHERE' => [
            'WHERE',
            Statement::ADD_CLAUSE | Statement::ADD_KEYWORD,
        ],
        'ORDER BY' => [
            'ORDER BY',
            Statement::ADD_CLAUSE | Statement::ADD_KEYWORD,
        ],
        'LIMIT' => [
            'LIMIT',
            Statement::ADD_CLAUSE | Statement::ADD_KEYWORD,
        ],
    ];

    /**
     * Tables used as sources for this statement.
     *
     * @var Expression[]|null
     */
    public array|null $tables = null;

    /**
     * The updated values.
     *
     * @var SetOperation[]|null
     */
    public array|null $set = null;

    /**
     * Conditions used for filtering each row of the result set.
     *
     * @var Condition[]|null
     */
    public array|null $where = null;

    /**
     * Specifies the order of the rows in the result set.
     *
     * @var OrderKeyword[]|null
     */
    public array|null $order = null;

    /**
     * Conditions used for limiting the size of the result set.
     */
    public Limit|null $limit = null;

    /**
     * Joins.
     *
     * @var JoinKeyword[]|null
     */
    public array|null $join = null;
}
