<?php

declare(strict_types=1);

namespace PhpMyAdmin\SqlParser\Statements;

use PhpMyAdmin\SqlParser\Components\Expression;
use PhpMyAdmin\SqlParser\Statement;

/**
 * `TRUNCATE` statement.
 */
class TruncateStatement extends Statement
{
    /**
     * Options for `TRUNCATE` statements.
     *
     * @var array<string, int|array<int, int|string>>
     * @psalm-var array<string, (positive-int|array{positive-int, ('var'|'var='|'expr'|'expr=')})>
     */
    public static array $statementOptions = ['TABLE' => 1];

    /**
     * The name of the truncated table.
     */
    public Expression|null $table = null;

    /**
     * Special build method for truncate statement as Statement::build would return empty string.
     */
    public function build(): string
    {
        return 'TRUNCATE TABLE ' . $this->table . ';';
    }
}
