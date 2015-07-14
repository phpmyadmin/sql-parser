<?php

namespace SqlParser\Tests\Components;

use SqlParser\Parser;
use SqlParser\Components\ParameterDefinition;

use SqlParser\Tests\TestCase;

class ParameterDefinitionTest extends TestCase
{

    public function testParse()
    {
        $component = ParameterDefinition::parse(
            new Parser(),
            $this->getTokensList('(a INT, b INT')
        );
        $this->assertEquals('a', $component[0]->name);
        $this->assertEquals('b', $component[1]->name);
    }
}
