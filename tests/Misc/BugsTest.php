<?php

namespace SqlParser\Tests\Parser;

use SqlParser\Tests\TestCase;

class BugsTest extends TestCase
{

    /**
     * @dataProvider testBugProvider
     */
    public function testBug($test)
    {
        $this->runParserTest($test);
    }

    public function testBugProvider()
    {
        return array(
            array('bugs/gh9'),
            array('bugs/gh14'),
            array('bugs/gh16'),
            array('bugs/pma11800'),
            array('bugs/pma11836'),
            array('bugs/pma11843'),
            array('bugs/pma11867'),
            array('bugs/pma11879'),
        );
    }
}
