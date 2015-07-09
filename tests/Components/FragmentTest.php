<?php

namespace SqlParser\Tests\Parser;

use SqlParser\Component;
use SqlParser\Parser;
use SqlParser\TokensList;
use SqlParser\Components\ArrayObj;

use SqlParser\Tests\TestCase;

class ComponentTest extends TestCase
{

    public function testDummy()
    {
        $this->assertEquals(null, Component::parse(new Parser(), new TokensList()));
        $this->assertEquals(null, Component::build(new ArrayObj()));
    }
}
