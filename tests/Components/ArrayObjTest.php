<?php

declare(strict_types=1);

namespace PhpMyAdmin\SqlParser\Tests\Components;

use PhpMyAdmin\SqlParser\Components\ArrayObj;
use PhpMyAdmin\SqlParser\Components\Expression;
use PhpMyAdmin\SqlParser\Parser;
use PhpMyAdmin\SqlParser\Parsers\ArrayObjs;
use PhpMyAdmin\SqlParser\Parsers\Expressions;
use PhpMyAdmin\SqlParser\Tests\TestCase;
use PHPUnit\Framework\Attributes\DataProvider;

class ArrayObjTest extends TestCase
{
    public function testBuildRaw(): void
    {
        $component = new ArrayObj(['a', 'b'], []);
        $this->assertEquals('(a, b)', $component->build());
    }

    public function testBuildValues(): void
    {
        $component = new ArrayObj([], ['a', 'b']);
        $this->assertEquals('(a, b)', $component->build());
    }

    public function testParseType(): void
    {
        $components = ArrayObjs::parse(
            new Parser(),
            $this->getTokensList('(1 + 2, 3 + 4)'),
            [
                'type' => Expressions::class,
                'typeOptions' => ['breakOnParentheses' => true],
            ],
        );
        $this->assertIsArray($components);
        $this->assertInstanceOf(Expression::class, $components[0]);
        $this->assertInstanceOf(Expression::class, $components[1]);
        $this->assertEquals('1 + 2', $components[0]->expr);
        $this->assertEquals('3 + 4', $components[1]->expr);
    }

    #[DataProvider('parseProvider')]
    public function testParse(string $test): void
    {
        $this->runParserTest($test);
    }

    /** @return string[][] */
    public static function parseProvider(): array
    {
        return [
            ['parser/parseArrayErr1'],
            ['parser/parseArrayErr3'],
        ];
    }
}
