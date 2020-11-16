<?php

declare(strict_types=1);

namespace PhpMyAdmin\SqlParser\Tests\Parser;

use PhpMyAdmin\SqlParser\Tests\TestCase;

class CreateStatementTest extends TestCase
{
    /**
     * @param mixed $test
     *
     * @dataProvider createProvider
     */
    public function testCreate($test)
    {
        $this->runParserTest($test);
    }

    public function createProvider(): array
    {
        return [
            ['parser/parseCreateDatabase'],
            ['parser/parseCreateDatabaseErr'],
            ['parser/parseCreateFunction'],
            ['parser/parseCreateFunctionErr1'],
            ['parser/parseCreateFunctionErr2'],
            ['parser/parseCreateFunctionErr3'],
            ['parser/parseCreateProcedure'],
            ['parser/parseCreateProcedure2'],
            ['parser/parseCreateSchema'],
            ['parser/parseCreateSchemaErr'],
            ['parser/parseCreateTable'],
            ['parser/parseCreateTable2'],
            ['parser/parseCreateTable3'],
            ['parser/parseCreateTable4'],
            ['parser/parseCreateTable5'],
            ['parser/parseCreateTable6'],
            ['parser/parseCreateTable7'],
            ['parser/parseCreateTableErr1'],
            ['parser/parseCreateTableErr2'],
            ['parser/parseCreateTableErr3'],
            ['parser/parseCreateTableErr4'],
            ['parser/parseCreateTableErr5'],
            ['parser/parseCreateTableSelect'],
            ['parser/parseCreateTableAsSelect'],
            ['parser/parseCreateTableLike'],
            ['parser/parseCreateTableSpatial'],
            ['parser/parseCreateTableTimestampWithPrecision'],
            ['parser/parseCreateTrigger'],
            ['parser/parseCreateUser'],
            ['parser/parseCreateView'],
            ['parser/parseCreateView2'],
            ['parser/parseCreateViewWithoutQuotes'],
            ['parser/parseCreateViewWithQuotes'],
            ['parser/parseCreateViewWithWrongSyntax'],
        ];
    }
}
