<?php

declare(strict_types=1);

namespace PhpMyAdmin\SqlParser\Tests\Parser;

use PhpMyAdmin\SqlParser\Parser;
use PhpMyAdmin\SqlParser\Statements\KillStatement;
use PhpMyAdmin\SqlParser\Tests\TestCase;

class KillStatementTest extends TestCase
{
    /**
     * @dataProvider killProvider
     */
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
            ['parser/parseKillConnection'],
            ['parser/parseKillQuery'],
        ];
    }

    /**
     * @dataProvider buildKillProvider
     */
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
            ['KILL QUERY 3'],
            ['KILL CONNECTION 3'],
            ['KILL'],
        ];
    }
}
