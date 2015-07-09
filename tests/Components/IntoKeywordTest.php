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

    public function testParseErr1()
    {
        $component = IntoKeyword::parse(new Parser(), $this->getTokensList('OUTFILE;'));
        $this->assertEquals($component->type, 'OUTFILE');
    }
}
