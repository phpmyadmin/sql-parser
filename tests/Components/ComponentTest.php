<?php

namespace SqlParser\Tests\Parser;

use SqlParser\Component;
use SqlParser\Parser;
use SqlParser\TokensList;

use SqlParser\Tests\TestCase;

class ComponentTest extends TestCase
{

    /**
     * @expectedException \Exception
     * @expectedExceptionMessage Not implemented yet.
     * @runInSeparateProcess
     * @preserveGlobalState disabled
     */
    public function testParse()
    {
        Component::parse(new Parser(), new TokensList());
    }

    /**
     * @expectedException \Exception
     * @expectedExceptionMessage Not implemented yet.
     */
    public function testBuild()
    {
        Component::build(null);
    }
}
