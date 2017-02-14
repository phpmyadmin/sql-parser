<?php

namespace PhpMyAdmin\SqlParser\Tests\Parser;

use PhpMyAdmin\SqlParser\Tests\TestCase;

class CallStatementTest extends TestCase
{
    /**
     * @dataProvider testCallProvider
     *
     * @param mixed $test
     */
    public function testCall($test)
    {
        $this->runParserTest($test);
    }

    public function testCallProvider()
    {
        return array(
            array('parser/parseCall'),
            array('parser/parseCall2'),
            array('parser/parseCall3'),
        );
    }
}
