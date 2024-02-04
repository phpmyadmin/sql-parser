<?php

declare(strict_types=1);

namespace PhpMyAdmin\SqlParser\Tests\Utils;

use PhpMyAdmin\SqlParser\Parser;
use PhpMyAdmin\SqlParser\Tests\TestCase;
use PhpMyAdmin\SqlParser\Utils\Query;
use PhpMyAdmin\SqlParser\Utils\StatementFlags;
use PhpMyAdmin\SqlParser\Utils\StatementType;
use PHPUnit\Framework\Attributes\DataProvider;

class QueryTest extends TestCase
{
    /**
     * @psalm-param non-empty-string $query
     * @psalm-param array<key-of<properties-of<StatementFlags>>, bool|StatementType|null> $expected
     */
    #[DataProvider('getFlagsProvider')]
    public function testGetFlags(string $query, array $expected): void
    {
        $parser = new Parser($query);
        $flags = new StatementFlags();
        foreach ($expected as $property => $expectedValue) {
            $flags->$property = $expectedValue;
        }

        $this->assertEquals($flags, Query::getFlags($parser->statements[0]));
    }

    /** @psalm-return list<array{non-empty-string, array<key-of<properties-of<StatementFlags>>, bool|StatementType|null>}> */
    public static function getFlagsProvider(): array
    {
        return [
            [
                'ALTER TABLE DROP col',
                [
                    'reload' => true,
                    'queryType' => StatementType::Alter,
                ],
            ],
            [
                'CALL test()',
                [
                    'isProcedure' => true,
                    'queryType' => StatementType::Call,
                ],
            ],
            [
                'CREATE TABLE tbl (id INT)',
                [
                    'reload' => true,
                    'queryType' => StatementType::Create,
                ],
            ],
            [
                'CHECK TABLE tbl',
                [
                    'isMaint' => true,
                    'queryType' => StatementType::Check,
                ],
            ],
            [
                'DELETE FROM tbl',
                [
                    'isAffected' => true,
                    'isDelete' => true,
                    'queryType' => StatementType::Delete,
                ],
            ],
            [
                'DROP VIEW v',
                [
                    'reload' => true,
                    'queryType' => StatementType::Drop,
                ],
            ],
            [
                'DROP DATABASE db',
                [
                    'dropDatabase' => true,
                    'reload' => true,
                    'queryType' => StatementType::Drop,
                ],
            ],
            [
                'EXPLAIN tbl',
                [
                    'isExplain' => true,
                    'queryType' => StatementType::Explain,
                ],
            ],
            [
                'LOAD DATA INFILE \'/tmp/test.txt\' INTO TABLE test',
                [
                    'isAffected' => true,
                    'isInsert' => true,
                    'queryType' => StatementType::Load,
                ],
            ],
            [
                'INSERT INTO tbl VALUES (1)',
                [
                    'isAffected' => true,
                    'isInsert' => true,
                    'queryType' => StatementType::Insert,
                ],
            ],
            [
                'REPLACE INTO tbl VALUES (2)',
                [
                    'isAffected' => true,
                    'isReplace' => true,
                    'isInsert' => true,
                    'queryType' => StatementType::Replace,
                ],
            ],
            [
                'SELECT 1',
                [
                    'isSelect' => true,
                    'queryType' => StatementType::Select,
                ],
            ],
            [
                'SELECT * FROM tbl',
                [
                    'isSelect' => true,
                    'selectFrom' => true,
                    'queryType' => StatementType::Select,
                ],
            ],
            [
                'SELECT DISTINCT * FROM tbl LIMIT 0, 10 ORDER BY id',
                [
                    'distinct' => true,
                    'isSelect' => true,
                    'selectFrom' => true,
                    'limit' => true,
                    'order' => true,
                    'queryType' => StatementType::Select,
                ],
            ],
            [
                'SELECT * FROM actor GROUP BY actor_id',
                [
                    'isGroup' => true,
                    'isSelect' => true,
                    'selectFrom' => true,
                    'group' => true,
                    'queryType' => StatementType::Select,
                ],
            ],
            [
                'SELECT col1, col2 FROM table1 PROCEDURE ANALYSE(10, 2000);',
                [
                    'isAnalyse' => true,
                    'isSelect' => true,
                    'selectFrom' => true,
                    'queryType' => StatementType::Select,
                ],
            ],
            [
                'SELECT * FROM tbl INTO OUTFILE "/tmp/export.txt"',
                [
                    'isExport' => true,
                    'isSelect' => true,
                    'selectFrom' => true,
                    'queryType' => StatementType::Select,
                ],
            ],
            [
                'SELECT COUNT(id), SUM(id) FROM tbl',
                [
                    'isCount' => true,
                    'isFunc' => true,
                    'isSelect' => true,
                    'selectFrom' => true,
                    'queryType' => StatementType::Select,
                ],
            ],
            [
                'SELECT (SELECT "foo")',
                [
                    'isSelect' => true,
                    'isSubQuery' => true,
                    'queryType' => StatementType::Select,
                ],
            ],
            [
                'SELECT * FROM customer HAVING store_id = 2;',
                [
                    'isSelect' => true,
                    'selectFrom' => true,
                    'isGroup' => true,
                    'having' => true,
                    'queryType' => StatementType::Select,
                ],
            ],
            [
                'SELECT * FROM table1 INNER JOIN table2 ON table1.id=table2.id;',
                [
                    'isSelect' => true,
                    'selectFrom' => true,
                    'join' => true,
                    'queryType' => StatementType::Select,
                ],
            ],
            [
                'SHOW CREATE TABLE tbl',
                [
                    'isShow' => true,
                    'queryType' => StatementType::Show,
                ],
            ],
            [
                'UPDATE tbl SET id = 1',
                [
                    'isAffected' => true,
                    'queryType' => StatementType::Update,
                ],
            ],
            [
                'ANALYZE TABLE tbl',
                [
                    'isMaint' => true,
                    'queryType' => StatementType::Analyze,
                ],
            ],
            [
                'CHECKSUM TABLE tbl',
                [
                    'isMaint' => true,
                    'queryType' => StatementType::Checksum,
                ],
            ],
            [
                'OPTIMIZE TABLE tbl',
                [
                    'isMaint' => true,
                    'queryType' => StatementType::Optimize,
                ],
            ],
            [
                'REPAIR TABLE tbl',
                [
                    'isMaint' => true,
                    'queryType' => StatementType::Repair,
                ],
            ],
            [
                '(SELECT a FROM t1 WHERE a=10 AND B=1 ORDER BY a LIMIT 10) ' .
                'UNION ' .
                '(SELECT a FROM t2 WHERE a=11 AND B=2 ORDER BY a LIMIT 10);',
                [
                    'isSelect' => true,
                    'selectFrom' => true,
                    'limit' => true,
                    'order' => true,
                    'union' => true,
                    'queryType' => StatementType::Select,
                ],
            ],
            [
                'SELECT * FROM orders AS ord WHERE 1',
                [
                    'queryType' => StatementType::Select,
                    'isSelect' => true,
                    'selectFrom' => true,
                ],
            ],
            [
                'SET NAMES \'latin\'',
                ['queryType' => StatementType::Set],
            ],
        ];
    }

