<?php

namespace SqlParser\Tests\Builder;

use SqlParser\Parser;

use SqlParser\Tests\TestCase;

class TransactionStatementTest extends TestCase
{

    public function testBuilder()
    {
        $query = 'START TRANSACTION;' .
            'SELECT @A:=SUM(salary) FROM table1 WHERE type=1;' .
            'UPDATE table2 SET summary=@A WHERE type=1;' .
            'COMMIT;';

        $parser = new Parser($query);
        $stmt = $parser->statements[0];

        $this->assertEquals(
            'START TRANSACTION;' .
            'SELECT  @A:=SUM(salary) FROM table1 WHERE type=1 ;' .
            'UPDATE  table2 SET summary = @A WHERE type=1 ;' .
            'COMMIT',
            $stmt->build()
        );
    }
}
