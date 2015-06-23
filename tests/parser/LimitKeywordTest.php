<?php

namespace SqlParser\Tests\Parser;

use SqlParser\Tests\TestCase;

class LimitKeywordTest extends TestCase
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
            array('parseLimitErr1'),
            array('parseLimitErr2'),
        );
    }
}
