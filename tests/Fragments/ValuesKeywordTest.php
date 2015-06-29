<?php

namespace SqlParser\Tests\Fragments;

use SqlParser\Parser;
use SqlParser\Fragments\ValuesKeyword;

use SqlParser\Tests\TestCase;

class ValuesKeywordTest extends TestCase
{

    public function testParseErr1()
    {
        $parser = new Parser();
        $fragment = ValuesKeyword::parse($parser, $this->getTokensList('(1, 2 +'));
        // TODO: Assert errors.
    }

    public function testParseErr2()
    {
        $parser = new Parser();
        $fragment = ValuesKeyword::parse($parser, $this->getTokensList('(1, 2 TABLE'));
        // TODO: Assert errors.
    }

}
