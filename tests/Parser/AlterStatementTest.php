<?php

declare(strict_types=1);

namespace PhpMyAdmin\SqlParser\Tests\Parser;

use PhpMyAdmin\SqlParser\Tests\TestCase;

class AlterStatementTest extends TestCase
{
    /**
     * @param mixed $test
     *
     * @dataProvider alterProvider
     */
    public function testAlter($test)
    {
        $this->runParserTest($test);
    }

    public function alterProvider(): array
    {
        return [
            ['parser/parseAlter'],
            ['parser/parseAlter2'],
            ['parser/parseAlter3'],
            ['parser/parseAlter4'],
            ['parser/parseAlter5'],
            ['parser/parseAlter6'],
            ['parser/parseAlter7'],
            ['parser/parseAlter8'],
            ['parser/parseAlter9'],
            ['parser/parseAlter10'],
            ['parser/parseAlterErr'],
            ['parser/parseAlterErr2'],
            ['parser/parseAlterErr3'],
            ['parser/parseAlterWithInvisible'],
        ];
    }
}
