<?php

declare(strict_types=1);

namespace PhpMyAdmin\SqlParser\Statements;

use PhpMyAdmin\SqlParser\Components\OptionsArray;
use PhpMyAdmin\SqlParser\Components\SetOperation;
use PhpMyAdmin\SqlParser\Statement;

use function trim;

/**
 * `SET` statement.
 */
class SetStatement extends Statement
{
    /**
     * The clauses of this statement, in order.
     *
     * @see Statement::$clauses
     *
     * @var array<string, array<int, int|string>>
     * @psalm-var array<string, array{non-empty-string, (1|2|3)}>
     */
    public static $clauses = [
        'SET' => [
            'SET',
            3,
        ],
        '_END_OPTIONS' => [
            '_END_OPTIONS',
            1,
        ],
    ];

    /**
     * Possible exceptions in SET statement.
     *
     * @var array<string, int|array<int, int|string>>
     * @psalm-var array<string, (positive-int|array{positive-int, ('var'|'var='|'expr'|'expr=')})>
     */
    public static $statementOptions = [
        'CHARSET' => [
            3,
            'var',
        ],
        'CHARACTER SET' => [
            3,
            'var',
        ],
        'NAMES' => [
            3,
            'var',
        ],
        'PASSWORD' => [
            3,
            'expr',
        ],
        'SESSION' => 3,
        'GLOBAL' => 3,
        'PERSIST' => 3,
        'PERSIST_ONLY' => 3,
        '@@SESSION' => 3,
        '@@GLOBAL' => 3,
        '@@PERSIST' => 3,
        '@@PERSIST_ONLY' => 3,
    ];

    /**
     * @var array<string, int|array<int, int|string>>
     * @psalm-var array<string, (positive-int|array{positive-int, ('var'|'var='|'expr'|'expr=')})>
     */
    public static $statementEndOptions = [
        'COLLATE' => [
            1,
            'var',
        ],
        'DEFAULT' => 1,
    ];

    /**
     * Options used in current statement.
     *
     * @var OptionsArray|null
     */
    public $options;

    /**
     * The end options of this query.
     *
     * @see SetStatement::$statementEndOptions
     *
     * @var OptionsArray|null
     */
    public $endOptions;

    /**
     * The updated values.
     *
     * @var SetOperation[]|null
     */
    public $set;

    public function build(): string
    {
        $ret = 'SET ' . OptionsArray::build($this->options)
            . ' ' . SetOperation::build($this->set)
            . ' ' . OptionsArray::build($this->endOptions);

        return trim($ret);
    }
}
