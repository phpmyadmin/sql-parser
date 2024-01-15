<?php

declare(strict_types=1);

namespace PhpMyAdmin\SqlParser\Tests\Parser;

use PhpMyAdmin\SqlParser\Tests\TestCase;
use PHPUnit\Framework\Attributes\DataProvider;

class CallStatementTest extends TestCase
{
    #[DataProvider('callProvider')]
    public function testCall(string $test): void
    {
        $this->runParserTest($test);
    }

    /** @return string[][] */
    public static function callProvider(): array
    {
        return [
            ['parser/parseCall'],
            ['parser/parseCall2'],
            ['parser/parseCall3'],
            ['parser/parseCall4'],
            ['parser/parseCall5'],
        ];
    }
}
