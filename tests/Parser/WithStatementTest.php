<?php

declare(strict_types=1);

namespace PhpMyAdmin\SqlParser\Tests\Parser;

use PhpMyAdmin\SqlParser\Components\WithKeyword;
use PhpMyAdmin\SqlParser\Lexer;
use PhpMyAdmin\SqlParser\Parser;
use PhpMyAdmin\SqlParser\Statements\WithStatement;
use PhpMyAdmin\SqlParser\Tests\TestCase;
use PHPUnit\Framework\Attributes\DataProvider;

class WithStatementTest extends TestCase
{
    #[DataProvider('parseWith')]
    public function testParse(string $test): void
    {
        $this->runParserTest($test);
    }

    /** @return string[][] */
    public static function parseWith(): array
    {
        return [
            ['parser/parseWithStatement'],
            ['parser/parseWithStatement1'],
            ['parser/parseWithStatement2'],
            ['parser/parseWithStatement3'],
            ['parser/parseWithStatement4'],
            ['parser/parseWithStatement5'],
            ['parser/parseWithStatement6'],
            ['parser/parseWithStatement7'],
            ['parser/parseWithStatementErr'],
            ['parser/parseWithStatementErr1'],
            ['parser/parseWithStatementErr2'],
            ['parser/parseWithStatementErr3'],
            ['parser/parseWithStatementErr4'],
            ['parser/parseWithStatementErr5'],
            ['parser/parseWithStatementErr6'],
            ['parser/parseWithStatementErr7'],
            ['parser/parseWithStatementErr8'],
        ];
    }

    public function testWith(): void
    {
        $sql = <<<'SQL'
WITH categories(identifier, name, parent_id) AS (
    SELECT c.identifier, c.name, c.parent_id FROM category c WHERE c.identifier = 'a'
    UNION ALL
    SELECT c.identifier, c.name, c.parent_id FROM categories, category c WHERE c.identifier = categories.parent_id
), foo AS ( SELECT * FROM test )
SELECT * FROM categories
SQL;

        $lexer = new Lexer($sql);

        $lexerErrors = $this->getErrorsAsArray($lexer);
        $this->assertCount(0, $lexerErrors);
        $parser = new Parser($lexer->list);
        $parserErrors = $this->getErrorsAsArray($parser);
        $this->assertCount(0, $parserErrors);
        $this->assertCount(1, $parser->statements);

        // phpcs:disable Generic.Files.LineLength.TooLong
        $expected = <<<'SQL'
WITH categories(identifier, name, parent_id) AS (SELECT c.identifier, c.name, c.parent_id FROM category AS `c` WHERE c.identifier = 'a' UNION ALL SELECT c.identifier, c.name, c.parent_id FROM categories, category AS `c` WHERE c.identifier = categories.parent_id), foo AS (SELECT * FROM test) SELECT * FROM categories
SQL;
        // phpcs:enable
        $this->assertEquals($expected, $parser->statements[0]->build());
    }

    /** @return array<string, array{string, string}> */
    public static function cteNameCases(): array
    {
        return [
            'non-reserved keyword' => ['WITH data AS (SELECT 1) SELECT * FROM data', 'data'],
            'backtick keyword' => ['WITH `data` AS (SELECT 1) SELECT * FROM `data`', 'data'],
            'backtick identifier with space' => ['WITH `my cte` AS (SELECT 1) SELECT * FROM `my cte`', 'my cte'],
        ];
    }

    #[DataProvider('cteNameCases')]
    public function testWithNonReservedOrQuotedName(string $sql, string $expectedName): void
    {
        // https://github.com/phpmyadmin/sql-parser/issues/662
        // A CTE name may be a non-reserved keyword (e.g. "data") or a
        // backtick-quoted identifier; both must be accepted instead of being
        // reported as "The name of the CTE was expected."
        $lexer = new Lexer($sql);
        self::assertCount(0, $this->getErrorsAsArray($lexer));

        $parser = new Parser($lexer->list);
        self::assertCount(0, $this->getErrorsAsArray($parser));
        self::assertCount(1, $parser->statements);

        $statement = $parser->statements[0];
        self::assertInstanceOf(WithStatement::class, $statement);
        self::assertArrayHasKey($expectedName, $statement->withers);
    }

