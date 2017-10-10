<?php

namespace PhpMyAdmin\SqlParser\Tests\Parser;

use PhpMyAdmin\SqlParser\Tests\TestCase;

class CreateStatementTest extends TestCase
{
    /**
     * @dataProvider testCreateProvider
     *
     * @param mixed $test
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
            array('parser/parseCreateFunctionErr3'),
            array('parser/parseCreateProcedure'),
            array('parser/parseCreateProcedure2'),
            array('parser/parseCreateTable'),
            array('parser/parseCreateTable2'),
            array('parser/parseCreateTable3'),
            array('parser/parseCreateTable4'),
            array('parser/parseCreateTable5'),
            array('parser/parseCreateTable6'),
            array('parser/parseCreateTable7'),
            array('parser/parseCreateTableErr1'),
            array('parser/parseCreateTableErr2'),
            array('parser/parseCreateTableErr3'),
            array('parser/parseCreateTableErr4'),
            array('parser/parseCreateTableErr5'),
            array('parser/parseCreateTableSelect'),
            array('parser/parseCreateTableAsSelect'),
            array('parser/parseCreateTableLike'),
            array('parser/parseCreateTableSpatial'),
            array('parser/parseCreateTableTimestampWithPrecision'),
            array('parser/parseCreateTrigger'),
            array('parser/parseCreateUser'),
            array('parser/parseCreateView'),
            array('parser/parseCreateView2'),
            array('parser/parseCreateViewWithoutQuotes'),
            array('parser/parseCreateViewWithQuotes'),
        );
    }
}
