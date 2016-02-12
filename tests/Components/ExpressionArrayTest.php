<?php

namespace SqlParser\Tests\Components;

use SqlParser\Parser;
use SqlParser\Components\ExpressionArray;

use SqlParser\Tests\TestCase;

class ExpressionArrayTest extends TestCase
{

    public function testParse()
    {
        $component = ExpressionArray::parse(
            new Parser(),
            $this->getTokensList('(expr)'),
            array(
                'breakOnParentheses' => true,
            )
        );
        $this->assertEquals(array(), $component);
    }

    public function testParse2()
    {
        $component = ExpressionArray::parse(
            new Parser(),
            $this->getTokensList('(expr) +'),
            array(
                'parenthesesDelimited' => true,
            )
        );
        $this->assertEquals(1, count($component));
        $this->assertEquals('(expr)', $component[0]->expr);
    }
}
