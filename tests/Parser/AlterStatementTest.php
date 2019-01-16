<?php
declare(strict_types=1);

namespace PhpMyAdmin\SqlParser\Tests\Parser;

use PhpMyAdmin\SqlParser\Tests\TestCase;

class AlterStatementTest extends TestCase
{
    /**
     * @dataProvider alterProvider
     *
     * @param mixed $test
     */
    public function testAlter($test)
    {
        $this->runParserTest($test);
    }

    public function alterProvider()
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
            ['parser/parseAlterErr'],
            ['parser/parseAlterErr2'],
        ];
    }
}
