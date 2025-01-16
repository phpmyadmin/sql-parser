<?php

declare(strict_types=1);

namespace PhpMyAdmin\SqlParser\Tests\Parser;

use PhpMyAdmin\SqlParser\Components\WithKeyword;
use PhpMyAdmin\SqlParser\Lexer;
use PhpMyAdmin\SqlParser\Parser;
use PhpMyAdmin\SqlParser\Tests\TestCase;
use stdClass;

class WithStatementTest extends TestCase
{
    /**
     * @dataProvider parseWith
     */
    public function testParse(string $test): void
    {
        $this->runParserTest($test);
    }

    /**
     * @return string[][]
     */
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
        $sql = <<<SQL
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
        $expected = <<<SQL
WITH categories(identifier, name, parent_id) AS (SELECT c.identifier, c.name, c.parent_id FROM category AS `c` WHERE c.identifier = 'a' UNION ALL SELECT c.identifier, c.name, c.parent_id FROM categories, category AS `c` WHERE c.identifier = categories.parent_id), foo AS (SELECT * FROM test) SELECT * FROM categories
SQL;
        // phpcs:enable
        $this->assertEquals($expected, $parser->statements[0]->build());
    }

    public function testWithRecursive(): void
    {
        $sql = <<<SQL
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
        $expected = <<<SQL
WITH RECURSIVE number_sequence AS (SELECT 1 AS `number` UNION ALL SELECT `number`+ 1 FROM number_sequence WHERE `number` < 5) SELECT * FROM number_sequence
SQL;
        // phpcs:enable
        $this->assertEquals($expected, $parser->statements[0]->build());
    }

    public function testWithRecursiveWithers(): void
    {
        $sql = <<<SQL
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
        $expected = <<<SQL
WITH RECURSIVE cte AS (SELECT 1 AS `n`, CAST('abc' AS CHAR(20)) AS `str` UNION ALL SELECT n+ 1, CONCAT(str, str) FROM cte WHERE n < 3), cte2 AS (SELECT 1 AS `n`, CAST('def' AS CHAR(20)) AS `str` UNION ALL SELECT n+ 1, CONCAT(str, str) FROM cte WHERE n < 3) SELECT * FROM cte UNION SELECT * FROM cte2
SQL;
        // phpcs:enable
        $this->assertEquals($expected, $parser->statements[0]->build());
    }

    public function testWithHasErrors(): void
    {
        $sql = <<<SQL
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
        $sql = <<<SQL
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
        $expected = <<<SQL
WITH categories AS (SELECT * FROM (SELECT * FROM foo)) SELECT * FROM categories
SQL;
        // phpcs:enable
        $this->assertEquals($expected, $parser->statements[0]->build());
    }

    public function testWithHasUnclosedParenthesis(): void
    {
        $sql = <<<SQL
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
        $this->assertEquals($parserErrors[0][0], 'A closing bracket was expected.');
    }

    public function testBuildWrongWithKeyword(): void
    {
        $this->expectExceptionMessage('Can not build a component that is not a WithKeyword');
        WithKeyword::build(new stdClass());
    }

    public function testBuildBadWithKeyword(): void
    {
        $this->expectExceptionMessage('No statement inside WITH');
        WithKeyword::build(new WithKeyword('test'));
    }
}
