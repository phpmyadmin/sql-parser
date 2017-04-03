<?php

namespace PhpMyAdmin\SqlParser\Tests\Utils;

use PhpMyAdmin\SqlParser\Tests\TestCase;

class CLITest extends TestCase
{
    private function getCLI($getopt)
    {
        $cli = $this->getMockBuilder('PhpMyAdmin\SqlParser\Utils\CLI')->setMethods(array('getopt'))->getMock();
        $cli->method('getopt')->willReturn($getopt);

        return $cli;
    }

    /**
     * Test that getopt call works.
     *
     * We do mock it for other tests to return values we want.
     */
    public function testGetopt()
    {
        $cli = new \PhpMyAdmin\SqlParser\Utils\CLI();
        $this->assertEquals(
            $cli->getopt('', array()),
            array()
        );
    }

    /**
     * @dataProvider highlightParams
     *
     * @param mixed $getopt
     * @param mixed $output
     * @param mixed $result
     */
    public function testRunHighlight($getopt, $output, $result)
    {
        $cli = $this->getCLI($getopt);
        $this->expectOutputString($output);
        $this->assertEquals($result, $cli->runHighlight());
    }

    public function highlightParams()
    {
        return array(
            array(
                array('q' => 'SELECT 1'),
                "\x1b[35mSELECT\n    \x1b[92m1\x1b[0m\n",
                0,
            ),
            array(
                array('query' => 'SELECT 1'),
                "\x1b[35mSELECT\n    \x1b[92m1\x1b[0m\n",
                0,
            ),
            array(
                array('q' => 'SELECT /* comment */ 1 /* other */', 'f' => 'text'),
                "SELECT\n    /* comment */ 1 /* other */\n",
                0,
            ),
            array(
                array('q' => 'SELECT 1', 'f' => 'foo'),
                "ERROR: Invalid value for format!\n",
                1,
            ),
            array(
                array('q' => 'SELECT 1', 'f' => 'html'),
                '<span class="sql-reserved">SELECT</span>' . '<br/>' .
                '&nbsp;&nbsp;&nbsp;&nbsp;<span class="sql-number">1</span>' . "\n",
                0,
            ),
            array(
                array('h' => true),
                'Usage: highlight-query --query SQL [--format html|cli|text]' . "\n",
                0,
            ),
            array(
                array(),
                'ERROR: Missing parameters!' . "\n" .
                'Usage: highlight-query --query SQL [--format html|cli|text]' . "\n",
                1,
            ),
            array(
                false,
                '',
                1,
            ),
        );
    }

    /**
     * @dataProvider lintParams
     *
     * @param mixed $getopt
     * @param mixed $output
     * @param mixed $result
     */
    public function testRunLint($getopt, $output, $result)
    {
        $cli = $this->getCLI($getopt);
        $this->expectOutputString($output);
        $this->assertEquals($result, $cli->runLint());
    }

    public function lintParams()
    {
        return array(
            array(
                array('q' => 'SELECT 1'),
                '',
                0,
            ),
            array(
                array('query' => 'SELECT 1'),
                '',
                0,
            ),
            array(
                array('q' => 'SELECT SELECT'),
                '#1: An expression was expected. (near "SELECT" at position 7)' . "\n" .
                '#2: This type of clause was previously parsed. (near "SELECT" at position 7)' . "\n" .
                '#3: An expression was expected. (near "" at position 0)' . "\n",
                10,
            ),
            array(
                array('h' => true),
                'Usage: lint-query --query SQL' . "\n",
                0,
            ),
            array(
                array(),
                'ERROR: Missing parameters!' . "\n" .
                'Usage: lint-query --query SQL' . "\n",
                1,
            ),
            array(
                false,
                '',
                1,
            ),
        );
    }

    /**
     * @dataProvider tokenizeParams
     *
     * @param mixed $getopt
     * @param mixed $output
     * @param mixed $result
     */
    public function testRunTokenize($getopt, $output, $result)
    {
        $cli = $this->getCLI($getopt);
        $this->expectOutputString($output);
        $this->assertEquals($result, $cli->runTokenize());
    }

    public function tokenizeParams()
    {
        $result = (
            "[TOKEN 0]\nType = 1\nFlags = 3\nValue = 'SELECT'\nToken = 'SELECT'\n\n"
            . "[TOKEN 1]\nType = 3\nFlags = 0\nValue = ' '\nToken = ' '\n\n"
            . "[TOKEN 2]\nType = 6\nFlags = 0\nValue = 1\nToken = '1'\n\n"
            . "[TOKEN 3]\nType = 9\nFlags = 0\nValue = NULL\nToken = NULL\n\n"
        );

        return array(
            array(
                array('q' => 'SELECT 1'),
                $result,
                0,
            ),
            array(
                array('query' => 'SELECT 1'),
                $result,
                0,
            ),
            array(
                array('h' => true),
                'Usage: tokenize-query --query SQL' . "\n",
                0,
            ),
            array(
                array(),
                'ERROR: Missing parameters!' . "\n" .
                'Usage: tokenize-query --query SQL' . "\n",
                1,
            ),
            array(
                false,
                '',
                1,
            ),
        );
    }
}
