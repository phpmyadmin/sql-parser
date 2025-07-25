<?php

declare(strict_types=1);

namespace PhpMyAdmin\SqlParser\Tests\Lexer;

use PhpMyAdmin\SqlParser\Context;
use PhpMyAdmin\SqlParser\Contexts;
use PhpMyAdmin\SqlParser\Tests\TestCase;
use PHPUnit\Framework\Attributes\DataProvider;

use function class_exists;

class ContextTest extends TestCase
{
    public static function tearDownAfterClass(): void
    {
        Context::setMode();
    }

    public function testLoad(): void
    {
        // Default context is 5.7.0.
        $this->assertEquals('PhpMyAdmin\\SqlParser\\Contexts\\ContextMySql50700', Context::$loadedContext);
        $this->assertArrayHasKey('STORED', Context::$keywords);
        $this->assertArrayNotHasKey('AUTHORS', Context::$keywords);

        // Restoring context.
        Context::load();
        $this->assertArrayHasKey('STORED', Context::$keywords);
        $this->assertArrayNotHasKey('AUTHORS', Context::$keywords);
    }

    /**
     * Test for loading closest SQL context
     */
    #[DataProvider('contextLoadingProvider')]
    public function testLoadClosest(string $context, string|null $expected): void
    {
        $this->assertEquals($expected, Context::loadClosest($context));
        if ($expected !== null) {
            $this->assertEquals('PhpMyAdmin\\SqlParser\\Contexts\\Context' . $expected, Context::$loadedContext);
            $this->assertTrue(class_exists(Context::$loadedContext));
        }

        // Restoring context.
        Context::load();
    }

    /**
     * @return array<string, array<int, string|null>>
     * @psalm-return array<string, array{string, (string|null)}>
     */
    public static function contextLoadingProvider(): array
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
                'MySql9897969594',
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

    #[DataProvider('contextNamesProvider')]
    public function testLoadAll(string $context): void
    {
        Context::load($context);
        $this->assertEquals('PhpMyAdmin\\SqlParser\\Contexts\\Context' . $context, Context::$loadedContext);

        // Restoring context.
        Context::load();
    }

    /** @return string[][] */
    public static function contextNamesProvider(): array
    {
        return [
            ['MySql50000'],
            ['MySql50100'],
            ['MySql50500'],
            ['MySql50600'],
            ['MySql50700'],
            ['MySql80000'],
            ['MySql80100'],
            ['MySql80200'],
            ['MySql80300'],
            ['MySql80400'],
            ['MySql90000'],
            ['MySql90100'],
            ['MySql90200'],
            ['MySql90300'],
            ['MariaDb100000'],
            ['MariaDb100100'],
            ['MariaDb100200'],
            ['MariaDb100300'],
            ['MariaDb100400'],
            ['MariaDb100500'],
            ['MariaDb100600'],
            ['MariaDb100700'],
            ['MariaDb100800'],
            ['MariaDb100900'],
            ['MariaDb101000'],
            ['MariaDb101100'],
            ['MariaDb110000'],
            ['MariaDb110100'],
            ['MariaDb110200'],
            ['MariaDb110300'],
            ['MariaDb110400'],
            ['MariaDb110500'],
            ['MariaDb110600'],
            ['MariaDb110700'],
            ['MariaDb110800'],
            ['MariaDb120000'],
            ['MariaDb120100'],
        ];
    }

    #[DataProvider('contextClassesProvider')]
    public function testLoadAllByClass(string $context): void
    {
        Context::load($context);
        $this->assertEquals($context, Context::$loadedContext);

        // Restoring context.
        Context::load('');
    }

