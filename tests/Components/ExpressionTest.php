<?php

namespace SqlParser\Tests\Components;

use SqlParser\Parser;
use SqlParser\Components\Expression;

use SqlParser\Tests\TestCase;

class ExpressionTest extends TestCase
{

    public function testParse()
    {
        $component = Expression::parse(new Parser(), $this->getTokensList('IF(film_id > 0, film_id, film_id)'));
        $this->assertEquals($component->expr, 'IF(film_id > 0, film_id, film_id)');
    }

    public function testParse2()
    {
        $component = Expression::parse(new Parser(), $this->getTokensList('col`test`'));
    }

    public function testParseErr1()
    {
        $parser = new Parser();
        Expression::parse($parser, $this->getTokensList('(1))'));
        $errors = $this->getErrorsAsArray($parser);
        $this->assertEquals($errors[0][0], 'Unexpected bracket.');
    }

    public function testParseErr2()
    {
        $parser = new Parser();
        Expression::parse($parser, $this->getTokensList('tbl..col'));
        $errors = $this->getErrorsAsArray($parser);
        $this->assertEquals($errors[0][0], 'Unexpected dot.');
    }

    public function testBuild()
    {
        $component = new Expression('1 + 2', 'three');
        $this->assertEquals(Expression::build($component), '1 + 2 AS `three`');
    }
}
