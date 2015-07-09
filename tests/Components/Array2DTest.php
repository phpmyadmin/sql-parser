<?php

namespace SqlParser\Tests\Components;

use SqlParser\Parser;
use SqlParser\Components\Array2d;

use SqlParser\Tests\TestCase;

class Array2dTest extends TestCase
{

    public function testParseErr1()
    {
        $parser = new Parser();
        Array2d::parse($parser, $this->getTokensList('(1, 2 +'));
        // TODO: Assert errors.
    }

    public function testParseErr2()
    {
        $parser = new Parser();
        Array2d::parse($parser, $this->getTokensList('(1, 2 TABLE'));
        // TODO: Assert errors.
    }

}
