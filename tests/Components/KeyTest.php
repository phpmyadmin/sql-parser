<?php

declare(strict_types=1);

namespace PhpMyAdmin\SqlParser\Tests\Components;

use PhpMyAdmin\SqlParser\Components\Expression;
use PhpMyAdmin\SqlParser\Components\Key;
use PhpMyAdmin\SqlParser\Components\OptionsArray;
use PhpMyAdmin\SqlParser\Exceptions\ParserException;
use PhpMyAdmin\SqlParser\Parser;
use PhpMyAdmin\SqlParser\Tests\TestCase;
use PhpMyAdmin\SqlParser\Token;

class KeyTest extends TestCase
{
    public function testParse(): void
    {
        $component = Key::parse(
            new Parser(),
            $this->getTokensList('')
        );
        $this->assertNull($component->type);
        $this->assertNull($component->options);
        $this->assertNull($component->name);
        $this->assertNull($component->expr);
        $this->assertSame([], $component->columns);
        $this->assertSame(
            '()',
            Key::build($component)
        );
    }

    public function testParseKeyWithoutOptions(): void
    {
        $component = Key::parse(
            new Parser(),
            $this->getTokensList('KEY `alias_type_idx` (`alias_type`),')
        );
        $this->assertEquals('KEY', $component->type);
        $this->assertEquals('alias_type_idx', $component->name);
        $this->assertEquals(new OptionsArray(), $component->options);
        $this->assertNull($component->expr);
        $this->assertSame([['name' => 'alias_type']], $component->columns);
        $this->assertSame(
            'KEY `alias_type_idx` (`alias_type`)',
            Key::build($component)
        );
    }

    public function testParseKeyWithLengthWithoutOptions(): void
    {
        $component = Key::parse(
            new Parser(),
            $this->getTokensList('KEY `alias_type_idx` (`alias_type`(10)),')
        );
        $this->assertEquals('KEY', $component->type);
        $this->assertEquals('alias_type_idx', $component->name);
        $this->assertEquals(new OptionsArray(), $component->options);
        $this->assertNull($component->expr);
        $this->assertSame([['name' => 'alias_type', 'length' => 10]], $component->columns);
        $this->assertSame(
            'KEY `alias_type_idx` (`alias_type`(10))',
            Key::build($component)
        );
    }

    public function testParseKeyWithLengthWithoutOptionsWithOrder(): void
    {
        $component = Key::parse(
            new Parser(),
            $this->getTokensList('KEY `alias_type_idx` (`alias_type`(10) ASC),')
        );
        $this->assertEquals('KEY', $component->type);
        $this->assertEquals('alias_type_idx', $component->name);
        $this->assertEquals(new OptionsArray(), $component->options);
        $this->assertNull($component->expr);
        $this->assertSame([['name' => 'alias_type', 'length' => 10, 'order' => 'ASC']], $component->columns);
        $this->assertSame(
            'KEY `alias_type_idx` (`alias_type`(10) ASC)',
            Key::build($component)
        );
    }

    public function testParseKeyWithoutOptionsWithOrderLowercase(): void
    {
        $component = Key::parse(
            new Parser(),
            $this->getTokensList('KEY `alias_type_idx` (`alias_type` desc),')
        );
        $this->assertEquals('KEY', $component->type);
        $this->assertEquals('alias_type_idx', $component->name);
        $this->assertEquals(new OptionsArray(), $component->options);
        $this->assertNull($component->expr);
        $this->assertSame([['name' => 'alias_type', 'order' => 'DESC']], $component->columns);
        $this->assertSame(
            'KEY `alias_type_idx` (`alias_type` DESC)',
            Key::build($component)
        );
    }

    public function testParseKeyWithoutOptionsWithOrder(): void
    {
        $component = Key::parse(
            new Parser(),
            $this->getTokensList('KEY `alias_type_idx` (`alias_type` DESC),')
        );
        $this->assertEquals('KEY', $component->type);
        $this->assertEquals('alias_type_idx', $component->name);
        $this->assertEquals(new OptionsArray(), $component->options);
        $this->assertNull($component->expr);
        $this->assertSame([['name' => 'alias_type', 'order' => 'DESC']], $component->columns);
        $this->assertSame(
            'KEY `alias_type_idx` (`alias_type` DESC)',
            Key::build($component)
        );
    }

