<?php

namespace SqlParser\Tests\Components;

use SqlParser\Parser;
use SqlParser\Components\Array2d;

use SqlParser\Tests\TestCase;

class Array2dTest extends TestCase
{

    public function testParse()
    {
        $parser = new Parser();
        $arrays = Array2d::parse($parser, $this->getTokensList('(1, 2) +'));
        $this->assertEquals(
            array(1, 2),
            $arrays[0]->values
        );
    }

    public function testBuild()
    {
        $arrays = Array2d::parse(new Parser(), $this->getTokensList('(1, 2), (3, 4), (5, 6)'));
        $this->assertEquals(
            '(1, 2), (3, 4), (5, 6)',
            Array2d::build($arrays)
        );
    }

    public function testParseErr1()
    {
        $parser = new Parser();
        Array2d::parse($parser, $this->getTokensList('(1, 2 +'));
        // TODO: Assert errors.
    }

    public function testParseErr2()
    {
        $parser = new Parser();
        Array2d::parse($parser, $this->getTokensList('(1, 2 TABLE'));
    }

    public function testParseErr3()
    {
        $parser = new Parser();
        Array2d::parse($parser, $this->getTokensList(')'));
        Array2d::parse($parser, $this->getTokensList('TABLE'));
        // TODO: Assert errors.
    }

    public function testParseErr4()
    {
        $parser = new Parser();
        Array2d::parse($parser, $this->getTokensList('(1, 2),'));
        $this->assertEquals(
            "An opening bracket followed by a set of values was expected.",
            $parser->errors[0]->getMessage()
        );
    }

    public function testParseErr5()
    {
        $parser = new Parser();
        Array2d::parse($parser, $this->getTokensList('(1, 2),(3)'));
        $this->assertEquals(
            "2 values were expected, but found 1.",
            $parser->errors[0]->getMessage()
        );
    }
}
