<?php

declare(strict_types=1);

namespace PhpMyAdmin\SqlParser\Tests\Parser;

use PhpMyAdmin\SqlParser\Tests\TestCase;
use PHPUnit\Framework\Attributes\DataProvider;

class AnalyzeStatementTest extends TestCase
{
    #[DataProvider('analyzeProvider')]
    public function testAnalyze(string $test): void
    {
        $this->runParserTest($test);
    }

    /**
     * @return string[][]
     */
    public static function analyzeProvider(): array
    {
        return [
            ['parser/parseAnalyzeTable'],
            ['parser/parseAnalyzeTable1'],
            ['parser/parseAnalyzeErr1'],
            ['parser/parseAnalyzeErr2'],
        ];
    }
}