    public function testParseKeyWithLengthWithOptions(): void
    {
        $component = Key::parse(
            new Parser(),
            $this->getTokensList('KEY `alias_type_idx` (`alias_type`(10)) COMMENT \'my comment\',')
        );
        $this->assertEquals('KEY', $component->type);
        $this->assertEquals('alias_type_idx', $component->name);
        $this->assertEquals(new OptionsArray(
            [
                4 => [
                    'name' => 'COMMENT',
                    'equals' => false,
                    'expr' => '\'my comment\'',
                    'value' => 'my comment',
                ],
            ]
        ), $component->options);
        $this->assertNull($component->expr);
        $this->assertSame([['name' => 'alias_type', 'length' => 10]], $component->columns);
        $this->assertSame(
            'KEY `alias_type_idx` (`alias_type`(10)) COMMENT \'my comment\'',
            Key::build($component)
        );
    }

    public function testParseKeyWithLengthWithAllOptions(): void
    {
        $component = Key::parse(
            new Parser(),
            $this->getTokensList(
                // This is not a vary plausible example but it runs
                // Only ENGINE_ATTRIBUTE gives a not supported error but is still a valid syntax
                'KEY `alias_type_idx` (`alias_type`(10))'
                . ' COMMENT \'my comment\' VISIBLE KEY_BLOCK_SIZE=1'
                . ' INVISIBLE ENGINE_ATTRIBUTE \'foo\' SECONDARY_ENGINE_ATTRIBUTE=\'bar\' USING BTREE,'
            )
        );
        $this->assertEquals('KEY', $component->type);
        $this->assertEquals('alias_type_idx', $component->name);
        $this->assertEquals(new OptionsArray(
            [
                1 => [
                    'name' => 'KEY_BLOCK_SIZE',
                    'equals' => true,
                    'expr' => '1',
                    'value' => '1',
                ],
                2 => [
                    'name' => 'USING',
                    'equals' => false,
                    'expr' => 'BTREE',
                    'value' => 'BTREE',
                ],
                4 => [
                    'name' => 'COMMENT',
                    'equals' => false,
                    'expr' => '\'my comment\'',
                    'value' => 'my comment',
                ],
                5 => [
                    'name' => 'ENGINE_ATTRIBUTE',
                    'equals' => true,
                    'expr' => '\'foo\'',
                    'value' => 'foo',
                ],
                6 => 'VISIBLE',
                12 => 'INVISIBLE',
                13 => [
                    'name' => 'SECONDARY_ENGINE_ATTRIBUTE',
                    'equals' => true,
                    'expr' => '\'bar\'',
                    'value' => 'bar',
                ],
            ]
        ), $component->options);
        $this->assertNull($component->expr);
        $this->assertSame([['name' => 'alias_type', 'length' => 10]], $component->columns);
    }

    public function testParseKeyExpressionWithoutOptions(): void
    {
        $component = Key::parse(
            new Parser(),
            $this->getTokensList(
                'KEY `updated_tz_ind2` ((convert_tz(`cache_updated`,_utf8mb4\'GMT\',_utf8mb4\'GB\'))),'
            )
        );
        $this->assertEquals('KEY', $component->type);
        $this->assertEquals('updated_tz_ind2', $component->name);
        $this->assertEquals(new OptionsArray(), $component->options);
        $expr = new Expression('(convert_tz(`cache_updated`,_utf8mb4\'GMT\',_utf8mb4\'GB\'))');
        $expr->function = 'convert_tz';
        $this->assertEquals($expr, $component->expr);
        $this->assertSame([], $component->columns);
        $this->assertSame(
            'KEY `updated_tz_ind2` ((convert_tz(`cache_updated`,_utf8mb4\'GMT\',_utf8mb4\'GB\'))) ',
            Key::build($component)
        );
    }

    public function testParseKeyExpressionWithOptions(): void
    {
        $component = Key::parse(
            new Parser(),
            $this->getTokensList(
                'KEY `updated_tz_ind2`'
                . ' ((convert_tz(`cache_updated`,_utf8mb4\'GMT\',_utf8mb4\'GB\')))'
                . ' COMMENT \'my comment\','
            )
        );
        $this->assertEquals('KEY', $component->type);
        $this->assertEquals('updated_tz_ind2', $component->name);
        $this->assertEquals(new OptionsArray(
            [
                4 => [
                    'name' => 'COMMENT',
                    'equals' => false,
                    'expr' => '\'my comment\'',
                    'value' => 'my comment',
                ],
            ]
        ), $component->options);
        $expr = new Expression('(convert_tz(`cache_updated`,_utf8mb4\'GMT\',_utf8mb4\'GB\'))');
        $expr->function = 'convert_tz';
        $this->assertEquals($expr, $component->expr);
        $this->assertSame([], $component->columns);
        $this->assertSame(
            'KEY `updated_tz_ind2`'
            . ' ((convert_tz(`cache_updated`,_utf8mb4\'GMT\',_utf8mb4\'GB\')))'
            . ' COMMENT \'my comment\'',
            Key::build($component)
        );
    }