    /** @return string[][] */
    public static function contextClassesProvider(): array
    {
        return [
            [Contexts\ContextMySql50000::class],
            [Contexts\ContextMySql50100::class],
            [Contexts\ContextMySql50500::class],
            [Contexts\ContextMySql50600::class],
            [Contexts\ContextMySql50700::class],
            [Contexts\ContextMySql80000::class],
            [Contexts\ContextMySql80100::class],
            [Contexts\ContextMySql80200::class],
            [Contexts\ContextMySql80300::class],
            [Contexts\ContextMySql80400::class],
            [Contexts\ContextMySql90000::class],
            [Contexts\ContextMySql90100::class],
            [Contexts\ContextMySql90200::class],
            [Contexts\ContextMySql90300::class],
            [Contexts\ContextMariaDb100000::class],
            [Contexts\ContextMariaDb100100::class],
            [Contexts\ContextMariaDb100200::class],
            [Contexts\ContextMariaDb100300::class],
            [Contexts\ContextMariaDb100400::class],
            [Contexts\ContextMariaDb100500::class],
            [Contexts\ContextMariaDb100600::class],
            [Contexts\ContextMariaDb100700::class],
            [Contexts\ContextMariaDb100800::class],
            [Contexts\ContextMariaDb100900::class],
            [Contexts\ContextMariaDb101000::class],
            [Contexts\ContextMariaDb101100::class],
            [Contexts\ContextMariaDb110000::class],
            [Contexts\ContextMariaDb110100::class],
            [Contexts\ContextMariaDb110200::class],
            [Contexts\ContextMariaDb110300::class],
            [Contexts\ContextMariaDb110400::class],
            [Contexts\ContextMariaDb110500::class],
            [Contexts\ContextMariaDb110600::class],
            [Contexts\ContextMariaDb110700::class],
            [Contexts\ContextMariaDb110800::class],
            [Contexts\ContextMariaDb120000::class],
            [Contexts\ContextMariaDb120100::class],
        ];
    }

    public function testLoadError(): void
    {
        $this->assertFalse(Context::load('Foo'));
    }

    #[DataProvider('providerForTestMode')]
    public function testMode(int|string $mode, int $expected): void
    {
        Context::setMode($mode);
        $this->assertSame($expected, Context::getMode());
    }

    /**
     * @return array<int, array<int, int|string>>
     * @psalm-return list<array{int|string, int}>
     */
    public static function providerForTestMode(): array
    {
        return [
            [0, Context::SQL_MODE_NONE],
            [1, 1],
            ['', Context::SQL_MODE_NONE],
            ['invalid', Context::SQL_MODE_NONE],
            ['ALLOW_INVALID_DATES', Context::SQL_MODE_ALLOW_INVALID_DATES],
            ['ANSI_QUOTES', Context::SQL_MODE_ANSI_QUOTES],
            ['COMPAT_MYSQL', Context::SQL_MODE_COMPAT_MYSQL],
            ['ERROR_FOR_DIVISION_BY_ZERO', Context::SQL_MODE_ERROR_FOR_DIVISION_BY_ZERO],
            ['HIGH_NOT_PRECEDENCE', Context::SQL_MODE_HIGH_NOT_PRECEDENCE],
            ['IGNORE_SPACE', Context::SQL_MODE_IGNORE_SPACE],
            ['NO_AUTO_CREATE_USER', Context::SQL_MODE_NO_AUTO_CREATE_USER],
            ['NO_AUTO_VALUE_ON_ZERO', Context::SQL_MODE_NO_AUTO_VALUE_ON_ZERO],
            ['NO_BACKSLASH_ESCAPES', Context::SQL_MODE_NO_BACKSLASH_ESCAPES],
            ['NO_DIR_IN_CREATE', Context::SQL_MODE_NO_DIR_IN_CREATE],
            ['NO_ENGINE_SUBSTITUTION', Context::SQL_MODE_NO_ENGINE_SUBSTITUTION],
            ['NO_FIELD_OPTIONS', Context::SQL_MODE_NO_FIELD_OPTIONS],
            ['NO_KEY_OPTIONS', Context::SQL_MODE_NO_KEY_OPTIONS],
            ['NO_TABLE_OPTIONS', Context::SQL_MODE_NO_TABLE_OPTIONS],
            ['NO_UNSIGNED_SUBTRACTION', Context::SQL_MODE_NO_UNSIGNED_SUBTRACTION],
            ['NO_ZERO_DATE', Context::SQL_MODE_NO_ZERO_DATE],
            ['NO_ZERO_IN_DATE', Context::SQL_MODE_NO_ZERO_IN_DATE],
            ['ONLY_FULL_GROUP_BY', Context::SQL_MODE_ONLY_FULL_GROUP_BY],
            ['PIPES_AS_CONCAT', Context::SQL_MODE_PIPES_AS_CONCAT],
            ['REAL_AS_FLOAT', Context::SQL_MODE_REAL_AS_FLOAT],
            ['STRICT_ALL_TABLES', Context::SQL_MODE_STRICT_ALL_TABLES],
            ['STRICT_TRANS_TABLES', Context::SQL_MODE_STRICT_TRANS_TABLES],
            ['NO_ENCLOSING_QUOTES', Context::SQL_MODE_NO_ENCLOSING_QUOTES],
            ['ANSI', Context::SQL_MODE_ANSI],
            ['DB2', Context::SQL_MODE_DB2],
            ['MAXDB', Context::SQL_MODE_MAXDB],
            ['MSSQL', Context::SQL_MODE_MSSQL],
            ['ORACLE', Context::SQL_MODE_ORACLE],
            ['POSTGRESQL', Context::SQL_MODE_POSTGRESQL],
            ['TRADITIONAL', Context::SQL_MODE_TRADITIONAL],
        ];
    }

