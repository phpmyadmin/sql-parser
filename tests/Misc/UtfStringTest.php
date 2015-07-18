<?php

namespace SqlParser\Tests\Misc;

use SqlParser\UtfString;

use SqlParser\Tests\TestCase;

class UtfStringTest extends TestCase
{

    /**
     * Sample phrase in French.
     *
     * @var UtfString
     */
    const TEST_PHRASE = 'Les naïfs ægithales hâtifs pondant à Noël où il gèle sont sûrs d\'être déçus en voyant leurs drôles d\'œufs abîmés.';

    /**
     * The length of the sample phrase.
     *
     * @var int
     */
    const TEST_PHRASE_LEN = 113;

    public function testArrayAccess()
    {
        $str = new UtfString(static::TEST_PHRASE);

        // offsetExists
        $this->assertTrue(isset($str[static::TEST_PHRASE_LEN - 1]));
        $this->assertFalse(isset($str[-1]));
        $this->assertFalse(isset($str[static::TEST_PHRASE_LEN]));

        // offsetGet
        $this->assertEquals('.', $str[static::TEST_PHRASE_LEN - 1]);
        $this->assertEquals(null, $str[-1]);
        $this->assertEquals(null, $str[static::TEST_PHRASE_LEN]);
    }

    /**
     * @expectedException \Exception
     * @expectedExceptionMessage Not implemented.
     */
    public function testSet()
    {
        $str = new UtfString('');
        $str[0] = 'a';
    }

    /**
     * @expectedException \Exception
     * @expectedExceptionMessage Not implemented.
     */
    public function testUnset()
    {
        $str = new UtfString('');
        unset($str[0]);
    }

    public function testGetCharLength()
    {
        $this->assertEquals(1, UtfString::getCharLength(chr(0x00))); // 00000000
        $this->assertEquals(1, UtfString::getCharLength(chr(0x7F))); // 01111111

        $this->assertEquals(2, UtfString::getCharLength(chr(0xC0))); // 11000000
        $this->assertEquals(2, UtfString::getCharLength(chr(0xDF))); // 11011111

        $this->assertEquals(3, UtfString::getCharLength(chr(0xE0))); // 11100000
        $this->assertEquals(3, UtfString::getCharLength(chr(0xEF))); // 11101111

        $this->assertEquals(4, UtfString::getCharLength(chr(0xF0))); // 11110000
        $this->assertEquals(4, UtfString::getCharLength(chr(0xF7))); // 11110111

        $this->assertEquals(5, UtfString::getCharLength(chr(0xF8))); // 11111000
        $this->assertEquals(5, UtfString::getCharLength(chr(0xFB))); // 11111011

        $this->assertEquals(6, UtfString::getCharLength(chr(0xFC))); // 11111100
        $this->assertEquals(6, UtfString::getCharLength(chr(0xFD))); // 11111101
    }

    public function testToString()
    {
        $str = new UtfString(static::TEST_PHRASE);
        $this->assertEquals(static::TEST_PHRASE, (string) $str);
    }
}