    public function testWithRecursive(): void
    {
        $sql = <<<'SQL'
WITH RECURSIVE number_sequence AS (
    SELECT 1 AS `number`
    UNION ALL
    SELECT `number` + 1
    FROM number_sequence
    WHERE `number` < 5
)
SELECT * FROM number_sequence;
SQL;

        $lexer = new Lexer($sql);

        $lexerErrors = $this->getErrorsAsArray($lexer);
        $this->assertCount(0, $lexerErrors);
        $parser = new Parser($lexer->list);
        $parserErrors = $this->getErrorsAsArray($parser);
        $this->assertCount(0, $parserErrors);
        $this->assertCount(1, $parser->statements);

        // phpcs:disable Generic.Files.LineLength.TooLong
        $expected = <<<'SQL'
WITH RECURSIVE number_sequence AS (SELECT 1 AS `number` UNION ALL SELECT `number`+ 1 FROM number_sequence WHERE `number` < 5) SELECT * FROM number_sequence
SQL;
        // phpcs:enable
        $this->assertEquals($expected, $parser->statements[0]->build());
    }

    public function testWithRecursiveWithers(): void
    {
        $sql = <<<'SQL'
WITH RECURSIVE cte AS
(
    SELECT 1 AS n, CAST('abc' AS CHAR(20)) AS str
    UNION ALL
    SELECT n + 1, CONCAT(str, str) FROM cte WHERE n < 3
), cte2 AS
(
    SELECT 1 AS n, CAST('def' AS CHAR(20)) AS str
    UNION ALL
    SELECT n + 1, CONCAT(str, str) FROM cte WHERE n < 3
)
SELECT * FROM cte UNION SELECT * FROM cte2;
SQL;

        $lexer = new Lexer($sql);

        $lexerErrors = $this->getErrorsAsArray($lexer);
        $this->assertCount(0, $lexerErrors);
        $parser = new Parser($lexer->list);
        $parserErrors = $this->getErrorsAsArray($parser);
        $this->assertCount(0, $parserErrors);
        $this->assertCount(1, $parser->statements);

        // phpcs:disable Generic.Files.LineLength.TooLong
        $expected = <<<'SQL'
WITH RECURSIVE cte AS (SELECT 1 AS `n`, CAST('abc' AS CHAR(20)) AS `str` UNION ALL SELECT n+ 1, CONCAT(str, str) FROM cte WHERE n < 3), cte2 AS (SELECT 1 AS `n`, CAST('def' AS CHAR(20)) AS `str` UNION ALL SELECT n+ 1, CONCAT(str, str) FROM cte WHERE n < 3) SELECT * FROM cte UNION SELECT * FROM cte2
SQL;
        // phpcs:enable
        $this->assertEquals($expected, $parser->statements[0]->build());
    }

    public function testWithHasErrors(): void
    {
        $sql = <<<'SQL'
WITH categories(identifier, name, parent_id) AS (
    SOMETHING * FROM foo
)
SELECT * FROM categories
SQL;

        $lexer = new Lexer($sql);

        $lexerErrors = $this->getErrorsAsArray($lexer);
        $this->assertCount(0, $lexerErrors);
        $parser = new Parser($lexer->list);
        $parserErrors = $this->getErrorsAsArray($parser);
        $this->assertCount(4, $parserErrors);
    }

    public function testWithEmbedParenthesis(): void
    {
        $sql = <<<'SQL'
WITH categories AS (
    SELECT * FROM (SELECT * FROM foo)
)
SELECT * FROM categories
SQL;

        $lexer = new Lexer($sql);
        $lexerErrors = $this->getErrorsAsArray($lexer);
        $this->assertCount(0, $lexerErrors);
        $parser = new Parser($lexer->list);
        $parserErrors = $this->getErrorsAsArray($parser);
        $this->assertCount(0, $parserErrors);

        // phpcs:disable Generic.Files.LineLength.TooLong
        $expected = <<<'SQL'
WITH categories AS (SELECT * FROM (SELECT * FROM foo)) SELECT * FROM categories
SQL;
        // phpcs:enable
        $this->assertEquals($expected, $parser->statements[0]->build());
    }

    public function testWithHasUnclosedParenthesis(): void
    {
        $sql = <<<'SQL'
WITH categories(identifier, name, parent_id) AS (
    SELECT * FROM (SELECT * FROM foo
)
SELECT * FROM categories
SQL;

        $lexer = new Lexer($sql);

        $lexerErrors = $this->getErrorsAsArray($lexer);
        $this->assertCount(0, $lexerErrors);
        $parser = new Parser($lexer->list);
        $parserErrors = $this->getErrorsAsArray($parser);
        $this->assertEquals('A closing bracket was expected.', $parserErrors[0][0]);
    }

    public function testBuildBadWithKeyword(): void
    {
        $this->expectExceptionMessage('No statement inside WITH');
        (new WithKeyword('test'))->build();
    }
}
