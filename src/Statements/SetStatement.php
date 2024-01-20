<?php

declare(strict_types=1);

namespace PhpMyAdmin\SqlParser\Statements;

use PhpMyAdmin\SqlParser\Components\OptionsArray;
use PhpMyAdmin\SqlParser\Components\SetOperation;
use PhpMyAdmin\SqlParser\Parsers\SetOperations;
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
     * @var array<string, array{non-empty-string, int-mask-of<self::ADD_*>}>
     */
    public static array $clauses = [
        'SET' => [
            'SET',
            Statement::ADD_CLAUSE | Statement::ADD_KEYWORD,
        ],
        '_END_OPTIONS' => [
            '_END_OPTIONS',
            Statement::ADD_CLAUSE,
        ],
    ];

    /**
     * Possible exceptions in SET statement.
     *
     * @var array<string, int|array<int, int|string>>
     * @psalm-var array<string, (positive-int|array{positive-int, ('var'|'var='|'expr'|'expr=')})>
     */
    public static array $statementOptions = [
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

    protected const STATEMENT_END_OPTIONS = [
        'COLLATE' => [
            1,
            'var',
        ],
        'DEFAULT' => 1,
    ];

    /**
     * Options used in current statement.
     */
    public OptionsArray|null $options = null;

    /**
     * The end options of this query.
     *
     * @see SetStatement::STATEMENT_END_OPTIONS
     */
    public OptionsArray|null $endOptions = null;

    /**
     * The updated values.
     *
     * @var SetOperation[]|null
     */
    public array|null $set = null;

    public function build(): string
    {
        $ret = 'SET ' . $this->options->build()
            . ' ' . SetOperations::buildAll($this->set)
            . ' ' . ($this->endOptions?->build() ?? '');

        return trim($ret);
    }
}
