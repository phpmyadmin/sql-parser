<?php

class SelectStatementTest extends TestCase
{

    public function testSelect()
    {
        $parser = $this->runParserTest('parseSelect');
        $stmt = $parser->statements[0];
        $this->assertEquals(10, $stmt->options->has('MAX_STATEMENT_TIME'));
    }

    public function testSelectErr1()
    {
        $this->runParserTest('parseSelectErr1');
    }

    public function testSelectNested()
    {
        $this->runParserTest('parseSelectNested');
    }
}
