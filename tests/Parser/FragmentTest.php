<?php

namespace SqlParser\Tests\Parser;

use SqlParser\Fragment;
use SqlParser\Parser;
use SqlParser\TokensList;
use SqlParser\Fragments\ArrayFragment;

use SqlParser\Tests\TestCase;

class FragmentTest extends TestCase
{

    public function testDummy()
    {
        $this->assertEquals(null, Fragment::parse(new Parser(), new TokensList()));
        $this->assertEquals(null, Fragment::build(new ArrayFragment()));
    }
}
