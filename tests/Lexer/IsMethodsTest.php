<?php

namespace SqlParser\Tests\Lexer;

use SqlParser\Context;
use SqlParser\Token;

use SqlParser\Tests\TestCase;

class IsMethodsTest extends TestCase
{

    public function testIsKeyword()
    {
        $this->assertEquals(1 | Token::FLAG_KEYWORD_RESERVED, Context::isKeyword('SELECT'));
        $this->assertEquals(1 | Token::FLAG_KEYWORD_RESERVED, Context::isKeyword('ALL'));
        $this->assertEquals(1 | Token::FLAG_KEYWORD_RESERVED, Context::isKeyword('DISTINCT'));

        $this->assertEquals(
            1 | Token::FLAG_KEYWORD_RESERVED | Token::FLAG_KEYWORD_COMPOSED | Token::FLAG_KEYWORD_KEY,
            Context::isKeyword('PRIMARY KEY')
        );
        $this->assertEquals(
            1 | Token::FLAG_KEYWORD_RESERVED | Token::FLAG_KEYWORD_COMPOSED,
            Context::isKeyword('CHARACTER SET')
        );

        $this->assertEquals(1 | Token::FLAG_KEYWORD_RESERVED, Context::isKeyword('FROM', true));
        $this->assertEquals(null, Context::isKeyword('MODIFY', true));

        $this->assertEquals(null, Context::isKeyword('foo'));
        $this->assertEquals(null, Context::isKeyword('bar baz'));
    }

    public function testIsOperator()
    {
        $this->assertEquals(Token::FLAG_OPERATOR_ARITHMETIC, Context::isOperator('%'));
        $this->assertEquals(Token::FLAG_OPERATOR_LOGICAL, Context::isOperator('!'));
        $this->assertEquals(Token::FLAG_OPERATOR_LOGICAL, Context::isOperator('&&'));
        $this->assertEquals(Token::FLAG_OPERATOR_LOGICAL, Context::isOperator('<=>'));
        $this->assertEquals(Token::FLAG_OPERATOR_BITWISE, Context::isOperator('&'));
        $this->assertEquals(Token::FLAG_OPERATOR_ASSIGNMENT, Context::isOperator(':='));
        $this->assertEquals(Token::FLAG_OPERATOR_SQL, Context::isOperator(','));

        $this->assertEquals(Context::isOperator('a'), null);
    }

    public function testIsWhitespace()
    {
        $this->assertTrue(Context::isWhitespace(" "));
        $this->assertTrue(Context::isWhitespace("\r"));
        $this->assertTrue(Context::isWhitespace("\n"));
        $this->assertTrue(Context::isWhitespace("\t"));

        $this->assertFalse(Context::isWhitespace("a"));
        $this->assertFalse(Context::isWhitespace("\b"));
        $this->assertFalse(Context::isWhitespace("\u1000"));
    }

    public function testIsComment()
    {
        $this->assertEquals(Token::FLAG_COMMENT_BASH, Context::isComment('#'));
        $this->assertEquals(Token::FLAG_COMMENT_C, Context::isComment('/*'));
        $this->assertEquals(Token::FLAG_COMMENT_C, Context::isComment('*/'));
        $this->assertEquals(Token::FLAG_COMMENT_SQL, Context::isComment('-- '));
        $this->assertEquals(Token::FLAG_COMMENT_SQL, Context::isComment("--\t"));
        $this->assertEquals(Token::FLAG_COMMENT_SQL, Context::isComment("--\n"));

        $this->assertEquals(Token::FLAG_COMMENT_BASH, Context::isComment('# a comment'));
        $this->assertEquals(Token::FLAG_COMMENT_C, Context::isComment('/*comment */'));
        $this->assertEquals(Token::FLAG_COMMENT_SQL, Context::isComment('-- my comment'));

        $this->assertEquals(null, Context::isComment('--not a comment'));
    }

    public function testIsBool()
    {
        $this->assertTrue(Context::isBool('true'));
        $this->assertTrue(Context::isBool('false'));

        $this->assertFalse(Context::isBool('tru'));
        $this->assertFalse(Context::isBool('falsee'));
    }

    public function testIsNumber()
    {
        $this->assertTrue(Context::isNumber('+'));
        $this->assertTrue(Context::isNumber('-'));
        $this->assertTrue(Context::isNumber('.'));
        $this->assertTrue(Context::isNumber('0'));
        $this->assertTrue(Context::isNumber('1'));
        $this->assertTrue(Context::isNumber('2'));
        $this->assertTrue(Context::isNumber('3'));
        $this->assertTrue(Context::isNumber('4'));
        $this->assertTrue(Context::isNumber('5'));
        $this->assertTrue(Context::isNumber('6'));
        $this->assertTrue(Context::isNumber('7'));
        $this->assertTrue(Context::isNumber('8'));
        $this->assertTrue(Context::isNumber('9'));
        $this->assertTrue(Context::isNumber('e'));
        $this->assertTrue(Context::isNumber('E'));
    }

    public function testIsString()
    {
        $this->assertEquals(Token::FLAG_STRING_SINGLE_QUOTES, Context::isString("'"));
        $this->assertEquals(Token::FLAG_STRING_DOUBLE_QUOTES, Context::isString('"'));

        $this->assertEquals(Token::FLAG_STRING_SINGLE_QUOTES, Context::isString("'foo bar'"));
        $this->assertEquals(Token::FLAG_STRING_DOUBLE_QUOTES, Context::isString('"foo bar"'));

        $this->assertEquals(Context::isString('foo bar'), null);
    }

    public function testIsSymbol()
    {
        $this->assertEquals(Token::FLAG_SYMBOL_VARIABLE, Context::isSymbol('@'));
        $this->assertEquals(Token::FLAG_SYMBOL_BACKTICK, Context::isSymbol('`'));

        $this->assertEquals(Token::FLAG_SYMBOL_VARIABLE, Context::isSymbol('@id'));
        $this->assertEquals(Token::FLAG_SYMBOL_BACKTICK, Context::isSymbol('`id`'));

        $this->assertEquals(Context::isSymbol('id'), null);
    }

    public function testisSeparator()
    {
        $this->assertTrue(Context::isSeparator('+'));
        $this->assertTrue(Context::isSeparator('.'));

        $this->assertFalse(Context::isSeparator('1'));
        $this->assertFalse(Context::isSeparator('E'));
        $this->assertFalse(Context::isSeparator('_'));
    }
}
