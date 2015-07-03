<?php

namespace SqlParser\Tests\Fragments;

use SqlParser\Parser;
use SqlParser\Fragments\ReferencesKeyword;

use SqlParser\Tests\TestCase;

class ReferencesKeywordTest extends TestCase
{

    public function testParse()
    {
        $fragment = ReferencesKeyword::parse(new Parser(), $this->getTokensList('tbl (id)'));
        $this->assertEquals('tbl', $fragment->table);
        $this->assertEquals(array('id'), $fragment->columns);
    }

    public function testBuild()
    {
        $fragment = new ReferencesKeyword('tbl', array('id'));
        $this->assertEquals('`tbl` (`id`)', ReferencesKeyword::build($fragment));
    }
}
