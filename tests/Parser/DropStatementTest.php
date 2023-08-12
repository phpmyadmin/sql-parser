<?php

declare(strict_types=1);

namespace PhpMyAdmin\SqlParser\Tests\Parser;

use PhpMyAdmin\SqlParser\Tests\TestCase;
use PHPUnit\Framework\Attributes\DataProvider;

class DropStatementTest extends TestCase
{
    #[DataProvider('dropProvider')]
    public function testDrop(string $test): void
    {
        $this->runParserTest($test);
    }

    /**
     * @return string[][]
     */
    public static function dropProvider(): array
    {
        return [
            ['parser/parseDrop'],
            ['parser/parseDrop2'],
        ];
    }
}
