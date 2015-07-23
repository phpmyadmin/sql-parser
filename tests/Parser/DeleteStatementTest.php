<?php

namespace SqlParser\Tests\Parser;

use SqlParser\Tests\TestCase;

class DeleteStatementTest extends TestCase
{

    /**
     * @dataProvider testDeleteProvider
     */
    public function testDelete($test)
    {
        $this->runParserTest($test);
    }

    public function testDeleteProvider()
    {
        return array(
            array('parser/parseDelete'),
        );
    }
}
