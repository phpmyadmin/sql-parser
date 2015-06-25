<?php

namespace SqlParser\Tests\Parser;

use SqlParser\Fragment;
use SqlParser\Parser;
use SqlParser\TokensList;

use SqlParser\Tests\TestCase;

class FragmentTest extends TestCase
{

    public function testFragment()
    {
        $this->assertEquals(null, Fragment::parse(new Parser(), new TokensList()));
    }
}
