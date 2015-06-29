<?php

namespace SqlParser\Tests\Parser;

use SqlParser\Tests\TestCase;

class SelectStatementTest extends TestCase
{

    public function testSelectOptions()
    {
        $parser = $this->runParserTest('parseSelect');
        $stmt = $parser->statements[0];
        $this->assertEquals(10, $stmt->options->has('MAX_STATEMENT_TIME'));
    }

    /**
     * @dataProvider testSelectProvider
     */
    public function testSelect($test)
    {
        $this->runParserTest($test);
    }

    public function testSelectProvider()
    {
        return array(
            array('parseSelect2'),
            array('parseSelectErr1'),
            array('parseSelectNested'),
        );
    }
}
