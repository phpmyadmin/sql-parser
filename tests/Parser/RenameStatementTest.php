<?php

namespace PhpMyAdmin\SqlParser\Tests\Parser;

use PhpMyAdmin\SqlParser\Tests\TestCase;

class RenameStatementTest extends TestCase
{
    /**
     * @dataProvider testRenameProvider
     *
     * @param mixed $test
     */
    public function testRename($test)
    {
        $this->runParserTest($test);
    }

    public function testRenameProvider()
    {
        return [
            ['parser/parseRename'],
            ['parser/parseRename2'],
            ['parser/parseRenameErr1'],
            ['parser/parseRenameErr2'],
            ['parser/parseRenameErr3'],
            ['parser/parseRenameErr4'],
            ['parser/parseRenameErr5'],
        ];
    }
}
