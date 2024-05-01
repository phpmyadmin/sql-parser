<?php

declare(strict_types=1);

namespace PhpMyAdmin\SqlParser\Statements;

use PhpMyAdmin\SqlParser\Components\Expression;
use PhpMyAdmin\SqlParser\Components\OptionsArray;
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
    public static $OPTIONS = [
        'CONNECTION' => 1,
        'QUERY' => 1,
    ];

    /** @var Expression|null */
    public $processListId = null;

    public function build(): string
    {
        $option = $this->options === null || $this->options->isEmpty()
            ? ''
            : ' ' . OptionsArray::build($this->options);
        $expression = $this->processListId === null ? '' : ' ' . Expression::build($this->processListId);

        return trim('KILL' . $option . $expression);
    }
}
