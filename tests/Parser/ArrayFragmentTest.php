<?php

namespace SqlParser\Tests\Parser;

use SqlParser\Tests\TestCase;

class ArrayFragmentTest extends TestCase
{

    /**
     * @dataProvider testArrayProvider
     */
    public function testArray($test)
    {
        $this->runParserTest($test);
    }

    public function testArrayProvider()
    {
        return array(
            array('parseArrayErr1'),
            array('parseArrayErr2'),
            array('parseArrayErr3'),
        );
    }
}
