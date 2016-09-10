<?php

namespace SqlParser\Tests\Builder;

use SqlParser\Parser;

use SqlParser\Tests\TestCase;

class InsertStatementTest extends TestCase
{

    public function testBuilder()
    {
        /* Assertion 1 */
        $parser = new Parser(
            'INSERT INTO tbl(`col1`, `col2`, `col3`) VALUES (1, "str", 3.14)'
        );
        $stmt = $parser->statements[0];
        $this->assertEquals(
            'INSERT  INTO tbl(`col1`, `col2`, `col3`) VALUES (1, "str", 3.14)',
            $stmt->build()
        );


        /* Assertion 2 */
        /* Reserved keywords (with backqoutes as field name) */
        $parser = new Parser(
            'INSERT INTO tbl(`order`) VALUES (1)'
        );
        $stmt = $parser->statements[0];
        $this->assertEquals(
            'INSERT  INTO tbl(`order`) VALUES (1)',
            $stmt->build()
        );
    }
}
