<?php

namespace SqlParser\Tests\Fragments;

use SqlParser\Fragments\ArrayFragment;

use SqlParser\Tests\TestCase;

class ArrayFragmentTest extends TestCase
{

    public function testBuildRaw()
    {
        $fragment = new ArrayFragment(array('a', 'b'), array());
        $this->assertEquals('(a, b)', ArrayFragment::build($fragment));
    }

    public function testBuildValues()
    {
        $fragment = new ArrayFragment(array(), array('a', 'b'));
        $this->assertEquals('(a, b)', ArrayFragment::build($fragment));
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
            array('parseArrayErr1'),
            array('parseArrayErr2'),
            array('parseArrayErr3'),
        );
    }
}
