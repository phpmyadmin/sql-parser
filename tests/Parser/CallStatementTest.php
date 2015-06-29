<?php

namespace SqlParser\Tests\Parser;

use SqlParser\Tests\TestCase;

class CallStatementTest extends TestCase
{

    /**
     * @dataProvider testCallProvider
     */
    public function testCall($test)
    {
        $this->runParserTest($test);
    }

    public function testCallProvider()
    {
        return array(
            array('parseCall'),
            array('parseCall2'),
            array('parseCall3'),
        );
    }
}
