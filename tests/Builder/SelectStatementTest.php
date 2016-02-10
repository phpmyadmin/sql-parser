<?php

namespace SqlParser\Tests\Builder;

use SqlParser\Parser;

use SqlParser\Tests\TestCase;

class SelectStatementTest extends TestCase
{

    public function testBuilder()
    {
        $query = 'SELECT * FROM t1 LEFT JOIN (t2, t3, t4) '
            . 'ON (t2.a=t1.a AND t3.b=t1.b AND t4.c=t1.c)';

        $parser = new Parser($query);
        $stmt = $parser->statements[0];

        $this->assertEquals(
            'SELECT  * FROM t1 LEFT JOIN (t2, t3, t4) '
            . 'ON (t2.a=t1.a AND t3.b=t1.b AND t4.c=t1.c) ',
            $stmt->build()
        );
    }

    public function testBuilderUnion()
    {
        $parser = new Parser('SELECT 1 UNION SELECT 2');
        $stmt = $parser->statements[0];

        $this->assertEquals(
            'SELECT  1 UNION SELECT  2  ',
            $stmt->build()
        );
    }
}
