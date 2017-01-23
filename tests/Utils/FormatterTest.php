<?php

namespace PhpMyAdmin\SqlParser\Tests\Utils;

use PhpMyAdmin\SqlParser\Utils\Formatter;
use PhpMyAdmin\SqlParser\Tests\TestCase;

class FormatTest extends TestCase
{
    /**
     * @dataProvider mergeFormats
     */
    public function testMergeFormats($default, $overriding, $expected)
    {
        $formatter = $this->getMockBuilder('PhpMyAdmin\SqlParser\Utils\Formatter')
            ->disableOriginalConstructor()
            ->setMethods(array('getDefaultOptions', 'getDefaultFormats'))
            ->getMock();

        $formatter->expects($this->once())
            ->method('getDefaultOptions')
            ->willReturn(array(
                'type' => 'text',
                'line_ending' => null,
                'indentation' => null,
                'clause_newline' => null,
                'parts_newline' => null,
            ));

        $formatter->expects($this->once())
            ->method('getDefaultFormats')
            ->willReturn($default);

        $expectedOptions = array(
            'type' => 'test-type',
            'line_ending' => '<br>',
            'indentation' => '    ',
            'clause_newline' => null,
            'parts_newline' => 0,
            'formats' => $expected,
        );

        $overridingOptions = array(
            'type' => 'test-type',
            'line_ending' => '<br>',
            'formats' => $overriding,
        );

        $reflectionMethod = new \ReflectionMethod($formatter, 'getMergedOptions');
        $reflectionMethod->setAccessible(true);
        $this->assertEquals($expectedOptions, $reflectionMethod->invoke($formatter, $overridingOptions));
    }

    public function mergeFormats()
    {
        // array($default[], $overriding[], $expected[])
        return array(
            'empty formats' => array(
                'default' => array(
                    array(
                        'type' => 0,
                        'flags' => 0,
                        'html' => '',
                        'cli' => '',
                        'function' => '',
                    ),
                ),
                'overriding' => array(
                    array(
                    ),
                ),
                'expected' => array(
                    array(
                        'type' => 0,
                        'flags' => 0,
                        'html' => '',
                        'cli' => '',
                        'function' => '',
                    ),
                ),
            ),
            'no flags' => array(
                'default' => array(
                    array(
                        'type' => 0,
                        'flags' => 0,
                        'html' => 'html',
                        'cli' => 'cli',
                    ),
                    array(
                        'type' => 0,
                        'flags' => 1,
                        'html' => 'html',
                        'cli' => 'cli',
                    ),
                ),
                'overriding' => array(
                    array(
                        'type' => 0,
                        'html' => 'new html',
                        'cli' => 'new cli',
                    ),
                ),
                'expected' => array(
                    array(
                        'type' => 0,
                        'flags' => 0,
                        'html' => 'new html',
                        'cli' => 'new cli',
                        'function' => '',
                    ),
                    array(
                        'type' => 0,
                        'flags' => 1,
                        'html' => 'html',
                        'cli' => 'cli',
                    ),
                ),
            ),
            'with flags' => array(
                'default' => array(
                    array(
                        'type' => -1,
                        'flags' => 0,
                        'html' => 'html',
                        'cli' => 'cli',
                    ),
                    array(
                        'type' => 0,
                        'flags' => 0,
                        'html' => 'html',
                        'cli' => 'cli',
                    ),
                    array(
                        'type' => 0,
                        'flags' => 1,
                        'html' => 'html',
                        'cli' => 'cli',
                    ),
                ),
                'overriding' => array(
                    array(
                        'type' => 0,
                        'flags' => 0,
                        'html' => 'new html',
                        'cli' => 'new cli',
                    ),
                ),
                'expected' => array(
                    array(
                        'type' => -1,
                        'flags' => 0,
                        'html' => 'html',
                        'cli' => 'cli',
                    ),
                    array(
                        'type' => 0,
                        'flags' => 0,
                        'html' => 'new html',
                        'cli' => 'new cli',
                        'function' => '',
                    ),
                    array(
                        'type' => 0,
                        'flags' => 1,
                        'html' => 'html',
                        'cli' => 'cli',
                    ),
                ),
            ),
            'with extra formats' => array(
                'default' => array(
                    array(
                        'type' => 0,
                        'flags' => 0,
                        'html' => 'html',
                        'cli' => 'cli',
                    ),
                ),
                'overriding' => array(
                    array(
                        'type' => 0,
                        'flags' => 1,
                        'html' => 'new html',
                        'cli' => 'new cli',
                    ),
                    array(
                        'type' => 1,
                        'html' => 'new html',
                        'cli' => 'new cli',
                    ),
                    array(
                        'type' => 1,
                        'flags' => 1,
                        'html' => 'new html',
                        'cli' => 'new cli',
                    ),
                ),
                'expected' => array(
                    array(
                        'type' => 0,
                        'flags' => 0,
                        'html' => 'html',
                        'cli' => 'cli',
                    ),
                    array(
                        'type' => 0,
                        'flags' => 1,
                        'html' => 'new html',
                        'cli' => 'new cli',
                        'function' => '',
                    ),
                    array(
                        'type' => 1,
                        'flags' => 0,
                        'html' => 'new html',
                        'cli' => 'new cli',
                        'function' => '',
                    ),
                    array(
                        'type' => 1,
                        'flags' => 1,
                        'html' => 'new html',
                        'cli' => 'new cli',
                        'function' => '',
                    ),
                ),
            ),
        );
    }

