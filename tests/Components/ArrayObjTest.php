<?php

namespace SqlParser\Tests\Components;

use SqlParser\Parser;
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

    public function testParseType()
    {
        $components = ArrayObj::parse(
            new Parser(),
            $this->getTokensList('(1 + 2, 3 + 4)'),
            array(
                'type' => 'SqlParser\\Components\\Expression',
                'typeOptions' => array(
                    'breakOnParentheses' => true,
                ),
            )
        );
        $this->assertEquals($components[0]->expr, '1 + 2');
        $this->assertEquals($components[1]->expr, '3 + 4');
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
            array('parser/parseArrayErr1'),
            array('parser/parseArrayErr3'),
        );
    }
}
