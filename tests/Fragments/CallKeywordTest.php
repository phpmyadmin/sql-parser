<?php

namespace SqlParser\Tests\Fragments;

use SqlParser\Fragments\ArrayFragment;
use SqlParser\Fragments\CallKeyword;

use SqlParser\Tests\TestCase;

class CallKeywordTest extends TestCase
{

    public function testBuildArray()
    {
        $fragment = new CallKeyword('func', array('a', 'b'));
        $this->assertEquals('func(a, b)', CallKeyword::build($fragment));
    }

    public function testBuildArrayFragment()
    {
        $fragment = new CallKeyword('func', new ArrayFragment(array('a', 'b')));
        $this->assertEquals('func(a, b)', CallKeyword::build($fragment));
    }
}
