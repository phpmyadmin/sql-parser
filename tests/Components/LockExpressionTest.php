<?php

declare(strict_types=1);

namespace PhpMyAdmin\SqlParser\Tests\Components;

use PhpMyAdmin\SqlParser\Components\LockExpression;
use PhpMyAdmin\SqlParser\Parser;
use PhpMyAdmin\SqlParser\Parsers\LockExpressions;
use PhpMyAdmin\SqlParser\Tests\TestCase;
use PHPUnit\Framework\Attributes\DataProvider;

class LockExpressionTest extends TestCase
{
    public function testParse(): void
    {
        $component = LockExpressions::parse(new Parser(), $this->getTokensList('table1 AS t1 READ LOCAL'));
        $this->assertNotNull($component->table);
        $this->assertEquals('table1', $component->table->table);
        $this->assertEquals('t1', $component->table->alias);
        $this->assertEquals('READ LOCAL', $component->type);
    }

    public function testParse2(): void
    {
        $component = LockExpressions::parse(new Parser(), $this->getTokensList('table1 LOW_PRIORITY WRITE'));
        $this->assertNotNull($component->table);
        $this->assertEquals('table1', $component->table->table);
        $this->assertEquals('LOW_PRIORITY WRITE', $component->type);
    }

    #[DataProvider('parseErrProvider')]
    public function testParseErr(string $expr, string $error): void
    {
        $parser = new Parser();
        LockExpressions::parse($parser, $this->getTokensList($expr));
        $errors = $this->getErrorsAsArray($parser);
        $this->assertEquals($error, $errors[0][0]);
    }

    /** @return string[][] */
    public static function parseErrProvider(): array
    {
        return [
            [
                'table1 AS t1',
                'Unexpected end of LOCK expression.',
            ],
            [
                'table1 AS t1 READ WRITE',
                'Unexpected keyword.',
            ],
            [
                'table1 AS t1 READ 2',
                'Unexpected token.',
            ],
        ];
    }

    public function testBuildAll(): void
    {
        $component = [
            LockExpressions::parse(new Parser(), $this->getTokensList('table1 AS t1 READ LOCAL')),
            LockExpressions::parse(new Parser(), $this->getTokensList('table2 LOW_PRIORITY WRITE')),
        ];
        $this->assertEquals(
            'table1 AS `t1` READ LOCAL, table2 LOW_PRIORITY WRITE',
            LockExpression::buildAll($component),
        );
    }
}
