<?php

namespace PhpMyAdmin\SqlParser\Tests\Builder;

use PhpMyAdmin\SqlParser\Parser;
use PhpMyAdmin\SqlParser\Tests\TestCase;

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

    public function testBuilderAlias()
    {
        $parser = new Parser(
            'SELECT sgu.id, sgu.email_address FROM `sf_guard_user` sgu '
            . 'RIGHT JOIN `student_course_booking` scb ON sgu.id = scb.user_id '
            . 'WHERE `has_found_course` = \'1\' GROUP BY sgu.id '
            . 'ORDER BY scb.id DESC LIMIT 0,300'
        );
        $stmt = $parser->statements[0];

        $this->assertEquals(
            'SELECT  sgu.id, sgu.email_address FROM `sf_guard_user` AS `sgu` '
            . 'RIGHT JOIN `student_course_booking` AS `scb` ON sgu.id = scb.user_id '
            . 'WHERE `has_found_course` = \'1\' GROUP BY sgu.id '
            . 'ORDER BY scb.id DESC LIMIT 0, 300 ',
            $stmt->build()
        );
    }

    public function testBuilderEndOptions()
    {
        /* Assertion 1 */
        $query = 'SELECT  pid, name2 FROM tablename WHERE pid = 20 FOR UPDATE ';
        $parser = new Parser($query);
        $stmt = $parser->statements[0];

        $this->assertEquals(
            $query,
            $stmt->build()
        );

        /* Assertion 2 */
        $query = 'SELECT  pid, name2 FROM tablename WHERE pid = 20 LOCK IN SHARE MODE ';
        $parser = new Parser($query);
        $stmt = $parser->statements[0];

        $this->assertEquals(
            $query,
            $stmt->build()
        );
    }

    public function testBuilderIntoOptions()
    {
        /* Assertion 1 */
        $query = 'SELECT  a, b, a+b INTO OUTFILE "/tmp/result.txt"'
            . ' COLUMNS TERMINATED BY \',\' OPTIONALLY ENCLOSED BY \'"\''
            . ' LINES TERMINATED BY \'\n\''
            . ' FROM test_table ';
        $parser = new Parser($query);
        $stmt = $parser->statements[0];

        $this->assertEquals(
            $query,
            $stmt->build()
        );
    }

    public function testBuildGroupBy()
    {
        $query = 'SELECT  COUNT(CustomerID), Country FROM Customers GROUP BY Country ';
        $parser = new Parser($query);
        $stmt = $parser->statements[0];

        $this->assertEquals(
            $query,
            $stmt->build()
        );
    }
}
