<?php

namespace SqlParser\Tests\Lexer;

use SqlParser\Exceptions\LexerException;
use SqlParser\Lexer;

use SqlParser\Tests\TestCase;

class LexerTest extends TestCase
{

    /**
     * @runInSeparateProcess
     * @preserveGlobalState disabled
     */
    public function testError()
    {
        $lexer = new Lexer('');

        $lexer->error(__('error #1'), 'foo', 1, 2);
        $lexer->error(
            sprintf(__('%2$s #%1$d'), 2, 'error'),
            'bar',
            3,
            4
        );

        $this->assertEquals(
            $lexer->errors,
            array(
                new LexerException('error #1', 'foo', 1, 2),
                new LexerException('error #2', 'bar', 3, 4),
            )
        );
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

        $lexer->error(__('strict error'), 'foo', 1, 4);
    }

    /**
     * @dataProvider testLexProvider
     */
    public function testLex($test)
    {
        $this->runParserTest($test);
    }

    public function testLexProvider()
    {
        return array(
            array('lexer/lex'),
            array('lexer/lexUtf8'),
            array('lexer/lexBool'),
            array('lexer/lexComment'),
            array('lexer/lexDelimiter'),
            array('lexer/lexDelimiter2'),
            array('lexer/lexDelimiterErr1'),
            array('lexer/lexDelimiterErr2'),
            array('lexer/lexDelimiterErr3'),
            array('lexer/lexKeyword'),
            array('lexer/lexKeyword2'),
            array('lexer/lexNumber'),
            array('lexer/lexOperator'),
            array('lexer/lexString'),
            array('lexer/lexStringErr1'),
            array('lexer/lexSymbol'),
            array('lexer/lexSymbolErr1'),
            array('lexer/lexSymbolErr2'),
            array('lexer/lexSymbolErr3'),
            array('lexer/lexSymbolUser'),
            array('lexer/lexWhitespace'),
        );
    }
}
