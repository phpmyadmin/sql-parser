<?php

namespace SqlParser\Tests\Utils;

use SqlParser\Parser;
use SqlParser\Token;
use SqlParser\Utils\Tokens;

use SqlParser\Tests\TestCase;

class TokensTest extends TestCase
{

    /**
     * @dataProvider replaceTokensProvider
     */
    public function testReplaceTokens($list, $find, $replace, $expected)
    {
        $this->assertEquals($expected, Tokens::replaceTokens($list, $find, $replace));
    }

    public function replaceTokensProvider()
    {
        return array(
            array(
                'SELECT * FROM /*x*/a/*c*/.b',
                array(
                    array('value_str' => 'a'),
                    array('token' => '.'),
                ),
                array(
                    new Token('c'),
                    new Token('.'),
                ),
                'SELECT * FROM /*x*/c.b',
            )
        );
    }

    /**
     * @dataProvider matchProvider
     */
    public function testMatch($token, $pattern, $expected)
    {
        $this->assertEquals($expected, Tokens::match($token, $pattern));
    }

    public function matchProvider()
    {
        return array(
            array(new Token(''), array(), true),

            array(
                new Token('"abc"', Token::TYPE_STRING, Token::FLAG_STRING_DOUBLE_QUOTES),
                array('token' => '"abc"'),
                true
            ),
            array(
                new Token('"abc""', Token::TYPE_STRING, Token::FLAG_STRING_DOUBLE_QUOTES),
                array('value' => 'abc'),
                true
            ),
            array(
                new Token('"abc""', Token::TYPE_STRING, Token::FLAG_STRING_DOUBLE_QUOTES),
                array('value_str' => 'ABC'),
                true
            ),
            array(
                new Token('"abc""', Token::TYPE_STRING, Token::FLAG_STRING_DOUBLE_QUOTES),
                array('type' => Token::TYPE_STRING),
                true
            ),
            array(
                new Token('"abc""', Token::TYPE_STRING, Token::FLAG_STRING_DOUBLE_QUOTES),
                array('flags' => Token::FLAG_STRING_DOUBLE_QUOTES),
                true
            ),

            array(
                new Token('"abc"', Token::TYPE_STRING, Token::FLAG_STRING_DOUBLE_QUOTES),
                array('token' => '"abcd"'),
                false
            ),
            array(
                new Token('"abc""', Token::TYPE_STRING, Token::FLAG_STRING_DOUBLE_QUOTES),
                array('value' => 'abcd'),
                false
            ),
            array(
                new Token('"abc""', Token::TYPE_STRING, Token::FLAG_STRING_DOUBLE_QUOTES),
                array('value_str' => 'ABCd'),
                false
            ),
            array(
                new Token('"abc""', Token::TYPE_STRING, Token::FLAG_STRING_DOUBLE_QUOTES),
                array('type' => Token::TYPE_NUMBER),
                false
            ),
            array(
                new Token('"abc""', Token::TYPE_STRING, Token::FLAG_STRING_DOUBLE_QUOTES),
                array('flags' => Token::FLAG_STRING_SINGLE_QUOTES),
                false
            ),
        );
    }
}