    public function testParseKeyExpressionWithOptionsError(): void
    {
        $parser = new Parser();
        $component = Key::parse(
            $parser,
            $this->getTokensList(
                'KEY `updated_tz_ind2` (()convert_tz(`cache_updated`,_utf8mb4\'GMT\',_utf8mb4\'GB\')))'
                . ' COMMENT \'my comment\','
            )
        );
        $this->assertEquals('KEY', $component->type);
        $this->assertEquals('updated_tz_ind2', $component->name);
        $this->assertEquals(new OptionsArray(
            []
        ), $component->options);
        $t = new Token('convert_tz', Token::TYPE_KEYWORD, 33);
        $t->position = 25;

        $this->assertEquals([
            new ParserException(
                'Unexpected token.',
                $t
            ),
        ], $parser->errors);
        $this->assertEquals('()', $component->expr);
        $this->assertSame([], $component->columns);
        $this->assertSame('KEY `updated_tz_ind2` (()) ', Key::build($component));
    }

    public function testParseKeyOneExpressionWithOptions(): void
    {
        $parser = new Parser();
        $component = Key::parse(
            $parser,
            $this->getTokensList(
                'KEY `updated_tz_ind2`'
                . ' ('
                . '(convert_tz(`cache_updated`,_utf8mb4\'GMT\',_utf8mb4\'GB\')), '
                . '(convert_tz(`cache_updated`,_utf8mb4\'GMT\',_utf8mb4\'FR\'))'
                . ')'
                . ' COMMENT \'my comment\','
            )
        );
        $this->assertEquals('KEY', $component->type);
        $this->assertEquals('updated_tz_ind2', $component->name);
        $this->assertEquals(new OptionsArray(
            [
                4 => [
                    'name' => 'COMMENT',
                    'equals' => false,
                    'expr' => '\'my comment\'',
                    'value' => 'my comment',
                ],
            ]
        ), $component->options);
        $this->assertSame([], $parser->errors);
        $expr = new Expression(
            '(convert_tz(`cache_updated`,_utf8mb4\'GMT\',_utf8mb4\'GB\')),'
            . ' (convert_tz(`cache_updated`,_utf8mb4\'GMT\',_utf8mb4\'FR\'))'
        );
        $expr->function = 'convert_tz';
        $this->assertEquals($expr, $component->expr);
        $this->assertSame([], $component->columns);
        $this->assertSame(
            'KEY `updated_tz_ind2` ((convert_tz(`cache_updated`,_utf8mb4\'GMT\',_utf8mb4\'GB\')),'
            . ' (convert_tz(`cache_updated`,_utf8mb4\'GMT\',_utf8mb4\'FR\'))'
            . ') COMMENT \'my comment\'',
            Key::build($component)
        );
    }

    public function testParseKeyMultipleExpressionsWithOptions(): void
    {
        $parser = new Parser();
        $component = Key::parse(
            $parser,
            $this->getTokensList(
                'KEY `updated_tz_ind2`'
                . ' ('
                . '(convert_tz(`cache_updated`,_utf8mb4\'GMT\',_utf8mb4\'GB\')), '
                . '(convert_tz(`cache_updated`,_utf8mb4\'GMT\',_utf8mb4\'FR\')), '
                . '(convert_tz(`cache_updated`,_utf8mb4\'GMT\',_utf8mb4\'RU\'))'
                . ')'
                . ' COMMENT \'my comment\','
            )
        );
        $this->assertEquals('KEY', $component->type);
        $this->assertEquals('updated_tz_ind2', $component->name);
        $this->assertEquals(new OptionsArray(
            [
                4 => [
                    'name' => 'COMMENT',
                    'equals' => false,
                    'expr' => '\'my comment\'',
                    'value' => 'my comment',
                ],
            ]
        ), $component->options);
        $expr = new Expression(
            '(convert_tz(`cache_updated`,_utf8mb4\'GMT\',_utf8mb4\'GB\')),'
            . ' (convert_tz(`cache_updated`,_utf8mb4\'GMT\',_utf8mb4\'FR\')),'
            . ' (convert_tz(`cache_updated`,_utf8mb4\'GMT\',_utf8mb4\'RU\'))'
        );
        $expr->function = 'convert_tz';
        $this->assertEquals($expr, $component->expr);
        $this->assertSame([], $component->columns);
        $this->assertSame([], $parser->errors);
        $this->assertSame(
            'KEY `updated_tz_ind2` ((convert_tz(`cache_updated`,_utf8mb4\'GMT\',_utf8mb4\'GB\')),'
            . ' (convert_tz(`cache_updated`,_utf8mb4\'GMT\',_utf8mb4\'FR\')),'
            . ' (convert_tz(`cache_updated`,_utf8mb4\'GMT\',_utf8mb4\'RU\'))'
            . ') COMMENT \'my comment\'',
            Key::build($component)
        );
    }

