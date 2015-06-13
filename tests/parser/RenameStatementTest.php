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
        );
    }
}
