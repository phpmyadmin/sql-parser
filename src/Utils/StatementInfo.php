<?php

declare(strict_types=1);

namespace PhpMyAdmin\SqlParser\Utils;

use PhpMyAdmin\SqlParser\Parser;
use PhpMyAdmin\SqlParser\Statement;

final class StatementInfo
{
    /**
     * @param Parser         $parser       The parser used to analyze the statement.
     * @param Statement|null $statement    The first statement resulted from parsing.
     * @param array[]        $selectTables The real name of the tables selected; if there are no table names in the
     *                                     `SELECT` expressions, the table names are fetched from the `FROM` expressions
     * @psalm-param list<array{string|null, string|null}> $selectTables
     * @psalm-param list<string|null> $selectExpressions
     */
    public function __construct(
        public readonly Parser $parser,
        public readonly Statement|null $statement,
        public readonly StatementFlags $flags,
        public readonly array $selectTables,
        public readonly array $selectExpressions,
    ) {
    }
}
