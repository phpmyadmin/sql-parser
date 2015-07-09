<?php

namespace SqlParser\Tests\Components;

use SqlParser\Components\ArrayObj;

use SqlParser\Tests\TestCase;

class ArrayObjTest extends TestCase
{

    public function testBuildRaw()
    {
        $component = new ArrayObj(array('a', 'b'), array());
        $this->assertEquals('(a, b)', ArrayObj::build($component));
    }

    public function testBuildValues()
    {
        $component = new ArrayObj(array(), array('a', 'b'));
        $this->assertEquals('(a, b)', ArrayObj::build($component));
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
