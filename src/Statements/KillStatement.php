<?php

declare(strict_types=1);

namespace PhpMyAdmin\SqlParser\Statements;

use PhpMyAdmin\SqlParser\Components\Expression;
use PhpMyAdmin\SqlParser\Statement;

use function trim;

/**
 * `KILL` statement.
 *
 * KILL [CONNECTION | QUERY] processlist_id
 */
class KillStatement extends Statement
{
    /**
     * Options of this statement.
     *
     * @var array<string, int|array<int, int|string>>
     * @psalm-var array<string, (positive-int|array{positive-int, ('var'|'var='|'expr'|'expr=')})>
     */
    public static array $statementOptions = [
        'CONNECTION' => 1,
        'QUERY' => 1,
    ];

    public Expression|null $processListId = null;

    public function build(): string
    {
        $option = $this->options === null || $this->options->isEmpty()
            ? ''
            : ' ' . $this->options->build();
        $expression = $this->processListId === null ? '' : ' ' . $this->processListId->build();

        return trim('KILL' . $option . $expression);
    }
}
