<?php

namespace SqlParser\Tests\Components;

use SqlParser\Parser;
use SqlParser\Components\Expression;
use SqlParser\Components\Reference;

use SqlParser\Tests\TestCase;

class ReferenceTest extends TestCase
{

    public function testParse()
    {
        $component = Reference::parse(new Parser(), $this->getTokensList('tbl (id)'));
        $this->assertEquals('tbl', $component->table->table);
        $this->assertEquals(array('id'), $component->columns);
    }

    public function testBuild()
    {
        $component = new Reference(new Expression('`tbl`'), array('id'));
        $this->assertEquals('`tbl` (`id`)', Reference::build($component));
    }
}
