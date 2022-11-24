<?php

declare(strict_types=1);

namespace PhpMyAdmin\SqlParser\Tests\Parser;

use PhpMyAdmin\SqlParser\Tests\TestCase;

class ExplainStatementTest extends TestCase
{
    /**
     * @dataProvider explainProvider
     */
    public function testExplain(string $test): void
    {
        $this->runParserTest($test);
    }

    /**
     * @return string[][]
     */
    public function explainProvider(): array
    {
        return [
            ['parser/parseExplain'],
            ['parser/parseExplain1'],
            ['parser/parseExplain2'],
            ['parser/parseExplain3'],
            ['parser/parseExplain4'],
            ['parser/parseExplainErr'],
            ['parser/parseExplainErr1'],
            ['parser/parseExplainErr2'],
            ['parser/parseExplainErr3'],
            ['parser/parseExplainErr4'],
        ];
    }
}
