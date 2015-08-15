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
            array('parser/parseCreateFunction'),
            array('parser/parseCreateFunctionErr1'),
            array('parser/parseCreateFunctionErr2'),
            array('parser/parseCreateProcedure'),
            array('parser/parseCreateProcedure2'),
            array('parser/parseCreateTable'),
            array('parser/parseCreateTable2'),
            array('parser/parseCreateTable3'),
            array('parser/parseCreateTable4'),
            array('parser/parseCreateTableErr1'),
            array('parser/parseCreateTableErr2'),
            array('parser/parseCreateTrigger'),
            array('parser/parseCreateUser'),
            array('parser/parseCreateView'),
            array('parser/parseCreateView2'),
        );
    }
}
