<?php

namespace SqlParser\Tests\Fragments;

use SqlParser\Parser;
use SqlParser\Fragments\LimitKeyword;

use SqlParser\Tests\TestCase;

class LimitKeywordTest extends TestCase
{

    public function testBuild()
    {
        $fragment = new LimitKeyword(1);
        $this->assertEquals(LimitKeyword::build($fragment), '1');
    }

    public function testBuildWithOffset()
    {
        $fragment = new LimitKeyword(1, 2);
        $this->assertEquals(LimitKeyword::build($fragment), '2, 1');
    }

    /**
     * @dataProvider testParseProvider
     */
    public function testParse($test)
    {
        $this->runParserTest($test);
    }

    public function testParseProvider()
    {
        return array(
            array('parseLimitErr1'),
            array('parseLimitErr2'),
        );
    }
}
