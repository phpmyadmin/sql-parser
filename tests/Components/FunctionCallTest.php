<?php

declare(strict_types=1);

namespace PhpMyAdmin\SqlParser\Tests\Components;

use PhpMyAdmin\SqlParser\Components\ArrayObj;
use PhpMyAdmin\SqlParser\Components\FunctionCall;
use PhpMyAdmin\SqlParser\Tests\TestCase;

class FunctionCallTest extends TestCase
{
    public function testBuildArray()
    {
        $component = new FunctionCall('func', ['a', 'b']);
        $this->assertEquals('func(a, b)', FunctionCall::build($component));
    }

    public function testBuildArrayObj()
    {
        $component = new FunctionCall('func', new ArrayObj(['a', 'b']));
        $this->assertEquals('func(a, b)', FunctionCall::build($component));
    }
}
