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

        // Restoring context.
        Context::load('');
        $this->assertEquals('\\SqlParser\\Contexts\\ContextMySql50700', Context::$defaultContext);
        $this->assertTrue(isset(Context::$KEYWORDS['STORED']));
        $this->assertFalse(isset(Context::$KEYWORDS['AUTHORS']));
    }

    /**
     * @expectedException Exception
     * @expectedExceptionMessage Specified context ("\SqlParser\Contexts\ContextFoo") doesn't exist.
     */
    public function testLoadError()
    {
        Context::load('Foo');
    }
}
