<?php

namespace SqlParser\Tests\Parser;

use SqlParser\Tests\TestCase;

class ReplaceStatementTest extends TestCase
{

    /**
     * @dataProvider testReplaceProvider
     */
    public function testReplace($test)
    {
        $this->runParserTest($test);
    }

    public function testReplaceProvider()
    {
        return array(
            array('parser/parseReplace'),
            array('parser/parseReplace2'),
        );
    }
}
