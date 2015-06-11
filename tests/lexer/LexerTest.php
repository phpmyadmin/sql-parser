<?php

use SqlParser\Exceptions\LexerException;
use SqlParser\Lexer;

class LexerTest extends TestCase
{

    public function testError()
    {
        $lexer = new Lexer('');

        $lexer->error('error #1', 'foo', 1, 2);
        $lexer->error('error #2', 'bar', 3, 4);

        $this->assertEquals($lexer->errors, array(
            new LexerException('error #1', 'foo', 1, 2),
            new LexerException('error #2', 'bar', 3, 4),
        ));
    }

    /**
     * @expectedException SqlParser\Exceptions\LexerException
     * @expectedExceptionMessage strict error
     * @expectedExceptionCode 4
     */
    public function testErrorStrict()
    {
        $lexer = new Lexer('');
        $lexer->strict = true;

        $lexer->error('strict error', 'foo', 1, 4);
    }

    public function testLex()
    {
        $this->runLexerTest('lex');
    }

    public function testLexKeyword()
    {
        $this->runLexerTest('lexKeyword');
    }

    public function testLexOperator()
    {
        $this->runLexerTest('lexOperator');
    }

    public function testLexWhitespace()
    {
        $this->runLexerTest('lexWhitespace');
    }

    public function testLexComment()
    {
        $this->runLexerTest('lexComment');
    }

    public function testLexBool()
    {
        $this->runLexerTest('lexBool');
    }

    public function testLexNumber()
    {
        $this->runLexerTest('lexNumber');
    }

    public function testLexString()
    {
        $this->runLexerTest('lexString');
    }

    public function testLexStringErr1()
    {
        $this->runLexerTest('lexStringErr1');
    }

    public function testLexSymbol()
    {
        $this->runLexerTest('lexSymbol');
    }

    public function testLexSymbolUser()
    {
        $this->runLexerTest('lexSymbolUser');
    }

    public function testLexSymbolErr1()
    {
        $this->runLexerTest('lexSymbolErr1');
    }

    public function testLexSymbolErr2()
    {
        $this->runLexerTest('lexSymbolErr2');
    }

    public function testLexSymbolErr3()
    {
        $this->runLexerTest('lexSymbolErr3');
    }

    public function testLexDelimiter()
    {
        $this->runLexerTest('lexDelimiter');
    }
}
