<?php

require('vendor/autoload.php');

use SqlParser\Lexer;
use SqlParser\Parser;
use SqlParser\Token;

abstract class TestCase extends PHPUnit_Framework_TestCase
{

    /**
     * Gets test's input and expected output.
     *
     * @param string $name
     *
     * @return array
     */
    public function getData($name)
    {
        $input = file_get_contents('tests/data/' . $name . '.in');
        $output = unserialize(file_get_contents('tests/data/' . $name . '.out'));
        return array($input, $output);
    }

    /**
     * Tests the `Lexer`.
     *
     * @param string $name
     *
     * @return Lexer
     */
    public function runLexerTest($name)
    {
        list($input, $output) = $this->getData($name);

        $lexer = new Lexer($input);
        $lexer->lex();

        $this->assertEquals($output, $lexer);

        return $lexer;
    }

    /**
     * Tests the `Parser`.
     *
     * @param string $name
     *
     * @return Parser
     */
    public function runParserTest($name)
    {
        list($input, $output) = $this->getData($name);

        $lexer = new Lexer($input);
        $lexer->lex();

        $parser = new Parser($lexer->tokens);
        $parser->parse();

        $this->assertEquals($output, $parser);

        return $parser;
    }
}