    public function testGetFlagsWithEmptyString(): void
    {
        $statementInfo = Query::getAll('');
        self::assertEquals(new Parser(''), $statementInfo['parser']);
        self::assertNull($statementInfo['statement']);
        self::assertSame([], $statementInfo['select_tables']);
        self::assertSame([], $statementInfo['select_expr']);
        $flags = $statementInfo['flags'];
        self::assertFalse($flags->distinct);
        self::assertFalse($flags->dropDatabase);
        self::assertFalse($flags->group);
        self::assertFalse($flags->having);
        self::assertFalse($flags->isAffected);
        self::assertFalse($flags->isAnalyse);
        self::assertFalse($flags->isCount);
        /** @psalm-suppress DeprecatedProperty */
        self::assertFalse($flags->isDelete);
        /** @psalm-suppress DeprecatedProperty */
        self::assertFalse($flags->isExplain);
        self::assertFalse($flags->isExport);
        self::assertFalse($flags->isFunc);
        self::assertFalse($flags->isGroup);
        self::assertFalse($flags->isInsert);
        self::assertFalse($flags->isMaint);
        self::assertFalse($flags->isProcedure);
        /** @psalm-suppress DeprecatedProperty */
        self::assertFalse($flags->isReplace);
        /** @psalm-suppress DeprecatedProperty */
        self::assertFalse($flags->isSelect);
        /** @psalm-suppress DeprecatedProperty */
        self::assertFalse($flags->isShow);
        self::assertFalse($flags->isSubQuery);
        self::assertFalse($flags->join);
        self::assertFalse($flags->limit);
        self::assertFalse($flags->offset);
        self::assertFalse($flags->order);
        self::assertNull($flags->queryType);
        self::assertFalse($flags->reload);
        self::assertFalse($flags->selectFrom);
        self::assertFalse($flags->union);
    }

