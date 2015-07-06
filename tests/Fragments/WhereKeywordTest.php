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
        $this->assertEquals($fragment[0]->expr, 'id = 10');
    }

    public function testParseBetween()
    {
        $fragment = WhereKeyword::parse(new Parser(), $this->getTokensList('(id BETWEEN 10 AND 20) OR (id BETWEEN 30 AND 40)'));
        $this->assertEquals($fragment[0]->expr, '(id BETWEEN 10 AND 20)');
        $this->assertEquals($fragment[1]->expr, 'OR');
        $this->assertEquals($fragment[2]->expr, '(id BETWEEN 30 AND 40)');
    }
}
