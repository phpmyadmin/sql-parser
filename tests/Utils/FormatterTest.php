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
                "\x1b[35mSELECT\n  \x1b[92m1\x1b[0m",
                'cli'
            ),
            array(
                'SELECT "Text" AS BAR',
                "\x1b[35mSELECT\n  \x1b[91m\"Text\" \x1b[35mAS \x1b[39mBAR\x1b[0m",
                'cli'
            ),
            array(
                'SELECT coditm AS Item, descripcion AS Descripcion, contenedores AS Contenedores, IF(suspendido = 1, Si, NO) AS Suspendido FROM `DW_articulos` WHERE superado = 0',
                '<span class="sql-reserved">SELECT</span>' . "\n" .
                '  coditm <span class="sql-reserved">AS</span> Item,' . "\n" .
                '  descripcion <span class="sql-reserved">AS</span> Descripcion,' . "\n" .
                '  contenedores <span class="sql-reserved">AS</span> Contenedores,' . "\n" .
                '  <span class="sql-reserved">IF</span>(suspendido = <span class="sql-number">1</span>, Si, <span class="sql-keyword">NO</span>) <span class="sql-reserved">AS</span> Suspendido' . "\n" .
                '<span class="sql-reserved">FROM</span>' . "\n" .
                '  <span class="sql-variable">`DW_articulos`</span>' . "\n" .
                '<span class="sql-reserved">WHERE</span>' . "\n" .
                '  superado = <span class="sql-number">0</span>',
                'html',
            ),
        );
    }
}