    public function testGetAll(): void
    {
        $query = 'SELECT *, actor.actor_id, sakila2.film.* FROM sakila2.city, sakila2.film, actor';
        $parser = new Parser($query);
        $expected = [
            'parser' => $parser,
            'statement' => $parser->statements[0],
            'flags' => Query::getFlags($parser->statements[0]),
            'select_tables' => [['actor', null], ['film', 'sakila2']],
            'select_expr' => ['*'],
        ];
        $this->assertEquals($expected, Query::getAll($query));

        $query = 'SELECT * FROM sakila.actor, film';
        $parser = new Parser($query);
        $expected = [
            'parser' => $parser,
            'statement' => $parser->statements[0],
            'flags' => Query::getFlags($parser->statements[0]),
            'select_tables' => [['actor', 'sakila'], ['film', null]],
            'select_expr' => ['*'],
        ];
        $this->assertEquals($expected, Query::getAll($query));

        $query = 'SELECT a.actor_id FROM sakila.actor AS a, film';
        $parser = new Parser($query);
        $expected = [
            'parser' => $parser,
            'statement' => $parser->statements[0],
            'flags' => Query::getFlags($parser->statements[0]),
            'select_tables' => [['actor', 'sakila']],
            'select_expr' => [],
        ];
        $this->assertEquals($expected, Query::getAll($query));

        $query = 'SELECT CASE WHEN 2 IS NULL THEN "this is true" ELSE "this is false" END';
        $parser = new Parser($query);
        $expected = [
            'parser' => $parser,
            'statement' => $parser->statements[0],
            'flags' => Query::getFlags($parser->statements[0]),
            'select_tables' => [],
            'select_expr' => ['CASE WHEN 2 IS NULL THEN "this is true" ELSE "this is false" END'],
        ];
        $this->assertEquals($expected, Query::getAll($query));
    }

    /** @param string[] $expected */
    #[DataProvider('getTablesProvider')]
    public function testGetTables(string $query, array $expected): void
    {
        $parser = new Parser($query);
        $this->assertEquals(
            $expected,
            Query::getTables($parser->statements[0]),
        );
    }

    /**
     * @return array<int, array<int, string|string[]>>
     * @psalm-return list<array{string, string[]}>
     */
    public static function getTablesProvider(): array
    {
        return [
            [
                'INSERT INTO tbl(`id`, `name`) VALUES (1, "Name")',
                ['`tbl`'],
            ],
            [
                'INSERT INTO 0tbl(`id`, `name`) VALUES (1, "Name")',
                ['`0tbl`'],
            ],
            [
                'UPDATE tbl SET id = 0',
                ['`tbl`'],
            ],
            [
                'UPDATE 0tbl SET id = 0',
                ['`0tbl`'],
            ],
            [
                'DELETE FROM tbl WHERE id < 10',
                ['`tbl`'],
            ],
            [
                'DELETE FROM 0tbl WHERE id < 10',
                ['`0tbl`'],
            ],
            [
                'TRUNCATE tbl',
                ['`tbl`'],
            ],
            [
                'DROP VIEW v',
                [],
            ],
            [
                'DROP TABLE tbl1, tbl2',
                [
                    '`tbl1`',
                    '`tbl2`',
                ],
            ],
            [
                'RENAME TABLE a TO b, c TO d',
                [
                    '`a`',
                    '`c`',
                ],
            ],
        ];
    }

