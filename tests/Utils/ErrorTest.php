<?php

namespace SqlParser\Tests\Utils;

use SqlParser\Lexer;
use SqlParser\Parser;
use SqlParser\Utils\Error;

use SqlParser\Tests\TestCase;

class ErrorTest extends TestCase
{

    public function testGet()
    {
        $lexer = new Lexer('SELECT * FROM db..tbl $');
        $parser = new Parser($lexer->list);
        $this->assertEquals(
            array(
                array('Unexpected character.', 0, '$', 22),
                array('Unexpected dot.', 0, '.', 17),
            ),
            Error::get(array($lexer, $parser))
        );
    }

    public function testFormat()
    {
        $this->assertEquals(
            array('#1: error msg (near "token" at position 100)'),
            Error::format(array(array('error msg', 42, 'token', 100)))
        );
    }
}
