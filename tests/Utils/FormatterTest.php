<?php

namespace SqlParser\Tests\Utils;

use SqlParser\Utils\Formatter;

use SqlParser\Tests\TestCase;

class FormatTest extends TestCase
{
    /**
     * @dataProvider formatQueries
     */
    public function testFormat($query, $expected, $options)
    {
        $this->assertEquals(
            $expected,
            Formatter::format($query, $options)
        );
    }

    public function formatQueries()
    {
        return array(
            array(
                'SELECT 1',
                '<span class="sql-reserved">SELECT</span>' . '<br/>' .
                '  <span class="sql-number">1</span>',
                array('type' => 'html')
            ),
            array(
                'SELECT 1 # Comment',
                '<span class="sql-reserved">SELECT</span>' . '<br/>' .
                '  <span class="sql-number">1</span> <span class="sql-comment"># Comment' . "\n" .
                '</span>',
                array('type' => 'html')
            ),
            array(
                'SELECT HEX("1")',
                '<span class="sql-reserved">SELECT</span>' . '<br/>' .
                '  <span class="sql-keyword">HEX</span>(<span class="sql-string">"1"</span>)',
                array('type' => 'html')
            ),
            array(
                'SELECT * FROM foo WHERE bar=1',
                '<span class="sql-reserved">SELECT</span>' . '<br/>' .
                '  *' . '<br/>' .
                '<span class="sql-reserved">FROM</span>' . '<br/>' .
                '  foo' . '<br/>' .
                '<span class="sql-reserved">WHERE</span>' . '<br/>' .
                '  bar = <span class="sql-number">1</span>',
                array('type' => 'html')
            ),
            array(
                'CREATE PROCEDURE SPTEST() BEGIN FROM a SELECT *; END',
                '<span class="sql-reserved">CREATE</span>' . '<br/>' .
                '<span class="sql-reserved">PROCEDURE</span> SPTEST()' . '<br/>' .
                '<span class="sql-keyword">BEGIN</span>' . '<br/>' .
                '<span class="sql-reserved">FROM</span>' . '<br/>' .
                '  a' . '<br/>' .
                '<span class="sql-reserved">SELECT</span>' . '<br/>' .
                '  *;' . '<br/>' .
                '<span class="sql-keyword">END</span>',
                array('type' => 'html')
            ),
            array(
                'INSERT INTO foo VALUES (0, 0, 0), (1, 1, 1)',
                '<span class="sql-reserved">INSERT</span>' . '<br/>' .
                '<span class="sql-reserved">INTO</span>' . '<br/>' .
                '  foo' . '<br/>' .
                '<span class="sql-reserved">VALUES</span>' .
                '(<span class="sql-number">0</span>, <span class="sql-number">0</span>, <span class="sql-number">0</span>),' .
                '(<span class="sql-number">1</span>, <span class="sql-number">1</span>, <span class="sql-number">1</span>)',
                array('type' => 'html')
            ),
            array(
                'SELECT 1',
                "\x1b[35mSELECT\n  \x1b[92m1\x1b[0m",
                array('type' => 'cli')
            ),
            array(
                'SELECT "Text" AS BAR',
                "\x1b[35mSELECT\n  \x1b[91m\"Text\" \x1b[35mAS \x1b[39mBAR\x1b[0m",
                array('type' => 'cli')
            ),
            array(
                'SELECT coditm AS Item, descripcion AS Descripcion, contenedores AS Contenedores, IF(suspendido = 1, Si, NO) AS Suspendido FROM `DW_articulos` WHERE superado = 0',
                '<span class="sql-reserved">SELECT</span>' . '<br/>' .
                '  coditm <span class="sql-reserved">AS</span> Item,' . '<br/>' .
                '  descripcion <span class="sql-reserved">AS</span> Descripcion,' . '<br/>' .
                '  contenedores <span class="sql-reserved">AS</span> Contenedores,' . '<br/>' .
                '  <span class="sql-reserved">IF</span>(suspendido = <span class="sql-number">1</span>, Si, <span class="sql-keyword">NO</span>) <span class="sql-reserved">AS</span> Suspendido' . '<br/>' .
                '<span class="sql-reserved">FROM</span>' . '<br/>' .
                '  <span class="sql-variable">`DW_articulos`</span>' . '<br/>' .
                '<span class="sql-reserved">WHERE</span>' . '<br/>' .
                '  superado = <span class="sql-number">0</span>',
                array('type' => 'html'),
            ),
            array(
                'SELECT 1 -- comment',
                '<span class="sql-reserved">SELECT</span>' . '<br/>' .
                '  <span class="sql-number">1</span> <span class="sql-comment">-- comment' . "\n" .
                '</span>',
                array('type' => 'html'),
            ),
            array(
                'SELECT 1 -- comment',
                '<span class="sql-reserved">SELECT</span>' . '<br/>' .
                '  <span class="sql-number">1</span>',
                array('type' => 'html', 'remove_comments' => true),
            ),
            array(
                'CREATE TABLE IF NOT EXISTS `pma__bookmark` (' . "\n" .
                '  `id` int(11) NOT NULL auto_increment,' . "\n" .
                '  `dbase` varchar(255) NOT NULL default "",' . "\n" .
                '  `user` varchar(255) NOT NULL default "",' . "\n" .
                '  `label` varchar(255) COLLATE utf8_general_ci NOT NULL default "",' . "\n" .
                '  `query` text NOT NULL,' . "\n" .
                '  PRIMARY KEY  (`id`)' . "\n",
                '<span class="sql-reserved">CREATE</span> <span class="sql-reserved">TABLE</span> <span class="sql-reserved">IF NOT EXISTS</span> <span class="sql-variable">`pma__bookmark`</span>(' . '<br/>' .
                '  <span class="sql-variable">`id`</span> <span class="sql-reserved">INT</span>(<span class="sql-number">11</span>) <span class="sql-reserved">NOT NULL</span> <span class="sql-keyword">AUTO_INCREMENT</span>,' . '<br/>' .
                '  <span class="sql-variable">`dbase`</span> <span class="sql-reserved">VARCHAR</span>(<span class="sql-number">255</span>) <span class="sql-reserved">NOT NULL</span> <span class="sql-reserved">DEFAULT</span> <span class="sql-string">""</span>,' . '<br/>' .
                '  <span class="sql-variable">`user`</span> <span class="sql-reserved">VARCHAR</span>(<span class="sql-number">255</span>) <span class="sql-reserved">NOT NULL</span> <span class="sql-reserved">DEFAULT</span> <span class="sql-string">""</span>,' . '<br/>' .
                '  <span class="sql-variable">`label`</span> <span class="sql-reserved">VARCHAR</span>(<span class="sql-number">255</span>) <span class="sql-reserved">COLLATE</span> utf8_general_ci <span class="sql-reserved">NOT NULL</span> <span class="sql-reserved">DEFAULT</span> <span class="sql-string">""</span>,' . '<br/>' .
                '  <span class="sql-variable">`query`</span> <span class="sql-keyword">TEXT</span> <span class="sql-reserved">NOT NULL</span>,' . '<br/>' .
                '  <span class="sql-reserved">PRIMARY KEY</span>(<span class="sql-variable">`id`</span>)',
                array('type' => 'html'),
            ),
            array(
                "select '<s>xss' from `<s>xss` , <s>nxss /*s<s>xss*/",
                '<span class="sql-reserved">SELECT</span><br/>  <span class="sql-string">\'&lt;s&gt;xss\'</span><br/><span class="sql-reserved">FROM</span><br/>  <span class="sql-variable">`&lt;s&gt;xss`</span>,<br/>  &lt; s &gt; nxss <span class="sql-comment">/*s&lt;s&gt;xss*/</span>',
                array('type' => 'html'),
            ),
            array(
                "select 'text\x1b[33mcolor-inj' from tbl",
                "\x1b[35mSELECT\n  \x1b[91m'text\\x1B[33mcolor-inj'\n\x1b[35mFROM\n  \x1b[39mtbl\x1b[0m",
                array('type' => 'cli'),
            ),
        );
    }
}
