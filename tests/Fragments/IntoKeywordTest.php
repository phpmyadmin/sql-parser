<?php

namespace SqlParser\Tests\Fragments;

use SqlParser\Parser;
use SqlParser\Fragments\IntoKeyword;

use SqlParser\Tests\TestCase;

class IntoKeywordTest extends TestCase
{

    public function testParse()
    {
        $fragment = IntoKeyword::parse(new Parser(), $this->getTokensList('OUTFILE "/tmp/outfile.txt"'));
        $this->assertEquals($fragment->type, 'OUTFILE');
        $this->assertEquals($fragment->name, '/tmp/outfile.txt');
    }

    public function testParseErr1()
    {
        $fragment = IntoKeyword::parse(new Parser(), $this->getTokensList('OUTFILE;'));
        $this->assertEquals($fragment->type, 'OUTFILE');
    }
}
