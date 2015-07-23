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
            array('parser/parseCall'),
            array('parser/parseCall2'),
            array('parser/parseCall3'),
        );
    }
}
