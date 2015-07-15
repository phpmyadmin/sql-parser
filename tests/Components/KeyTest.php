<?php

namespace SqlParser\Tests\Components;

use SqlParser\Parser;
use SqlParser\Components\Key;

use SqlParser\Tests\TestCase;

class KeyTest extends TestCase
{

    public function testParse()
    {
        $component = Key::parse(
            new Parser(),
            $this->getTokensList('')
        );
        $this->assertEquals(null, $component->name);
    }
}
