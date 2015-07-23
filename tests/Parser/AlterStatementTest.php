<?php

namespace SqlParser\Tests\Parser;

use SqlParser\Tests\TestCase;

class AlterStatementTest extends TestCase
{

    /**
     * @dataProvider testAlterProvider
     */
    public function testAlter($test)
    {
        $this->runParserTest($test);
    }

    public function testAlterProvider()
    {
        return array(
            array('parser/parseAlter'),
            array('parser/parseAlter2'),
        );
    }
}
