<?php

namespace SqlParser\Tests\Lexer;

use SqlParser\Context;

use SqlParser\Tests\TestCase;

class ContextTest extends TestCase
{

    public function testLoad()
    {
        // Default context is 5.7.0.
        $this->assertEquals('\\SqlParser\\Contexts\\ContextMySql50700', Context::$loadedContext);
        $this->assertTrue(isset(Context::$KEYWORDS['STORED']));
        $this->assertFalse(isset(Context::$KEYWORDS['AUTHORS']));

        Context::load('MySql50600');
        $this->assertEquals('\\SqlParser\\Contexts\\ContextMySql50600', Context::$loadedContext);
        $this->assertFalse(isset(Context::$KEYWORDS['STORED']));
        $this->assertTrue(isset(Context::$KEYWORDS['AUTHORS']));

        Context::loadClosest('MySql50712');
        $this->assertEquals('\\SqlParser\\Contexts\\ContextMySql50700', Context::$loadedContext);

        $this->assertEquals(null, Context::loadClosest('Sql'));

        // Restoring context.
        Context::load('');
        $this->assertEquals('\\SqlParser\\Contexts\\ContextMySql50700', Context::$defaultContext);
        $this->assertTrue(isset(Context::$KEYWORDS['STORED']));
        $this->assertFalse(isset(Context::$KEYWORDS['AUTHORS']));
    }

    /**
     * @expectedException Exception
     * @expectedExceptionMessage Specified context ("\SqlParser\Contexts\ContextFoo") does not exist.
     */
    public function testLoadError()
    {
        Context::load('Foo');
    }

    public function testMode()
    {
        Context::setMode('REAL_AS_FLOAT,ANSI_QUOTES,IGNORE_SPACE');
        $this->assertEquals(
            Context::REAL_AS_FLOAT | Context::ANSI_QUOTES | Context::IGNORE_SPACE,
            Context::$MODE
        );
        Context::setMode();
        $this->assertEquals(0, Context::$MODE);
    }

    public function testEscape()
    {
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
