<?php

declare(strict_types=1);

namespace PhpMyAdmin\SqlParser\Tests\benchmarks;

use PhpMyAdmin\SqlParser\UtfString;

use function chr;
use function file_get_contents;

class UtfStringBench
{
    /** @var string */
    private $testContents;

    /**
     * @BeforeMethods("setUp")
     * @Iterations(20)
     * @Revs(4)
     * @OutputTimeUnit("milliseconds")
     * @Assert("mode(variant.time.avg) < 38 milliseconds +/- 10%")
     * @Assert("mode(variant.time.avg) > 20 milliseconds +/- 10%")
     */
    public function benchBuildUtfString(): void
    {
        $str1 = new UtfString($this->testContents);
        for ($i = 0; $i < $str1->length(); $i++) {
            $str1[$i];// Make offset offsetGet work
        }
    }

    /**
     * @BeforeMethods("setUp")
     * @Iterations(2)
     * @Revs(2)
     * @OutputTimeUnit("microseconds")
     * @Assert("mode(variant.time.avg) < 75 microseconds +/- 10%")
     * @Assert("mode(variant.time.avg) > 60 microseconds +/- 10%")
     */
    public function benchGetCharLength(): void
    {
        UtfString::getCharLength(chr(0x00)); // 00000000
        UtfString::getCharLength(chr(0x7F)); // 01111111

        UtfString::getCharLength(chr(0xC0)); // 11000000
        UtfString::getCharLength(chr(0xDF)); // 11011111

        UtfString::getCharLength(chr(0xE0)); // 11100000
        UtfString::getCharLength(chr(0xEF)); // 11101111

        UtfString::getCharLength(chr(0xF0)); // 11110000
        UtfString::getCharLength(chr(0xF7)); // 11110111

        UtfString::getCharLength(chr(0xF8)); // 11111000
        UtfString::getCharLength(chr(0xFB)); // 11111011

        UtfString::getCharLength(chr(0xFC)); // 11111100
        UtfString::getCharLength(chr(0xFD)); // 11111101
    }

    public function setUp(): void
    {
        $contentsPath = __DIR__ . '/../../LICENSE.txt';
        $this->testContents = (string) file_get_contents($contentsPath);
    }
}