    public function testModeWithCombinedModes(): void
    {
        Context::setMode(
            Context::SQL_MODE_REAL_AS_FLOAT | Context::SQL_MODE_ANSI_QUOTES | Context::SQL_MODE_IGNORE_SPACE,
        );
        $this->assertSame(
            Context::SQL_MODE_REAL_AS_FLOAT | Context::SQL_MODE_ANSI_QUOTES | Context::SQL_MODE_IGNORE_SPACE,
            Context::getMode(),
        );
        $this->assertTrue(Context::hasMode(Context::SQL_MODE_REAL_AS_FLOAT | Context::SQL_MODE_IGNORE_SPACE));
        $this->assertTrue(Context::hasMode(Context::SQL_MODE_ANSI_QUOTES));
        $this->assertFalse(Context::hasMode(Context::SQL_MODE_REAL_AS_FLOAT | Context::SQL_MODE_ALLOW_INVALID_DATES));
        $this->assertFalse(Context::hasMode(Context::SQL_MODE_ALLOW_INVALID_DATES));

        Context::setMode(Context::SQL_MODE_TRADITIONAL);
        $this->assertSame(Context::SQL_MODE_TRADITIONAL, Context::getMode());

        Context::setMode();
        $this->assertSame(Context::SQL_MODE_NONE, Context::getMode());
    }

    public function testModeWithString(): void
    {
        Context::setMode('REAL_AS_FLOAT,ANSI_QUOTES,IGNORE_SPACE');
        $this->assertSame(
            Context::SQL_MODE_REAL_AS_FLOAT | Context::SQL_MODE_ANSI_QUOTES | Context::SQL_MODE_IGNORE_SPACE,
            Context::getMode(),
        );
        $this->assertTrue(Context::hasMode(Context::SQL_MODE_REAL_AS_FLOAT | Context::SQL_MODE_IGNORE_SPACE));
        $this->assertTrue(Context::hasMode(Context::SQL_MODE_ANSI_QUOTES));
        $this->assertFalse(Context::hasMode(Context::SQL_MODE_REAL_AS_FLOAT | Context::SQL_MODE_ALLOW_INVALID_DATES));
        $this->assertFalse(Context::hasMode(Context::SQL_MODE_ALLOW_INVALID_DATES));

        Context::setMode('TRADITIONAL');
        $this->assertSame(Context::SQL_MODE_TRADITIONAL, Context::getMode());

        Context::setMode('');
        $this->assertSame(Context::SQL_MODE_NONE, Context::getMode());
    }

    public function testEscape(): void
    {
        Context::setMode(Context::SQL_MODE_NO_ENCLOSING_QUOTES);
        $this->assertEquals('test', Context::escape('test'));
        $this->assertEquals('`123`', Context::escape('123'));
        $this->assertEquals('`$test`', Context::escape('$test'));
        $this->assertEquals('`te st`', Context::escape('te st'));
        $this->assertEquals('`te.st`', Context::escape('te.st'));

        Context::setMode(Context::SQL_MODE_ANSI_QUOTES);
        $this->assertEquals('"test"', Context::escape('test'));

        Context::setMode();
        $this->assertEquals('`test`', Context::escape('test'));

        $this->assertEquals(['`a`', '`b`'], Context::escapeAll(['a', 'b']));
    }

    public function testEscapeAll(): void
    {
        Context::setMode();
        $this->assertEquals(['`a`', '`b`'], Context::escapeAll(['a', 'b']));
    }
}
