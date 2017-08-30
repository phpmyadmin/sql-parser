<?php

namespace PhpMyAdmin\SqlParser\Tests\Lexer;

use PhpMyAdmin\SqlParser\Context;
use PhpMyAdmin\SqlParser\Tests\TestCase;

class ContextTest extends TestCase
{
    public function testLoad()
    {
        // Default context is 5.7.0.
        $this->assertEquals('\\PhpMyAdmin\\SqlParser\\Contexts\\ContextMySql50700', Context::$loadedContext);
        $this->assertTrue(isset(Context::$KEYWORDS['STORED']));
        $this->assertFalse(isset(Context::$KEYWORDS['AUTHORS']));

        Context::load('MySql50600');
        $this->assertEquals('\\PhpMyAdmin\\SqlParser\\Contexts\\ContextMySql50600', Context::$loadedContext);
        $this->assertFalse(isset(Context::$KEYWORDS['STORED']));
        $this->assertTrue(isset(Context::$KEYWORDS['AUTHORS']));

        Context::loadClosest('MySql50712');
        $this->assertEquals('\\PhpMyAdmin\\SqlParser\\Contexts\\ContextMySql50700', Context::$loadedContext);

        $this->assertEquals(null, Context::loadClosest('Sql'));

        // Restoring context.
        Context::load('');
        $this->assertEquals('\\PhpMyAdmin\\SqlParser\\Contexts\\ContextMySql50700', Context::$defaultContext);
        $this->assertTrue(isset(Context::$KEYWORDS['STORED']));
        $this->assertFalse(isset(Context::$KEYWORDS['AUTHORS']));
    }

    /**
     * @dataProvider contextNames
     */
    public function testLoadAll($context)
    {
        Context::load($context);
        $this->assertEquals('\\PhpMyAdmin\\SqlParser\\Contexts\\Context' . $context, Context::$loadedContext);

        // Restoring context.
        Context::load('');
    }

    public function contextNames()
    {
        return array(
            array('MySql50000'),
            array('MySql50100'),
            array('MySql50500'),
            array('MySql50600'),
            array('MySql50700'),
            array('MySql80000'),
            array('MariaDb100000'),
            array('MariaDb100100'),
            array('MariaDb100200'),
        );
    }

    /**
     * @expectedException \Exception
     * @expectedExceptionMessage Specified context ("\PhpMyAdmin\SqlParser\Contexts\ContextFoo") does not exist.
     */
    public function testLoadError()
    {
        Context::load('Foo');
    }

    public function testMode()
    {
        Context::setMode('REAL_AS_FLOAT,ANSI_QUOTES,IGNORE_SPACE');
        $this->assertEquals(
            Context::SQL_MODE_REAL_AS_FLOAT | Context::SQL_MODE_ANSI_QUOTES | Context::SQL_MODE_IGNORE_SPACE,
            Context::$MODE
        );
        Context::setMode('TRADITIONAL');
        $this->assertEquals(
            Context::SQL_MODE_TRADITIONAL,
            Context::$MODE
        );
        Context::setMode();
        $this->assertEquals(0, Context::$MODE);
    }

    public function testEscape()
    {
        Context::setMode('NO_ENCLOSING_QUOTES');
        $this->assertEquals('test', Context::escape('test'));

        Context::setMode('ANSI_QUOTES');
        $this->assertEquals('"test"', Context::escape('test'));

        Context::setMode();
        $this->assertEquals('`test`', Context::escape('test'));

        $this->assertEquals(
            array('`a`', '`b`'),
            Context::escape(array('a', 'b'))
        );
    }
}
