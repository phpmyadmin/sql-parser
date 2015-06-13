<?php

namespace SqlParser\Tests;

require('vendor/autoload.php');

use SqlParser\Lexer;
use SqlParser\Parser;
use SqlParser\Token;

/**
 * Implements useful methods for testing.
 *
 * Each test consists of a string that represents the serialized Lexer or Parser
 * instance. Because exceptions include information like file name, which may
 * change due to environment's configuration, their information is extracted
 * in an array which is serialized.
 *
 * For example, a parser test consists of an array with two keys, `parser`
 * which holds the Parser instance, without errors and the `errors` key which
 * holds the array that was previously extracted.
 */
abstract class TestCase extends \PHPUnit_Framework_TestCase
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

        $errors = array();
        foreach ($lexer->errors as $err) {
            $errors[] = array($err->getMessage(), $err->ch, $err->pos, $err->getCode());
        }
        $lexer->errors = array();

        $this->assertEquals($output['errors'], $errors);
        $this->assertEquals($output['lexer'], $lexer);

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
        $parser = new Parser($lexer->tokens);

        $errors = array();
        foreach ($parser->errors as $err) {
            $errors[] = array($err->getMessage(), $err->token, $err->getCode());
        }
        $parser->errors = array();

        $this->assertEquals($output['errors'], $errors);
        $this->assertEquals($output['parser'], $parser);

        return $parser;
    }
}
