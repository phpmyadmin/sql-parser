<?php

class CreateStatementTest extends TestCase
{

    public function testCreateTable()
    {
        $this->runParserTest('parseCreateTable');
    }

    public function testCreateProcedure()
    {
        $this->runParserTest('parseCreateProcedure');
    }

    public function testCreateProcedure2()
    {
        $this->runParserTest('parseCreateProcedure2');
    }

    public function testCreateFunction()
    {
        $this->runParserTest('parseCreateFunction');
    }

    public function testCreateFunctionErr1()
    {
        $this->runParserTest('parseCreateFunctionErr1');
    }
}
