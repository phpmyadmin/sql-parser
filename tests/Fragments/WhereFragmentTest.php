<?php

namespace SqlParser\Tests\Fragments;

use SqlParser\Parser;
use SqlParser\Fragments\WhereKeyword;

use SqlParser\Tests\TestCase;

class WhereKeywordTest extends TestCase
{

    public function testParse()
    {
        $fragment = WhereKeyword::parse(new Parser(), $this->getTokensList('/* id = */ id = 10'));
        $this->assertEquals($fragment[0]->condition, 'id = 10');
    }
}
