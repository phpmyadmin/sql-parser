<?php

declare(strict_types=1);

namespace PhpMyAdmin\SqlParser\Tests\Parser;

use PhpMyAdmin\SqlParser\Tests\TestCase;
use PHPUnit\Framework\Attributes\DataProvider;

class RestoreStatementTest extends TestCase
{
    #[DataProvider('restoreProvider')]
    public function testRestore(string $test): void
    {
        $this->runParserTest($test);
    }

    /**
     * @return string[][]
     */
    public static function restoreProvider(): array
    {
        return [
            ['parser/parseRestore'],
        ];
    }
}
