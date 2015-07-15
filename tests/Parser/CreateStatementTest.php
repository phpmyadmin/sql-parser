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
            array('parseCreateFunction'),
            array('parseCreateFunctionErr1'),
            array('parseCreateFunctionErr2'),
            array('parseCreateProcedure'),
            array('parseCreateProcedure2'),
            array('parseCreateTable'),
            array('parseCreateTable2'),
            array('parseCreateTable3'),
            array('parseCreateTableErr1'),
            array('parseCreateTableErr2'),
            array('parseCreateTrigger'),
            array('parseCreateUser'),
            array('parseCreateView'),
            array('parseCreateView2'),
        );
    }
}
