<?php

namespace SqlParser\Tests\Parser;

use SqlParser\Exceptions\ParserException;
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
            'Unrecognized statement type.',
            $parser->errors[0]->getMessage()
        );
    }

    public function testUnrecognizedKeyword()
    {
        $parser = new Parser('SELECT 1 FROM foo PARTITION(bar, baz) AS');
        $this->assertEquals(
            'Unrecognized keyword.',
            $parser->errors[0]->getMessage()
        );
    }

    /**
     * @runInSeparateProcess
     * @preserveGlobalState disabled
     */
    public function testError()
    {
        $parser = new Parser(new TokensList());

        $parser->error('error #1', new Token('foo'), array(), 1);
        $parser->error('%2$s #%1$d', new Token('bar'), array(2, 'error'), 2);

        $this->assertEquals(
            $parser->errors,
            array(
                new ParserException('error #1', new Token('foo'), 1),
                new ParserException('error #2', new Token('bar'), 2),
            )
        );
    }

    /**
     * @runInSeparateProcess
     * @preserveGlobalState disabled
     */
    public function testErrorTranslate()
    {
        define('TRANSLATE', '\\SqlParser\\Tests\\translate');

        $parser = new Parser(new TokensList());

        $parser->error('TO_TRANSLATE', null);

        $this->assertEquals(
            $parser->errors,
            array(new ParserException('***', null, 0))
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

        $parser->error('strict error', new Token('foo'), array(), 3);
    }
}
