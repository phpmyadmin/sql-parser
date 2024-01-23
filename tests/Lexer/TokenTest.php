<?php

declare(strict_types=1);

namespace PhpMyAdmin\SqlParser\Tests\Lexer;

use PhpMyAdmin\SqlParser\Tests\TestCase;
use PhpMyAdmin\SqlParser\Token;
use PhpMyAdmin\SqlParser\TokenType;

class TokenTest extends TestCase
{
    public function testExtractKeyword(): void
    {
        $tok = new Token('SelecT', TokenType::Keyword, Token::FLAG_KEYWORD_RESERVED);
        $this->assertEquals('SELECT', $tok->value);

        $tok = new Token('aS', TokenType::Keyword, Token::FLAG_KEYWORD_RESERVED);
        $this->assertEquals('AS', $tok->value);
    }

    public function testExtractWhitespace(): void
    {
        $tok = new Token(" \t \r \n ", TokenType::Whitespace);
        $this->assertEquals(' ', $tok->value);
    }

    public function testExtractBool(): void
    {
        $tok = new Token('false', TokenType::Bool);
        $this->assertFalse($tok->value);

        $tok = new Token('True', TokenType::Bool);
        $this->assertTrue($tok->value);
    }

    public function testExtractNumber(): void
    {
        $tok = new Token('--42', TokenType::Number, Token::FLAG_NUMBER_NEGATIVE);
        $this->assertEquals(42, $tok->value);

        $tok = new Token('---42', TokenType::Number, Token::FLAG_NUMBER_NEGATIVE);
        $this->assertEquals(-42, $tok->value);

        $tok = new Token('0xFE', TokenType::Number, Token::FLAG_NUMBER_HEX);
        $this->assertEquals(0xFE, $tok->value);

        $tok = new Token('-0xEF', TokenType::Number, Token::FLAG_NUMBER_NEGATIVE | Token::FLAG_NUMBER_HEX);
        $this->assertEquals(-0xEF, $tok->value);

        $tok = new Token('3.14', TokenType::Number, Token::FLAG_NUMBER_FLOAT);
        $this->assertEquals(3.14, $tok->value);
    }

    public function testExtractString(): void
    {
        $tok = new Token('"foo bar "', TokenType::String);
        $this->assertEquals('foo bar ', $tok->value);

        $tok = new Token("' bar foo '", TokenType::String);
        $this->assertEquals(' bar foo ', $tok->value);

        $tok = new Token("'\''", TokenType::String);
        $this->assertEquals('\'', $tok->value);

        $tok = new Token('"\c\d\e\f\g\h\i\j\k\l\m\p\q\s\u\v\w\x\y\z"', TokenType::String);
        $this->assertEquals('cdefghijklmpqsuvwxyz', $tok->value);
    }

    public function testExtractSymbol(): void
    {
        $tok = new Token('@foo', TokenType::Symbol, Token::FLAG_SYMBOL_VARIABLE);
        $this->assertEquals('foo', $tok->value);

        $tok = new Token('`foo`', TokenType::Symbol, Token::FLAG_SYMBOL_BACKTICK);
        $this->assertEquals('foo', $tok->value);

        $tok = new Token('@`foo`', TokenType::Symbol, Token::FLAG_SYMBOL_VARIABLE);
        $this->assertEquals('foo', $tok->value);

        $tok = new Token(':foo', TokenType::Symbol, Token::FLAG_SYMBOL_PARAMETER);
        $this->assertEquals('foo', $tok->value);

        $tok = new Token('?', TokenType::Symbol, Token::FLAG_SYMBOL_PARAMETER);
        $this->assertEquals('?', $tok->value);
    }

    public function testInlineToken(): void
    {
        $token = new Token(" \r \n \t ");
        $this->assertEquals(' \r \n \t ', $token->getInlineToken());
    }
}
