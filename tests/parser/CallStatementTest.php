<?php

class CallStatementTest extends TestCase
{

    public function testCall()
    {
        $this->runParserTest('parseCall');
    }

    public function testCall2()
    {
        $this->runParserTest('parseCall2');
    }
}
