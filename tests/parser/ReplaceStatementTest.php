<?php

class ReplaceStatementTest extends TestCase
{

    public function testReplace()
    {
        $this->runParserTest('parseReplace');
    }

    public function testReplace2()
    {
        $this->runParserTest('parseReplace2');
    }
}
