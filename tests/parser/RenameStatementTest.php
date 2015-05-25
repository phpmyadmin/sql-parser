<?php

class RenameStatementTest extends TestCase
{

    public function testRename()
    {
        $this->runParserTest('parseRename');
    }

    public function testRename2()
    {
        $this->runParserTest('parseRename2');
    }
}
