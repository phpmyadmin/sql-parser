<?php

class UpdateStatementTest extends TestCase
{

    public function testUpdate()
    {
        $this->runParserTest('parseUpdate');
    }

    public function testUpdate2()
    {
        $this->runParserTest('parseUpdate2');
    }
}
