<?php

namespace SqlParser\Tests\Parser;

use SqlParser\Tests\TestCase;

class UpdateStatementTest extends TestCase
{

    /**
     * @dataProvider testUpdateProvider
     */
    public function testUpdate($test)
    {
        $this->runParserTest($test);
    }

    public function testUpdateProvider()
    {
        return array(
            array('parser/parseUpdate'),
            array('parser/parseUpdate2'),
        );
    }
}
