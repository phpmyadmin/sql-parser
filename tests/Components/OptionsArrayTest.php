<?php

declare(strict_types=1);

namespace PhpMyAdmin\SqlParser\Tests\Components;

use PhpMyAdmin\SqlParser\Components\Expression;
use PhpMyAdmin\SqlParser\Components\OptionsArray;
use PhpMyAdmin\SqlParser\Parser;
use PhpMyAdmin\SqlParser\Parsers\OptionsArrays;
use PhpMyAdmin\SqlParser\Tests\TestCase;

class OptionsArrayTest extends TestCase
{
    public function testParse(): void
    {
        $component = OptionsArrays::parse(
            new Parser(),
            $this->getTokensList('A B = /*comment*/ (test) C'),
            [
                'A' => 1,
                'B' => [
                    2,
                    'var',
                ],
                'C' => 3,
            ],
        );
        $this->assertEquals(
            [
                1 => 'A',
                2 => [
                    'name' => 'B',
                    'expr' => '(test)',
                    'value' => 'test',
                    'equals' => true,
                ],
                3 => 'C',
            ],
            $component->options,
        );
    }

    public function testParseExpr(): void
    {
        $component = OptionsArrays::parse(
            new Parser(),
            $this->getTokensList('SUM = (3 + 5) RESULT = 8'),
            [
                'SUM' => [
                    1,
                    'expr',
                    ['parenthesesDelimited' => true],
                ],
                'RESULT' => [
                    2,
                    'var',
                ],
            ],
        );
        $sumValue = $component->get('SUM', true);
        $this->assertInstanceOf(Expression::class, $sumValue);
        $this->assertEquals('(3 + 5)', (string) $sumValue);
        $this->assertEquals('8', $component->get('RESULT'));
    }

    public function testHas(): void
    {
        $component = OptionsArrays::parse(
            new Parser(),
            $this->getTokensList('A B = /*comment*/ (test) C'),
            [
                'A' => 1,
                'B' => [
                    2,
                    'var',
                ],
                'C' => 3,
            ],
        );
        $this->assertTrue($component->has('A'));
        $this->assertEquals('test', $component->get('B'));
        $this->assertTrue($component->has('C'));
        $this->assertFalse($component->has('D'));
    }

    public function testRemove(): void
    {
        /* Assertion 1 */
        $component = new OptionsArray(['a', 'b', 'c']);
        $this->assertTrue($component->remove('b'));
        $this->assertFalse($component->remove('d'));
        $this->assertEquals([0 => 'a', 2 => 'c'], $component->options);

        /* Assertion 2 */
        $component = OptionsArrays::parse(
            new Parser(),
            $this->getTokensList('A B = /*comment*/ (test) C'),
            [
                'A' => 1,
                'B' => [
                    2,
                    'var',
                ],
                'C' => 3,
            ],
        );
        $this->assertEquals('test', $component->get('B'));
        $component->remove('B');
        $this->assertFalse($component->has('B'));
    }

    public function testMerge(): void
    {
        $component = new OptionsArray(['a']);
        $component->merge(new OptionsArray(['b', 'c']));
        $this->assertEquals(['a', 'b', 'c'], $component->options);
    }

    public function testBuild(): void
    {
        $component = new OptionsArray(
            [
                'ALL',
                'SQL_CALC_FOUND_ROWS',
                [
                    'name' => 'MAX_STATEMENT_TIME',
                    'value' => '42',
                    'equals' => true,
                    'expr' => '',
                ],
            ],
        );
        $this->assertEquals(
            'ALL SQL_CALC_FOUND_ROWS MAX_STATEMENT_TIME=42',
            $component->build(),
        );
    }

    public function testBuildWithRecursive(): void
    {
        $component = OptionsArrays::parse(
            new Parser(),
            $this->getTokensList('RECURSIVE'),
            ['RECURSIVE' => 1],
        );
        $this->assertEquals(
            'RECURSIVE',
            $component->build(),
        );
    }
}
