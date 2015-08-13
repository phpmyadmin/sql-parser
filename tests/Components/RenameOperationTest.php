<?php

namespace SqlParser\Tests\Components;

use SqlParser\Parser;
use SqlParser\Components\RenameOperation;

use SqlParser\Tests\TestCase;

class RenameOperationTest extends TestCase
{

    public function testBuild()
    {
        $component = RenameOperation::parse(new Parser(), $this->getTokensList('a TO b, c TO d'));
        $this->assertEquals(RenameOperation::build($component), 'a TO b, c TO d');
    }
}