    public function testParseKeyWithColumnsAndExpressions(): void
    {
        $parser = new Parser();
        $component = Key::parse(
            $parser,
            $this->getTokensList('KEY `k` (`a`,`b` DESC,((((`a` % 7) * 3) + `b`)),`c`),')
        );
        self::assertSame([], $parser->errors);
        self::assertEquals('KEY', $component->type);
        self::assertEquals('k', $component->name);
        self::assertNull($component->expr);
        self::assertSame([
            ['name' => 'a'],
            ['name' => 'b', 'order' => 'DESC'],
            ['expr' => '((((`a` % 7) * 3) + `b`))'],
            ['name' => 'c'],
        ], $component->columns);
        self::assertSame(
            'KEY `k` (`a`,`b` DESC,((((`a` % 7) * 3) + `b`)),`c`)',
            Key::build($component)
        );
    }

    public function testParseKeyWithExpressionBeforeColumns(): void
    {
        $parser = new Parser();
        $component = Key::parse(
            $parser,
            $this->getTokensList('UNIQUE KEY `u` ((ifnull(`a`,0)) DESC,`b`) USING BTREE,')
        );
        self::assertSame([], $parser->errors);
        self::assertEquals('UNIQUE KEY', $component->type);
        self::assertNull($component->expr);
        self::assertSame([
            ['expr' => '(ifnull(`a`,0))', 'order' => 'DESC'],
            ['name' => 'b'],
        ], $component->columns);
        self::assertSame(
            'UNIQUE KEY `u` ((ifnull(`a`,0)) DESC,`b`) USING BTREE',
            Key::build($component)
        );
    }

    public function testParseKeyExpressionWithOrder(): void
    {
        $parser = new Parser();
        $component = Key::parse($parser, $this->getTokensList('KEY `expr_desc` (((`a` + 1)) DESC),'));
        self::assertSame([], $parser->errors);
        self::assertNull($component->expr);
        self::assertSame([['expr' => '((`a` + 1))', 'order' => 'DESC']], $component->columns);
        self::assertSame('KEY `expr_desc` (((`a` + 1)) DESC)', Key::build($component));
    }

    public function testParseKeyWithColumnsAndExpressionsError(): void
    {
        $parser = new Parser();
        $component = Key::parse($parser, $this->getTokensList('KEY `k` (`a`,((`b` * 2)) `c`(5),`d`),'));
        $t = new Token('`c`', Token::TYPE_SYMBOL, Token::FLAG_SYMBOL_BACKTICK);
        $t->value = 'c';
        $t->position = 25;
        self::assertEquals([new ParserException('Unexpected token.', $t)], $parser->errors);
        self::assertSame([
            ['name' => 'a'],
            ['expr' => '((`b` * 2))'],
            ['name' => 'd'],
        ], $component->columns);
    }

    public function testParseKeyWithUnexpectedParenthesisAfterExpression(): void
    {
        $parser = new Parser();
        $component = Key::parse($parser, $this->getTokensList('KEY `k` (`a`,((`b` + 1))(5),`d`),'));
        $t = new Token('(', Token::TYPE_OPERATOR, Token::FLAG_OPERATOR_SQL);
        $t->position = 24;
        self::assertEquals([new ParserException('Unexpected token.', $t)], $parser->errors);
        self::assertSame([
            ['name' => 'a'],
            ['expr' => '((`b` + 1))'],
            ['name' => 'd'],
        ], $component->columns);
    }
}
