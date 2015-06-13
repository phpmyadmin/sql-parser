<?php

namespace SqlParser\Tests\Parser;

use SqlParser\Tests\TestCase;

class CreateStatementTest extends TestCase
{

    /**
     * @dataProvider testCreateProvider
     */
    public function testCreate($test)
    {
        $this->runParserTest($test);
    }

    public function testCreateProvider()
    {
        return array(
            array('parseCreateTable'),
            array('parseCreateProcedure'),
            array('parseCreateProcedure2'),
            array('parseCreateFunction'),
            array('parseCreateFunctionErr1'),
        );
    }
}
