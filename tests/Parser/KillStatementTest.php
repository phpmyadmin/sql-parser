<?php

declare(strict_types=1);

namespace PhpMyAdmin\SqlParser\Tests\Parser;

use PhpMyAdmin\SqlParser\Parser;
use PhpMyAdmin\SqlParser\Statements\KillStatement;
use PhpMyAdmin\SqlParser\Tests\TestCase;

class KillStatementTest extends TestCase
{
    /** @dataProvider killProvider */
    public function testKill(string $test): void
    {
        $this->runParserTest($test);
    }

    /**
     * @return string[][]
     */
    public static function killProvider(): array
    {
        return [
            ['parser/parseKill'],
            ['parser/parseKill2'],
            ['parser/parseKill3'],
            ['parser/parseKillConnection'],
            ['parser/parseKillQuery'],
            ['parser/parseKillErr1'],
            ['parser/parseKillErr2'],
            ['parser/parseKillErr3'],
            ['parser/parseKillErr4'],
        ];
    }

    /** @dataProvider buildKillProvider */
    public function testBuildKill(string $sql): void
    {
        $parser = new Parser($sql);
        $this->assertCount(1, $parser->statements);
        $statement = $parser->statements[0];
        $this->assertInstanceOf(KillStatement::class, $statement);
        $builtSql = $statement->build();
        $this->assertEquals($sql, $builtSql);
    }

    /**
     * @return array<int, array<int, string>>
     * @psalm-return list<list<string>>
     */
    public static function buildKillProvider(): array
    {
        return [
            ['KILL (SELECT 3 + 4)'],
            ['KILL QUERY 4'],
            ['KILL CONNECTION 5'],
            ['KILL 6'],
            ['KILL QUERY (SELECT 7)'],
            ['KILL SOFT QUERY (SELECT 8)'],
            ['KILL HARD 9'],
            ['KILL USER 10'],
            ['KILL SOFT (SELECT 1)'],
            ['KILL (2)'],
            ['KILL QUERY ID (2)'],
            ['KILL QUERY ID (SELECT ID FROM INFORMATION_SCHEMA.PROCESSLIST LIMIT 0, 1)'],
        ];
    }
}
