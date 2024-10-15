<?php

declare(strict_types=1);

namespace PhpMyAdmin\SqlParser\Tests\Misc;

use PhpMyAdmin\SqlParser\Tests\TestCase;
use PhpMyAdmin\SqlParser\UtfString;
use PHPUnit\Framework\Attributes\DataProvider;
use Throwable;

class UtfStringTest extends TestCase
{
    /**
     * Sample phrase in French.
     */
    public const TEST_PHRASE = 'Les naïfs ægithales hâtifs pondant à Noël où il '
        . 'gèle sont sûrs d\'être déçus en voyant leurs drôles d\'œufs abîmés.';

    /**
     * The length of the sample phrase.
     */
    public const TEST_PHRASE_LEN = 113;

    public function testArrayAccess(): void
    {
        $str = new UtfString(self::TEST_PHRASE);

        // offsetExists
        $this->assertArrayHasKey(self::TEST_PHRASE_LEN - 1, $str);
        $this->assertArrayNotHasKey(-1, $str);
        $this->assertArrayNotHasKey(self::TEST_PHRASE_LEN, $str);

        // offsetGet
        $this->assertEquals('.', $str[self::TEST_PHRASE_LEN - 1]);
        $this->assertEquals('', $str[-1]);
        $this->assertEquals('', $str[self::TEST_PHRASE_LEN]);
    }

    public function testSet(): void
    {
        $this->expectExceptionMessage('Not implemented.');
        $this->expectException(Throwable::class);
        $str = new UtfString('');
        $str[0] = 'a';
    }

    public function testUnset(): void
    {
        $this->expectExceptionMessage('Not implemented.');
        $this->expectException(Throwable::class);
        $str = new UtfString('');
        unset($str[0]);
    }

    public function testToString(): void
    {
        $str = new UtfString(self::TEST_PHRASE);
        $this->assertEquals(self::TEST_PHRASE, (string) $str);
    }

    /**
     * Test access to string.
     */
    #[DataProvider('utf8StringsProvider')]
    public function testAccess(string $text, string|null $pos10, string|null $pos20): void
    {
        $str = new UtfString($text);
        $this->assertEquals($pos10, $str->offsetGet(10));
        $this->assertEquals($pos20, $str->offsetGet(20));
        $this->assertEquals($pos10, $str->offsetGet(10));
    }

    /**
     * @return array<string, array<int, string|null>>
     * @psalm-return array<string, array{string, (string|null), (string|null)}>
     */
    public static function utf8StringsProvider(): array
    {
        return [
            'ascii' => [
                'abcdefghijklmnopqrstuvwxyz',
                'k',
                'u',
            ],
            'unicode' => [
                'áéíóúýěřťǔǐǒǎšďȟǰǩľžčǚň',
                'ǐ',
                'č',
            ],
            'emoji' => [
                '🦋😄😃😀😊😉😍😘😚😗😂👿😮😨😱😠😡😤😖😆😋👯',
                '😂',
                '😋',
            ],
            'iso' => [
                "P\xf8\xed\xb9ern\xec \xbelu\xbbou\xe8k\xfd k\xf3d \xfap\xecl \xef\xe1belsk\xe9 k\xf3dy",
                null,
                null,
            ],
            'random' => [
                'xℤⅿↈⅬ⅀ↆℜℝ⅗ℾ℧ⅰℓⅯⅵⅣ⅒21⅞',
                'ℾ',
                '⅞',
            ],
        ];
    }
}
