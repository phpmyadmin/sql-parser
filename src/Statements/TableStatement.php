<?php

declare(strict_types=1);

namespace PhpMyAdmin\SqlParser\Statements;

use PhpMyAdmin\SqlParser\Components\Expression;
use PhpMyAdmin\SqlParser\Components\Limit;
use PhpMyAdmin\SqlParser\Components\OrderKeyword;
use PhpMyAdmin\SqlParser\Statement;

/**
 * `TABLE` statement.
 *
 * TABLE table_references
 *     [ORDER BY {col_name | expr | position}
 *       [ASC | DESC], ...]
 *     [LIMIT row_count [OFFSET offset]]
 */
class TableStatement extends Statement
{
    /**
     * The clauses of this statement, in order.
     *
     * @see Statement::$CLAUSES
     *
     * @var array<string, array<int, int|string>>
     * @psalm-var array<string, array{non-empty-string, (1|2|3)}>
     */
    public static $CLAUSES = [
        'TABLE' => [
            'TABLE',
            3,
        ],
        'ORDER BY' => [
            'ORDER BY',
            3,
        ],
        'LIMIT' => [
            'LIMIT',
            3,
        ],
        'UNION' => [
            'UNION',
            1,
        ],
    ];

    /**
     * Tables used as sources for this statement.
     *
     * @var Expression[]
     */
    public $from = [];

    /**
     * Specifies the order of the rows in the result set.
     *
     * @var OrderKeyword[]|null
     */
    public $order;

    /**
     * Conditions used for limiting the size of the result set.
     *
     * @var Limit|null
     */
    public $limit;

    /**
     * Unions.
     *
     * @var TableStatement[]
     */
    public $union = [];
}
