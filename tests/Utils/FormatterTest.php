<?php

namespace SqlParser\Tests\Utils;

use SqlParser\Utils\Formatter;

use SqlParser\Tests\TestCase;

class FormatTest extends TestCase
{
    /**
     * @dataProvider formatQueries
     */
    public function testFormat($query, $expected)
    {
        $this->assertEquals(
            $expected,
            Formatter::format($query, array('type' => 'html'))
        );
    }

    public function formatQueries()
    {
        return array(
            array(
                'SELECT 1',
                '<span class="sql-reserved">SELECT</span>' . "\n" .
                '  <span class="sql-number">1</span>'
            ),
            array(
                'SELECT 1 # Comment',
                '<span class="sql-reserved">SELECT</span>' . "\n" .
                '  <span class="sql-number">1</span> <span class="sql-comment"># Comment' . "\n" .
                '</span>'
            ),
            array(
                'SELECT HEX("1")',
                '<span class="sql-reserved">SELECT</span>' . "\n" .
                '  <span class="sql-keyword">HEX</span>(<span class="sql-string">"1"</span>)'
            ),
            array(
                'SELECT * FROM foo WHERE bar=1',
                '<span class="sql-reserved">SELECT</span>' . "\n" .
                '  *' . "\n" .
                '<span class="sql-reserved">FROM</span>' . "\n" .
                '  foo' . "\n" .
                '<span class="sql-reserved">WHERE</span>' . "\n" .
                '  bar = <span class="sql-number">1</span>'
            ),
        );
    }
}
