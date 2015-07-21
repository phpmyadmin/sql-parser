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
            sprintf(__('%2$s #%1$d'), 2, 'error'), 'bar', 3, 4
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
        $this->runLexerTest($test);
    }

    public function testLexProvider()
    {
        return array(
            array('lex'),
            array('lexUtf8'),
            array('lexBool'),
            array('lexComment'),
            array('lexDelimiter'),
            array('lexDelimiter2'),
            array('lexDelimiterErr1'),
            array('lexDelimiterErr2'),
            array('lexKeyword'),
            array('lexNumber'),
            array('lexOperator'),
            array('lexString'),
            array('lexStringErr1'),
            array('lexSymbol'),
            array('lexSymbolErr1'),
            array('lexSymbolErr2'),
            array('lexSymbolErr3'),
            array('lexSymbolUser'),
            array('lexWhitespace'),
        );
    }
}
