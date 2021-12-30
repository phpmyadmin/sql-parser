<?php

declare(strict_types=1);

namespace PhpMyAdmin\SqlParser\Tests\Parser;

use PhpMyAdmin\SqlParser\Tests\TestCase;

class TransactionStatementTest extends TestCase
{
    /**
     * @dataProvider transactionProvider
     */
    public function testTransaction(string $test): void
    {
        $this->runParserTest($test);
    }

    /**
     * @return string[][]
     */
    public function transactionProvider(): array
    {
        return [
            ['parser/parseTransaction'],
            ['parser/parseTransaction2'],
            ['parser/parseTransaction3'],
            ['parser/parseTransactionErr1'],
        ];
    }
}
