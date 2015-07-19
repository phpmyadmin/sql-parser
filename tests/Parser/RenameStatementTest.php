<?php

namespace SqlParser\Tests\Parser;

use SqlParser\Tests\TestCase;

class RenameStatementTest extends TestCase
{

    /**
     * @dataProvider testRenameProvider
     */
    public function testRename($test)
    {
        $this->runParserTest($test);
    }

    public function testRenameProvider()
    {
        return array(
            array('parseRename'),
            array('parseRename2'),
            array('parseRenameErr1'),
            array('parseRenameErr2'),
            array('parseRenameErr3'),
            array('parseRenameErr4'),
        );
    }
}
