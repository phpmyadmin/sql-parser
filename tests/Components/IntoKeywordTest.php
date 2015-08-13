<?php

namespace SqlParser\Tests\Components;

use SqlParser\Parser;
use SqlParser\Components\IntoKeyword;

use SqlParser\Tests\TestCase;

class IntoKeywordTest extends TestCase
{

    public function testParse()
    {
        $component = IntoKeyword::parse(new Parser(), $this->getTokensList('OUTFILE "/tmp/outfile.txt"'));
        $this->assertEquals($component->type, 'OUTFILE');
        $this->assertEquals($component->dest, '/tmp/outfile.txt');
    }

    public function testBuild()
    {
        $component = IntoKeyword::parse(new Parser(), $this->getTokensList('tbl(col1, col2)'));
        $this->assertEquals('tbl(col1, col2)', IntoKeyword::build($component));
    }

    public function testBuildOutfile()
    {
        $component = IntoKeyword::parse(new Parser(), $this->getTokensList('OUTFILE "/tmp/outfile.txt"'));
        $this->assertEquals('OUTFILE "/tmp/outfile.txt"', IntoKeyword::build($component));
    }

    public function testParseErr1()
    {
        $component = IntoKeyword::parse(new Parser(), $this->getTokensList('OUTFILE;'));
        $this->assertEquals($component->type, 'OUTFILE');
    }
}