    public function testGetClause(): void
    {
        /* Assertion 1 */
        $parser = new Parser(
            'SELECT c.city_id, c.country_id ' .
            'FROM `city` ' .
            'WHERE city_id < 1 /* test */' .
            'ORDER BY city_id ASC ' .
            'LIMIT 0, 1 ' .
            'INTO OUTFILE "/dev/null"',
        );
        $this->assertNotNull($parser->list);
        $this->assertEquals(
            '0, 1 INTO OUTFILE "/dev/null"',
            Query::getClause(
                $parser->statements[0],
                $parser->list,
                'LIMIT',
                0,
            ),
        );
        // Assert it returns all clauses between FROM and LIMIT
        $this->assertEquals(
            'WHERE city_id < 1 ORDER BY city_id ASC',
            Query::getClause(
                $parser->statements[0],
                $parser->list,
                'FROM',
                'LIMIT',
            ),
        );
        // Assert it returns all clauses between SELECT and LIMIT
        $this->assertEquals(
            'FROM `city` WHERE city_id < 1 ORDER BY city_id ASC',
            Query::getClause(
                $parser->statements[0],
                $parser->list,
                'LIMIT',
                'SELECT',
            ),
        );

        /* Assertion 2 */
        $parser = new Parser(
            'DELETE FROM `renewal` ' .
            'WHERE number = "1DB" AND actionDate <= CURRENT_DATE() ' .
            'ORDER BY id ASC ' .
            'LIMIT 1',
        );
        $this->assertNotNull($parser->list);
        $this->assertEquals(
            'number = "1DB" AND actionDate <= CURRENT_DATE()',
            Query::getClause(
                $parser->statements[0],
                $parser->list,
                'WHERE',
            ),
        );
        $this->assertEquals(
            '1',
            Query::getClause(
                $parser->statements[0],
                $parser->list,
                'LIMIT',
            ),
        );
        $this->assertEquals(
            'id ASC',
            Query::getClause(
                $parser->statements[0],
                $parser->list,
                'ORDER BY',
            ),
        );

        /* Assertion 3 */
        $parser = new Parser(
            'UPDATE `renewal` SET `some_column` = 1 ' .
            'WHERE number = "1DB" AND actionDate <= CURRENT_DATE() ' .
            'ORDER BY id ASC ' .
            'LIMIT 1',
        );
        $this->assertNotNull($parser->list);
        $this->assertEquals(
            'number = "1DB" AND actionDate <= CURRENT_DATE()',
            Query::getClause(
                $parser->statements[0],
                $parser->list,
                'WHERE',
            ),
        );
        $this->assertEquals(
            '1',
            Query::getClause(
                $parser->statements[0],
                $parser->list,
                'LIMIT',
            ),
        );
        $this->assertEquals(
            'id ASC',
            Query::getClause(
                $parser->statements[0],
                $parser->list,
                'ORDER BY',
            ),
        );
    }

    public function testReplaceClause(): void
    {
        $parser = new Parser('SELECT *, (SELECT 1) FROM film LIMIT 0, 10;');
        $this->assertNotNull($parser->list);
        $this->assertEquals(
            'SELECT *, (SELECT 1) FROM film WHERE film_id > 0 LIMIT 0, 10',
            Query::replaceClause(
                $parser->statements[0],
                $parser->list,
                'WHERE film_id > 0',
            ),
        );

        $parser = new Parser(
            'select supplier.city, supplier.id from supplier '
            . 'union select customer.city, customer.id from customer',
        );
        $this->assertNotNull($parser->list);
        $this->assertEquals(
            'select supplier.city, supplier.id from supplier '
            . 'union select customer.city, customer.id from customer'
            . ' ORDER BY city ',
            Query::replaceClause(
                $parser->statements[0],
                $parser->list,
                'ORDER BY city',
            ),
        );
    }

