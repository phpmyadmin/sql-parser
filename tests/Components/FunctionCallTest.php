<?php

namespace SqlParser\Tests\Components;

use SqlParser\Components\ArrayObj;
use SqlParser\Components\FunctionCall;

use SqlParser\Tests\TestCase;

class FunctionCallTest extends TestCase
{

    public function testBuildArray()
    {
        $component = new FunctionCall('func', array('a', 'b'));
        $this->assertEquals('func(a, b)', FunctionCall::build($component));
    }

    public function testBuildArrayObj()
    {
        $component = new FunctionCall('func', new ArrayObj(array('a', 'b')));
        $this->assertEquals('func(a, b)', FunctionCall::build($component));
    }
}
