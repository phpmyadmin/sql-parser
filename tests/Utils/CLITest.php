<?php

namespace SqlParser\Tests\Utils;

use SqlParser\Tests\TestCase;

class CLITest extends TestCase
{
    private function getCLI($getopt)
    {
        $cli = $this->getMockBuilder('SqlParser\Utils\CLI')->setMethods(array('getopt'))->getMock();
        $cli->method('getopt')->willReturn($getopt);
        return $cli;
    }

    /**
     * @dataProvider highlightParams
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
                "\x1b[35mSELECT\n  \x1b[92m1\x1b[0m\n",
                0,
            ),
            array(
                array('query' => 'SELECT 1'),
                "\x1b[35mSELECT\n  \x1b[92m1\x1b[0m\n",
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
                '  <span class="sql-number">1</span>' . "\n",
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
}
