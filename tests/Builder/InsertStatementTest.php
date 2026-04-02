<?php

declare(strict_types=1);

namespace PhpMyAdmin\SqlParser\Tests\Builder;

use Generator;
use PhpMyAdmin\SqlParser\Context;
use PhpMyAdmin\SqlParser\Parser;
use PhpMyAdmin\SqlParser\Tests\TestCase;

class InsertStatementTest extends TestCase
{
    /** @var int */
    private $sqlMode;

    public function setUp(): void
    {
        parent::setUp();

        $this->sqlMode = Context::getMode();
    }

    protected function tearDown(): void
    {
        Context::setMode($this->sqlMode);

        parent::tearDown();
    }

    /** @dataProvider providerForTestBuilder */
    public function testBuilder(string $sql): void
    {
        $parser = new Parser($sql);
        $stmt = $parser->statements[0];
        self::assertEquals($sql, $stmt->build());
    }

    /** @return Generator<string, list<string>> */
    public function providerForTestBuilder(): Generator
    {
        yield 'INSERT ... VALUES ...' => ['INSERT INTO tbl(`col1`, `col2`, `col3`) VALUES (1, "str", 3.14)'];

        yield 'Reserved keywords (with backquotes as field name)' => ['INSERT INTO tbl(`order`) VALUES (1)'];

        yield 'INSERT ... SET ...' => ['INSERT INTO tbl SET FOO = 1'];

        yield 'INSERT ... SELECT ... ' => ['INSERT INTO tbl SELECT * FROM bar'];

        yield 'INSERT ... ON DUPLICATE KEY UPDATE ...' =>
            ['INSERT INTO tbl SELECT * FROM bar ON DUPLICATE KEY UPDATE baz = 1'];

        yield 'INSERT [OPTIONS] INTO ...' => ['INSERT DELAYED IGNORE INTO tbl SELECT * FROM bar'];
    }

    public function testBuilderAnsi(): void
    {
        Context::setMode(Context::SQL_MODE_ANSI_QUOTES);
        $sql = "INSERT INTO foo (bar, baz) VALUES ('bar', 'baz')";
        $parser = new Parser($sql);
        $stmt = $parser->statements[0];
        self::assertEquals('INSERT INTO foo("bar", "baz") VALUES (\'bar\', \'baz\')', $stmt->build());
    }
}
