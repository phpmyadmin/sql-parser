<?php

namespace SqlParser\Tests\Parser;

use SqlParser\Tests\TestCase;

class RestoreStatementTest extends TestCase
{

    /**
     * @dataProvider testRestoreProvider
     */
    public function testRestore($test)
    {
        $this->runParserTest($test);
    }

    public function testRestoreProvider()
    {
        return array(
            array('parser/parseRestore'),
        );
    }
}
