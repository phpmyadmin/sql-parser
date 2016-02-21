<?php

namespace SqlParser\Tests\Components;

use SqlParser\Parser;
use SqlParser\Components\Limit;

use SqlParser\Tests\TestCase;

class LimitTest extends TestCase
{

    public function testBuild()
    {
        $component = new Limit(1);
        $this->assertEquals(Limit::build($component), '1');
    }

    public function testBuildWithOffset()
    {
        $component = new Limit(1, 2);
        $this->assertEquals(Limit::build($component), '2, 1');
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
            array('parser/parseLimitErr1'),
            array('parser/parseLimitErr2'),
        );
    }
}
