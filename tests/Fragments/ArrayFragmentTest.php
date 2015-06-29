<?php

namespace SqlParser\Tests\Fragments;

use SqlParser\Fragments\ArrayFragment;

use SqlParser\Tests\TestCase;

class ArrayFragmentTest extends TestCase
{

    public function testBuildRaw()
    {
        $fragment = new ArrayFragment(array('a', 'b'), array());
        $this->assertEquals('(a, b)', ArrayFragment::build($fragment));
    }

    public function testBuildValues()
    {
        $fragment = new ArrayFragment(array(), array('a', 'b'));
        $this->assertEquals('(a, b)', ArrayFragment::build($fragment));
    }
}