    /**
     * @dataProvider formatQueries_new
     */
    public function testFormat_new($query, $text, $cli, $html, array $options = array())
    {
        // Test TEXT format
        $this->assertEquals($text, Formatter::format($query, array('type' => 'text') + $options));

        // Test CLI format
        $this->assertEquals($cli, Formatter::format($query, array('type' => 'cli') + $options));

        // Test HTML format
        $this->assertEquals($html, Formatter::format($query, array('type' => 'html') + $options));
    }

    public function formatQueries_new()
    {
        return array(
            'empty' => array(
                'query' => '',
                'text' => '',
                'cli' => "\x1b[0m",
                'html' => '',
            ),
            'simply' => array(
                'query' => 'select *',
                'text' =>
                    'SELECT' . "\n" .
                    '    *',
                'cli' =>
                    "\x1b[35mSELECT" . "\n" .
                    "    \x1b[39m*" . "\x1b[0m",
                'html' =>
                    '<span class="sql-reserved">SELECT</span>' . '<br/>' .
                    '&nbsp;&nbsp;&nbsp;&nbsp;*',
            ),
            'comments' => array(
                'query' =>
                    'select /* Comment */ *' . "\n" .
                    'from tbl # Comment' . "\n" .
                    'where 1 -- Comment',
                'text' =>
                    'SELECT' . "\n" .
                    '    /* Comment */ *' . "\n" .
                    'FROM' . "\n" .
                    '    tbl # Comment' . "\n" .
                    'WHERE' . "\n" .
                    '    1 -- Comment',
                'cli' =>
                    "\x1b[35mSELECT" . "\n" .
                    "    \x1b[37m/* Comment */ \x1b[39m*" . "\n" .
                    "\x1b[35mFROM" . "\n" .
                    "    \x1b[39mtbl \x1b[37m# Comment" . "\n" .
                    "\x1b[35mWHERE" . "\n" .
                    "    \x1b[92m1 \x1b[37m-- Comment" . "\x1b[0m",
                'html' =>
                    '<span class="sql-reserved">SELECT</span>' . '<br/>' .
                    '&nbsp;&nbsp;&nbsp;&nbsp;<span class="sql-comment">/* Comment */</span> *' . '<br/>' .
                    '<span class="sql-reserved">FROM</span>' . '<br/>' .
                    '&nbsp;&nbsp;&nbsp;&nbsp;tbl <span class="sql-comment"># Comment</span>' . '<br/>' .
                    '<span class="sql-reserved">WHERE</span>' . '<br/>' .
                    '&nbsp;&nbsp;&nbsp;&nbsp;<span class="sql-number">1</span> <span class="sql-comment">-- Comment</span>',
            ),
            'strip comments' => array(
                'query' =>
                    'select /* Comment */ *' . "\n" .
                    'from tbl # Comment' . "\n" .
                    'where 1 -- Comment',
                'text' =>
                    'SELECT' . "\n" .
                    '    *' . "\n" .
                    'FROM' . "\n" .
                    '    tbl' . "\n" .
                    'WHERE' . "\n" .
                    '    1',
                'cli' =>
                    "\x1b[35mSELECT" . "\n" .
                    "    \x1b[39m*" . "\n" .
                    "\x1b[35mFROM" . "\n" .
                    "    \x1b[39mtbl" . "\n" .
                    "\x1b[35mWHERE" . "\n" .
                    "    \x1b[92m1" . "\x1b[0m",
                'html' =>
                    '<span class="sql-reserved">SELECT</span>' . '<br/>' .
                    '&nbsp;&nbsp;&nbsp;&nbsp;*' . '<br/>' .
                    '<span class="sql-reserved">FROM</span>' . '<br/>' .
                    '&nbsp;&nbsp;&nbsp;&nbsp;tbl' . '<br/>' .
                    '<span class="sql-reserved">WHERE</span>' . '<br/>' .
                    '&nbsp;&nbsp;&nbsp;&nbsp;<span class="sql-number">1</span>',
                'options' => array(
                    'remove_comments' => true,
                ),
            ),
            'keywords' => array(
                'query' => 'select hex("1")',
                'text' =>
                    'SELECT' . "\n" .
                    '    HEX("1")',
                'cli' =>
                    "\x1b[35mSELECT" . "\n" .
                    "    \x1b[95mHEX\x1b[39m(\x1b[91m\"1\"\x1b[39m)" . "\x1b[0m",
                'html' =>
                    '<span class="sql-reserved">SELECT</span>' . '<br/>' .
                    '&nbsp;&nbsp;&nbsp;&nbsp;<span class="sql-keyword">HEX</span>(<span class="sql-string">"1"</span>)',
            ),
            'create procedure' => array(
                'query' => 'create procedure test_procedure() begin from tbl select *; end',
                'text' =>
                    'CREATE' . "\n" .
                    'PROCEDURE test_procedure() BEGIN' . "\n" .
                    'FROM' . "\n" .
                    '    tbl' . "\n" .
                    'SELECT' . "\n" .
                    '    *; END',
                'cli' =>
                    "\x1b[35mCREATE" . "\n" .
                    "\x1b[35mPROCEDURE \x1b[39mtest_procedure\x1b[39m(\x1b[39m) \x1b[95mBEGIN" . "\n" .
                    "\x1b[35mFROM" . "\n" .
                    "    \x1b[39mtbl" . "\n" .
                    "\x1b[35mSELECT" . "\n" .
                    "    \x1b[39m*\x1b[39m; \x1b[95mEND" . "\x1b[0m",
                'html' =>
                    '<span class="sql-reserved">CREATE</span>' . '<br/>' .
                    '<span class="sql-reserved">PROCEDURE</span> test_procedure() <span class="sql-keyword">BEGIN</span>' . '<br/>' .
                    '<span class="sql-reserved">FROM</span>' . '<br/>' .
                    '&nbsp;&nbsp;&nbsp;&nbsp;tbl' . '<br/>' .
                    '<span class="sql-reserved">SELECT</span>' . '<br/>' .
                    '&nbsp;&nbsp;&nbsp;&nbsp;*; <span class="sql-keyword">END</span>',
            ),
            'insert' => array(
                'query' => 'insert into foo values (0, 0, 0), (1, 1, 1)',
                'text' =>
                    'SELECT' . "\n" .
                    '    HEX("1")',
                'cli' =>
                    "\x1b[35mSELECT" . "\n" .
                    "    \x1b[95mHEX\x1b[39m(\x1b[91m\"1\"\x1b[39m)" . "\x1b[0m",
                'html' =>
                    '<span class="sql-reserved">SELECT</span>' . '<br/>' .
                    '&nbsp;&nbsp;&nbsp;&nbsp;<span class="sql-keyword">HEX</span>(<span class="sql-string">"1"</span>)',
            ),
        );
    }

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
            array(  # Covered by 'simply'
                'SELECT 1',
                '<span class="sql-reserved">SELECT</span>' . '<br/>' .
                '&nbsp;&nbsp;&nbsp;&nbsp;<span class="sql-number">1</span>',
                array('type' => 'html'),
            ),
            array(  # Covered by 'comments'
                'SELECT /* Comment */ 1' . "\n" .
                'FROM tbl # Comment' . "\n" .
                'WHERE 1 -- Comment',
                'SELECT' . "\n" .
                '    /* Comment */ 1' . "\n" .
                'FROM' . "\n" .
                '    tbl # Comment' . "\n" .
                'WHERE' . "\n" .
                '    1 -- Comment',
                array('type' => 'text'),
            ),
            array(  # Covered by 'comments'
                'SELECT 1 # Comment',
                '<span class="sql-reserved">SELECT</span>' . '<br/>' .
                '&nbsp;&nbsp;&nbsp;&nbsp;<span class="sql-number">1</span> <span class="sql-comment"># Comment</span>',
                array('type' => 'html'),
            ),
            array(  # Covered by 'comments'
                'SELECT 1 -- comment',
                '<span class="sql-reserved">SELECT</span>' . '<br/>' .
                '&nbsp;&nbsp;&nbsp;&nbsp;<span class="sql-number">1</span> <span class="sql-comment">-- comment</span>',
                array('type' => 'html'),
            ),
            array(  # Covered by 'strip comments'
                'SELECT 1 -- comment',
                '<span class="sql-reserved">SELECT</span>' . '<br/>' .
                '&nbsp;&nbsp;&nbsp;&nbsp;<span class="sql-number">1</span>',
                array('type' => 'html', 'remove_comments' => true),
            ),
            array(  # Covered by 'keywords'
                'SELECT HEX("1")',
                '<span class="sql-reserved">SELECT</span>' . '<br/>' .
                '&nbsp;&nbsp;&nbsp;&nbsp;<span class="sql-keyword">HEX</span>(<span class="sql-string">"1"</span>)',
                array('type' => 'html'),
            ),
            array(
                'SELECT * FROM foo WHERE bar=1',
                '<span class="sql-reserved">SELECT</span>' . '<br/>' .
                '&nbsp;&nbsp;&nbsp;&nbsp;*' . '<br/>' .
                '<span class="sql-reserved">FROM</span>' . '<br/>' .
                '&nbsp;&nbsp;&nbsp;&nbsp;foo' . '<br/>' .
                '<span class="sql-reserved">WHERE</span>' . '<br/>' .
                '&nbsp;&nbsp;&nbsp;&nbsp;bar = <span class="sql-number">1</span>',
                array('type' => 'html'),
            ),
            array(  # Covered by 'create procedure'
                'CREATE PROCEDURE SPTEST() BEGIN FROM a SELECT *; END',
                '<span class="sql-reserved">CREATE</span>' . '<br/>' .
                '<span class="sql-reserved">PROCEDURE</span> SPTEST()' . '<br/>' .
                '<span class="sql-keyword">BEGIN</span>' . '<br/>' .
                '<span class="sql-reserved">FROM</span>' . '<br/>' .
                '&nbsp;&nbsp;&nbsp;&nbsp;a' . '<br/>' .
                '<span class="sql-reserved">SELECT</span>' . '<br/>' .
                '&nbsp;&nbsp;&nbsp;&nbsp;*;' . '<br/>' .
                '<span class="sql-keyword">END</span>',
                array('type' => 'html'),
            ),
            array(  # Covered by 'insert'
                'INSERT INTO foo VALUES (0, 0, 0), (1, 1, 1)',
                '<span class="sql-reserved">INSERT</span>' . '<br/>' .
                '<span class="sql-reserved">INTO</span>' . '<br/>' .
                '&nbsp;&nbsp;&nbsp;&nbsp;foo' . '<br/>' .
                '<span class="sql-reserved">VALUES</span>' .
                '(<span class="sql-number">0</span>, <span class="sql-number">0</span>, <span class="sql-number">0</span>),' .
                '(<span class="sql-number">1</span>, <span class="sql-number">1</span>, <span class="sql-number">1</span>)',
                array('type' => 'html'),
            ),
            array(  # Covered by 'simply'
                'SELECT 1',
                "\x1b[35mSELECT\n    \x1b[92m1\x1b[0m",
                array('type' => 'cli'),
            ),
            array(
                'SELECT "Text" AS BAR',
                "\x1b[35mSELECT\n    \x1b[91m\"Text\" \x1b[35mAS \x1b[39mBAR\x1b[0m",
                array('type' => 'cli'),
            ),
            array(
                'SELECT coditm AS Item, descripcion AS Descripcion, contenedores AS Contenedores, IF(suspendido = 1, Si, NO) AS Suspendido FROM `DW_articulos` WHERE superado = 0',
                '<span class="sql-reserved">SELECT</span>' . '<br/>' .
                '&nbsp;&nbsp;&nbsp;&nbsp;coditm <span class="sql-reserved">AS</span> Item,' . '<br/>' .
                '&nbsp;&nbsp;&nbsp;&nbsp;descripcion <span class="sql-reserved">AS</span> Descripcion,' . '<br/>' .
                '&nbsp;&nbsp;&nbsp;&nbsp;contenedores <span class="sql-reserved">AS</span> Contenedores,' . '<br/>' .
                '&nbsp;&nbsp;&nbsp;&nbsp;<span class="sql-reserved">IF</span>(suspendido = <span class="sql-number">1</span>, Si, <span class="sql-keyword">NO</span>) <span class="sql-reserved">AS</span> Suspendido' . '<br/>' .
                '<span class="sql-reserved">FROM</span>' . '<br/>' .
                '&nbsp;&nbsp;&nbsp;&nbsp;<span class="sql-variable">`DW_articulos`</span>' . '<br/>' .
                '<span class="sql-reserved">WHERE</span>' . '<br/>' .
                '&nbsp;&nbsp;&nbsp;&nbsp;superado = <span class="sql-number">0</span>',
                array('type' => 'html'),
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
                '&nbsp;&nbsp;&nbsp;&nbsp;<span class="sql-variable">`id`</span> <span class="sql-reserved">INT</span>(<span class="sql-number">11</span>) <span class="sql-reserved">NOT NULL</span> <span class="sql-keyword">AUTO_INCREMENT</span>,' . '<br/>' .
                '&nbsp;&nbsp;&nbsp;&nbsp;<span class="sql-variable">`dbase`</span> <span class="sql-reserved">VARCHAR</span>(<span class="sql-number">255</span>) <span class="sql-reserved">NOT NULL</span> <span class="sql-reserved">DEFAULT</span> <span class="sql-string">""</span>,' . '<br/>' .
                '&nbsp;&nbsp;&nbsp;&nbsp;<span class="sql-variable">`user`</span> <span class="sql-reserved">VARCHAR</span>(<span class="sql-number">255</span>) <span class="sql-reserved">NOT NULL</span> <span class="sql-reserved">DEFAULT</span> <span class="sql-string">""</span>,' . '<br/>' .
                '&nbsp;&nbsp;&nbsp;&nbsp;<span class="sql-variable">`label`</span> <span class="sql-reserved">VARCHAR</span>(<span class="sql-number">255</span>) <span class="sql-reserved">COLLATE</span> utf8_general_ci <span class="sql-reserved">NOT NULL</span> <span class="sql-reserved">DEFAULT</span> <span class="sql-string">""</span>,' . '<br/>' .
                '&nbsp;&nbsp;&nbsp;&nbsp;<span class="sql-variable">`query`</span> <span class="sql-keyword">TEXT</span> <span class="sql-reserved">NOT NULL</span>,' . '<br/>' .
                '&nbsp;&nbsp;&nbsp;&nbsp;<span class="sql-reserved">PRIMARY KEY</span>(<span class="sql-variable">`id`</span>)',
                array('type' => 'html'),
            ),
            array(
                "select '<s>xss' from `<s>xss` , <s>nxss /*s<s>xss*/",
                '<span class="sql-reserved">SELECT</span>' . '<br/>' .
                '&nbsp;&nbsp;&nbsp;&nbsp;<span class="sql-string">\'&lt;s&gt;xss\'</span>' . '<br/>' .
                '<span class="sql-reserved">FROM</span>' . '<br/>' .
                '&nbsp;&nbsp;&nbsp;&nbsp;<span class="sql-variable">`&lt;s&gt;xss`</span>,' . '<br/>' .
                '&nbsp;&nbsp;&nbsp;&nbsp;&lt; s &gt; nxss <span class="sql-comment">/*s&lt;s&gt;xss*/</span>',
                array('type' => 'html'),
            ),
            array(
                "select 'text\x1b[33mcolor-inj' from tbl",
                "\x1b[35mSELECT\n    \x1b[91m'text\\x1B[33mcolor-inj'\n\x1b[35mFROM\n    \x1b[39mtbl\x1b[0m",
                array('type' => 'cli'),
            ),
        );
    }
}
