<?php

declare(strict_types=1);

namespace PhpMyAdmin\SqlParser\Tests\Lexer;

use PhpMyAdmin\SqlParser\Context;
use PhpMyAdmin\SqlParser\SqlModes;
use PhpMyAdmin\SqlParser\Tests\TestCase;
use Throwable;

use function class_exists;

class ContextTest extends TestCase
{
    public function testLoad(): void
    {
        // Default context is 5.7.0.
        $this->assertEquals('\\PhpMyAdmin\\SqlParser\\Contexts\\ContextMySql50700', Context::$loadedContext);
        $this->assertArrayHasKey('STORED', Context::$KEYWORDS);
        $this->assertArrayNotHasKey('AUTHORS', Context::$KEYWORDS);

        // Restoring context.
        Context::load('');
        $this->assertEquals('\\PhpMyAdmin\\SqlParser\\Contexts\\ContextMySql50700', Context::$defaultContext);
        $this->assertArrayHasKey('STORED', Context::$KEYWORDS);
        $this->assertArrayNotHasKey('AUTHORS', Context::$KEYWORDS);
    }

    /**
     * Test for loading closest SQL context
     *
     * @dataProvider contextLoadingProvider
     */
    public function testLoadClosest(string $context, ?string $expected): void
    {
        $this->assertEquals($expected, Context::loadClosest($context));
        if ($expected !== null) {
            $this->assertEquals('\\PhpMyAdmin\\SqlParser\\Contexts\\Context' . $expected, Context::$loadedContext);
            $this->assertTrue(class_exists(Context::$loadedContext));
        }

        // Restoring context.
        Context::load('');
    }

    /**
     * @return array<string, array<int, string|null>>
     * @psalm-return array<string, array{string, (string|null)}>
     */
    public function contextLoadingProvider(): array
    {
        return [
            'MySQL match' => [
                'MySql50500',
                'MySql50500',
            ],
            'MySQL strip' => [
                'MySql50712',
                'MySql50700',
            ],
            'MySQL fallback' => [
                'MySql99999',
                'MySql50700',
            ],
            'MariaDB match' => [
                'MariaDb100000',
                'MariaDb100000',
            ],
            'MariaDB stripg' => [
                'MariaDb109900',
                'MariaDb100000',
            ],
            'MariaDB fallback' => [
                'MariaDb990000',
                'MariaDb100300',
            ],
            'Invalid' => [
                'Sql',
                null,
            ],
        ];
    }

    /**
     * @dataProvider contextNamesProvider
     */
    public function testLoadAll(string $context): void
    {
        Context::load($context);
        $this->assertEquals('\\PhpMyAdmin\\SqlParser\\Contexts\\Context' . $context, Context::$loadedContext);

        // Restoring context.
        Context::load('');
    }

    /**
     * @return string[][]
     */
    public function contextNamesProvider(): array
    {
        return [
            ['MySql50000'],
            ['MySql50100'],
            ['MySql50500'],
            ['MySql50600'],
            ['MySql50700'],
            ['MySql80000'],
            ['MariaDb100000'],
            ['MariaDb100100'],
            ['MariaDb100200'],
            ['MariaDb100300'],
        ];
    }

    public function testLoadError(): void
    {
        $this->expectExceptionMessage(
            'Specified context ("\PhpMyAdmin\SqlParser\Contexts\ContextFoo") does not exist.'
        );
        $this->expectException(Throwable::class);
        Context::load('Foo');
    }

    public function testMode(): void
    {
        Context::setMode('REAL_AS_FLOAT,ANSI_QUOTES,IGNORE_SPACE');
        $this->assertSame(SqlModes::REAL_AS_FLOAT | SqlModes::ANSI_QUOTES | SqlModes::IGNORE_SPACE, Context::getMode());
        $this->assertTrue(Context::hasMode(SqlModes::REAL_AS_FLOAT | SqlModes::IGNORE_SPACE));
        $this->assertTrue(Context::hasMode(SqlModes::ANSI_QUOTES));
        $this->assertFalse(Context::hasMode(SqlModes::REAL_AS_FLOAT | SqlModes::ALLOW_INVALID_DATES));
        $this->assertFalse(Context::hasMode(SqlModes::ALLOW_INVALID_DATES));

        Context::setMode('TRADITIONAL');
        $this->assertSame(SqlModes::TRADITIONAL, Context::getMode());

        Context::setMode();
        $this->assertSame(SqlModes::NONE, Context::$MODE);
    }

    public function testEscape(): void
    {
        Context::setMode('NO_ENCLOSING_QUOTES');
        $this->assertEquals('test', Context::escape('test'));

        Context::setMode('ANSI_QUOTES');
        $this->assertEquals('"test"', Context::escape('test'));

        Context::setMode();
        $this->assertEquals('`test`', Context::escape('test'));

        $this->assertEquals(
            [
                '`a`',
                '`b`',
            ],
            Context::escape(['a', 'b'])
        );
    }
}
