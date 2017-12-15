<?php

namespace PhpMyAdmin\SqlParser\Tests\Components;

use PhpMyAdmin\SqlParser\Components\Key;
use PhpMyAdmin\SqlParser\Parser;
use PhpMyAdmin\SqlParser\Tests\TestCase;

class KeyTest extends TestCase
{
    public function testParse()
    {
        $component = Key::parse(
            new Parser(),
            $this->getTokensList('')
        );
        $this->assertNull($component->name);
    }
}
