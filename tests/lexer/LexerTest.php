<?php

namespace SqlParser\Tests\Lexer;

use SqlParser\Exceptions\LexerException;
use SqlParser\Lexer;

use SqlParser\Tests\TestCase;

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

    /**
     * @dataProvider testLexProvider
     */
    public function testLex($test)
    {
        $this->runLexerTest($test);
    }

    public function testLexProvider()
    {
        return array(
            array('lex'),
            array('lexKeyword'),
            array('lexOperator'),
            array('lexWhitespace'),
            array('lexComment'),
            array('lexBool'),
            array('lexNumber'),
            array('lexString'),
            array('lexStringErr1'),
            array('lexSymbol'),
            array('lexSymbolUser'),
            array('lexSymbolErr1'),
            array('lexSymbolErr2'),
            array('lexSymbolErr3'),
            array('lexDelimiter'),
        );
    }
}
