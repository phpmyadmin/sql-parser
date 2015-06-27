<?php

namespace SqlParser\Tests\Parser;

use SqlParser\Exceptions\ParserException;
use SqlParser\Lexer;
use SqlParser\Parser;
use SqlParser\Token;
use SqlParser\TokensList;

use SqlParser\Tests\TestCase;

class ParserTest extends TestCase
{

    public function testParse()
    {
        $this->runParserTest('parse');
    }

    public function testUnrecognizedStatement()
    {
        $parser = new Parser('SELECT 1; FROM');
        $this->assertEquals(
            $parser->errors[0]->getMessage(),
            'Unrecognized statement type "FROM".'
        );
    }

    public function testUnrecognizedKeyword()
    {
        $parser = new Parser('SELECT 1 FROM foo PARTITION(bar, baz) AS');
        $this->assertEquals(
            $parser->errors[0]->getMessage(),
            'Unrecognized keyword "AS".'
        );
    }

    public function testError()
    {
        $parser = new Parser(new TokensList());

        $parser->error('error #1', new Token('foo'), 1);
        $parser->error('error #2', new Token('bar'), 2);

        $this->assertEquals(
            $parser->errors, array(
            new ParserException('error #1', new Token('foo'), 1),
            new ParserException('error #2', new Token('bar'), 2),
            )
        );
    }

    /**
     * @expectedException SqlParser\Exceptions\ParserException
     * @expectedExceptionMessage strict error
     * @expectedExceptionCode 3
     */
    public function testErrorStrict()
    {
        $parser = new Parser(new TokensList());
        $parser->strict = true;

        $parser->error('strict error', new Token('foo'), 3);
    }
}
