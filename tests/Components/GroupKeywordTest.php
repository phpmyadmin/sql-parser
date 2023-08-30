<?php

declare(strict_types=1);

namespace PhpMyAdmin\SqlParser\Tests\Components;

use Generator;
use PhpMyAdmin\SqlParser\Components\Expression;
use PhpMyAdmin\SqlParser\Components\GroupKeyword;
use PhpMyAdmin\SqlParser\Tests\TestCase;
use PHPUnit\Framework\Attributes\DataProvider;

use function array_map;
use function is_array;

class GroupKeywordTest extends TestCase
{
    /**
     * @return Generator<string, array{GroupKeyword|array<GroupKeyword>, string}>
     */
    public static function provideExpressions(): Generator
    {
        yield 'With no expression at all' => [[], ''];

        yield 'With single simple expression' => [
            self::makeComponentFrom('a'),
            'a',
        ];

        yield 'With multiple simple expressions' => [
            self::makeComponentsFrom('a', 'b', 'c'),
            'a, b, c',
        ];

        yield 'With single untrimmed expression' => [
            self::makeComponentFrom('  o  '),
            'o',
        ];

        yield 'With single untrimmed expression having several kinds of whitespaces' => [
            self::makeComponentFrom(" \n\r foo \t\v\x00  "),
            'foo',
        ];

        yield 'With multiple untrimmed expressions' => [
            self::makeComponentsFrom('  x', ' y ', 'z  '),
            'x, y, z',
        ];

        yield 'With multiple untrimmed expression having several kinds of whitespaces' => [
            self::makeComponentsFrom(" \n\r\t\v\x00foo", " \n\r\tbar\v\x00", "baz \n\r\t\v\x00"),
            'foo, bar, baz',
        ];
    }

    /**
     * @param GroupKeyword|array<GroupKeyword> $component
     */
    #[DataProvider('provideExpressions')]
    public function testBuild($component, string $expected): void
    {
        if (is_array($component)) {
            $this->assertSame($expected, GroupKeyword::buildAll($component));
        } else {
            $this->assertSame($expected, $component->build());
        }
    }

    private static function makeComponentFrom(string $string): GroupKeyword
    {
        return new GroupKeyword(new Expression($string));
    }

    /**
     * @return array<GroupKeyword>
     */
    private static function makeComponentsFrom(string ...$string): array
    {
        return array_map([self::class, 'makeComponentFrom'], $string);
    }
}
