<?php

namespace SqlParser\Tests\Utils;

use SqlParser\Parser;
use SqlParser\Utils\Query;

use SqlParser\Tests\TestCase;

class QueryTest extends TestCase
{

    /**
     * @dataProvider testGetFlagsProvider
     */
    public function testGetFlags($query, $expected)
    {
        $parser = new Parser($query);
        $this->assertEquals(
            $expected,
            Query::getFlags($parser->statements[0])
        );
    }

    public function testGetFlagsProvider()
    {
        return array(
            array(
                'ALTER TABLE DROP col',
                array('reload' => true)
            ),
            array(
                'CREATE TABLE tbl (id INT)',
                array('reload' => true)
            ),
            array(
                'ANALYZE TABLE tbl',
                array(
                    'is_maint' => true,
                    'is_analyze' => true
                )
            ),
            array(
                'CHECK TABLE tbl',
                array('is_maint' => true)
            ),
            array(
                'DELETE FROM tbl',
                array(
                    'is_delete' => true,
                    'is_affected' => true
                ),
            ),
            array(
                'DROP VIEW v',
                array('reload' => true)
            ),
            array(
                'DROP DATABASE db',
                array(
                    'reload' => true,
                    'drop_database' => true
                )
            ),
            array(
                'EXPLAIN tbl',
                array('is_explain' => true),
            ),
            array(
                'INSERT INTO tbl VALUES (1)',
                array(
                    'is_affected' => true,
                    'is_insert' => true
                )
            ),
            array(
                'REPLACE INTO tbl VALUES (2)',
                array(
                    'is_affected' => true,
                    'is_replace' => true
                )
            ),
            array(
                'SELECT 1',
                array()
            ),
            array(
                'SELECT * FROM tbl',
                array('select_from' => true)
            ),
            array(
                'SELECT DISTINCT * FROM tbl',
                array(
                    'select_from' => true,
                    'distinct' => true
                )
            ),
            array(
                'SELECT * FROM tbl INTO OUTFILE "/tmp/export.txt"',
                array(
                    'select_from' => true,
                    'is_export' => true
                )
            ),
            array(
                'SELECT COUNT(id), SUM(id) FROM tbl',
                array(
                    'select_from' => true,
                    'is_func' => true,
                    'is_count' => true
                )
            ),
            array(
                'SELECT (SELECT "foo")',
                array('is_subquery' => true)
            ),
            array(
                'SHOW CREATE TABLE tbl',
                array('is_show' => true)
            ),
            array(
                'UPDATE tbl SET id = 1',
                array('is_affected' => true)
            )
        );
    }

}