    public function testReplaceClauseOnlyKeyword(): void
    {
        $parser = new Parser('SELECT *, (SELECT 1) FROM film LIMIT 0, 10');
        $this->assertNotNull($parser->list);
        $this->assertEquals(
            ' SELECT SQL_CALC_FOUND_ROWS *, (SELECT 1) FROM film LIMIT 0, 10',
            Query::replaceClause(
                $parser->statements[0],
                $parser->list,
                'SELECT SQL_CALC_FOUND_ROWS',
                null,
                true,
            ),
        );
    }

    public function testReplaceNonExistingPart(): void
    {
        $parser = new Parser('ALTER TABLE `sale_mast` OPTIMIZE PARTITION p3');
        $this->assertNotNull($parser->list);
        $this->assertEquals(
            '  ALTER TABLE `sale_mast` OPTIMIZE PARTITION p3',
            Query::replaceClause(
                $parser->statements[0],
                $parser->list,
                'ORDER BY',
                '',
            ),
        );
    }

    public function testReplaceClauses(): void
    {
        $parser = new Parser('SELECT *, (SELECT 1) FROM film LIMIT 0, 10;');
        $this->assertNotNull($parser->list);
        $this->assertSame('', Query::replaceClauses($parser->statements[0], $parser->list, []));
        $this->assertEquals(
            'SELECT *, (SELECT 1) FROM film WHERE film_id > 0 LIMIT 0, 10',
            Query::replaceClauses(
                $parser->statements[0],
                $parser->list,
                [
                    [
                        'WHERE',
                        'WHERE film_id > 0',
                    ],
                ],
            ),
        );

        $parser = new Parser(
            'SELECT c.city_id, c.country_id ' .
            'INTO OUTFILE "/dev/null" ' .
            'FROM `city` ' .
            'WHERE city_id < 1 ' .
            'ORDER BY city_id ASC ' .
            'LIMIT 0, 1 ',
        );
        $this->assertNotNull($parser->list);
        $this->assertEquals(
            'SELECT c.city_id, c.country_id ' .
            'INTO OUTFILE "/dev/null" ' .
            'FROM city AS c   ' .
            'ORDER BY city_id ASC ' .
            'LIMIT 0, 10 ',
            Query::replaceClauses(
                $parser->statements[0],
                $parser->list,
                [
                    [
                        'FROM',
                        'FROM city AS c',
                    ],
                    [
                        'WHERE',
                        '',
                    ],
                    [
                        'LIMIT',
                        'LIMIT 0, 10',
                    ],
                ],
            ),
        );
    }

    public function testGetFirstStatement(): void
    {
        $query = 'USE saki';
        $delimiter = null;
        [$statement, $query, $delimiter] = Query::getFirstStatement($query, $delimiter);
        $this->assertNull($statement);
        $this->assertEquals('USE saki', $query);
        $this->assertNull($delimiter);

        $query = 'USE sakila; ' .
            '/*test comment*/' .
            'SELECT * FROM actor; ' .
            'DELIMITER $$ ' .
            'UPDATE actor SET last_name = "abc"$$' .
            '/*!SELECT * FROM actor WHERE last_name = "abc"*/$$';
        $delimiter = null;

        [$statement, $query, $delimiter] = Query::getFirstStatement($query, $delimiter);
        $this->assertEquals('USE sakila;', $statement);

        [$statement, $query, $delimiter] = Query::getFirstStatement($query, $delimiter);
        $this->assertEquals('SELECT * FROM actor;', $statement);

        [$statement, $query, $delimiter] = Query::getFirstStatement($query, $delimiter);
        $this->assertEquals('DELIMITER $$', $statement);
        $this->assertEquals('$$', $delimiter);

        [$statement, $query, $delimiter] = Query::getFirstStatement($query, $delimiter);
        $this->assertEquals('UPDATE actor SET last_name = "abc"$$', $statement);

        [$statement, $query, $delimiter] = Query::getFirstStatement($query, $delimiter);
        $this->assertEquals('SELECT * FROM actor WHERE last_name = "abc"$$', $statement);
        $this->assertSame('', $query);
        $this->assertSame('$$', $delimiter);
    }
}
