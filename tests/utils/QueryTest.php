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
                'CALL test()',
                array('is_procedure' => true)
            ),
            array(
                'CREATE TABLE tbl (id INT)',
                array('reload' => true)
            ),
            array(
                'CHECK TABLE tbl',
                array('is_maint' => true)
            ),
            array(
                'DELETE FROM tbl',
                array(
                    'is_affected' => true,
                    'is_delete' => true
                ),
            ),
            array(
                'DROP VIEW v',
                array('reload' => true)
            ),
            array(
                'DROP DATABASE db',
                array(
                    'drop_database' => true,
                    'reload' => true
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
                array('is_select' => true)
            ),
            array(
                'SELECT * FROM tbl',
                array(
                    'is_select' => true,
                    'select_from' => true
                )
            ),
            array(
                'SELECT DISTINCT * FROM tbl',
                array(
                    'distinct' => true,
                    'is_select' => true,
                    'select_from' => true
                )
            ),
            array(
                'SELECT * FROM actor GROUP BY actor_id',
                array(
                    'is_group' => true,
                    'is_select' => true,
                    'select_from' => true
                )
            ),
            array(
                'SELECT col1, col2 FROM table1 PROCEDURE ANALYSE(10, 2000);',
                array(
                    'is_analyse' => true,
                    'is_select' => true,
                    'select_from' => true
                )
            ),
            array(
                'SELECT * FROM tbl INTO OUTFILE "/tmp/export.txt"',
                array(
                    'is_export' => true,
                    'is_select' => true,
                    'select_from' => true
                )
            ),
            array(
                'SELECT COUNT(id), SUM(id) FROM tbl',
                array(
                    'is_count' => true,
                    'is_func' => true,
                    'is_select' => true,
                    'select_from' => true
                )
            ),
            array(
                'SELECT (SELECT "foo")',
                array(
                    'is_select' => true,
                    'is_subquery' => true
                )
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

    public function testGetAll()
    {
        $query = 'SELECT *, actor.actor_id, sakila2.film.*
            FROM sakila2.city, sakila2.film, actor';
        $parser = new Parser($query);
        $this->assertEquals(
            array_merge(
                Query::getFlags($parser->statements[0], true),
                array(
                    'parser' => $parser,
                    'statement' => $parser->statements[0],
                    'tables' => array(
                        array('actor', null),
                        array('film', 'sakila2')
                    )
                )
            ),
            Query::getAll($query)
        );
    }

    public function testGetAllEmpty()
    {
        $this->assertEquals(array(), Query::getAll(''));
    }

}
