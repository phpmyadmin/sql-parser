<?php

namespace SqlParser\Tests\Fragments;

use SqlParser\Parser;
use SqlParser\Fragments\FieldFragment;

use SqlParser\Tests\TestCase;

class FieldFragmentTest extends TestCase
{

    public function testParse()
    {
        $fragment = FieldFragment::parse(new Parser(), $this->getTokensList('IF(film_id > 0, film_id, film_id)'));
        $this->assertEquals($fragment->expr, 'IF(film_id > 0, film_id, film_id)');
    }

    public function testParseErr1()
    {
        $parser = new Parser();
        FieldFragment::parse($parser, $this->getTokensList('(1))'));
        $errors = $this->getErrorsAsArray($parser);
        $this->assertEquals($errors[0][0], 'Unexpected bracket.');
    }

    public function testParseErr2()
    {
        $parser = new Parser();
        FieldFragment::parse($parser, $this->getTokensList('tbl..col'));
        $errors = $this->getErrorsAsArray($parser);
        $this->assertEquals($errors[0][0], 'Unexpected dot.');
    }

    public function testBuild()
    {
        $fragment = new FieldFragment('1 + 2', 'three');
        $this->assertEquals(FieldFragment::build($fragment), '1 + 2 AS `three`');
    }
}
