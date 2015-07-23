<?php

namespace SqlParser\Tests\Parser;

use SqlParser\Tests\TestCase;

class InsertStatementTest extends TestCase
{

    /**
     * @dataProvider testInsertProvider
     */
    public function testInsert($test)
    {
        $this->runParserTest($test);
    }

    public function testInsertProvider()
    {
        return array(
            array('parser/parseInsert'),
        );
    }
}
