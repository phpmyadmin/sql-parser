<?php

class InsertStatementTest extends TestCase
{

    public function testInsert()
    {
        $this->runParserTest('parseInsert');
    }
}
