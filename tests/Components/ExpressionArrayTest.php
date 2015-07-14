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
                'noBrackets' => true,
            )
        );
        $this->assertEquals(array(), $component);
    }
}
