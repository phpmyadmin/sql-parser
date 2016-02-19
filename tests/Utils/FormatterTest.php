<?php

namespace SqlParser\Tests\Utils;

use SqlParser\Utils\Formatter;

use SqlParser\Tests\TestCase;

class FormatTest extends TestCase
{
    /**
     * @dataProvider formatQueries
     */
    public function testFormat($query, $expected, $type)
    {
        $this->assertEquals(
            $expected,
            Formatter::format($query, array('type' => $type))
        );
    }

    public function formatQueries()
    {
        return array(
            array(
                'SELECT 1',
                '<span class="sql-reserved">SELECT</span>' . "\n" .
                '  <span class="sql-number">1</span>',
                'html'
            ),
            array(
                'SELECT 1 # Comment',
                '<span class="sql-reserved">SELECT</span>' . "\n" .
                '  <span class="sql-number">1</span> <span class="sql-comment"># Comment' . "\n" .
                '</span>',
                'html'
            ),
            array(
                'SELECT HEX("1")',
                '<span class="sql-reserved">SELECT</span>' . "\n" .
                '  <span class="sql-keyword">HEX</span>(<span class="sql-string">"1"</span>)',
                'html'
            ),
            array(
                'SELECT * FROM foo WHERE bar=1',
                '<span class="sql-reserved">SELECT</span>' . "\n" .
                '  *' . "\n" .
                '<span class="sql-reserved">FROM</span>' . "\n" .
                '  foo' . "\n" .
                '<span class="sql-reserved">WHERE</span>' . "\n" .
                '  bar = <span class="sql-number">1</span>',
                'html'
            ),
            array(
                'CREATE PROCEDURE SPTEST() BEGIN FROM a SELECT *; END',
                '<span class="sql-reserved">CREATE</span>' . "\n" .
                '<span class="sql-reserved">PROCEDURE</span> SPTEST()' . "\n" .
                '<span class="sql-keyword">BEGIN</span>' . "\n" .
                '<span class="sql-reserved">FROM</span>' . "\n" .
                '  a' . "\n" .
                '<span class="sql-reserved">SELECT</span>' . "\n" .
                '  *;' . "\n" .
                '<span class="sql-keyword">END</span>',
                'html'
            ),
            array(
                'INSERT INTO foo VALUES (0, 0, 0), (1, 1, 1)',
                '<span class="sql-reserved">INSERT</span>' . "\n" .
                '<span class="sql-reserved">INTO</span>' . "\n" .
                '  foo' . "\n" .
                '<span class="sql-reserved">VALUES</span>' .
                '(<span class="sql-number">0</span>, <span class="sql-number">0</span>, <span class="sql-number">0</span>),' .
                '(<span class="sql-number">1</span>, <span class="sql-number">1</span>, <span class="sql-number">1</span>)',
                'html'
            ),
            array(
                'SELECT 1',
                "\x1b[35mSELECT\n  \x1b[92m1",
                'cli'
            ),
        );
    }
}
