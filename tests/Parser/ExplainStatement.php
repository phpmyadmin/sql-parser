<?php

namespace SqlParser\Tests\Parser;

use SqlParser\Tests\TestCase;

class ExplainStatementTest extends TestCase
{

    /**
     * @dataProvider testExplainProvider
     */
    public function testExplain($test)
    {
        $this->runParserTest($test);
    }

    public function testExplainProvider()
    {
        return array(
            array('parser/parseExplain'),
        );
    }
}
