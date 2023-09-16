<?php

declare(strict_types=1);

namespace PhpMyAdmin\SqlParser\Tests\benchmarks;

use PhpMyAdmin\SqlParser\UtfString;

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
     * @Assert("mode(variant.time.avg) < 40 milliseconds +/- 10%")
     */
    public function benchBuildUtfString(): void
    {
        $str1 = new UtfString($this->testContents);
        for ($i = 0; $i < $str1->length(); $i++) {
            $str1[$i];// Make offset offsetGet work
        }
    }

    public function setUp(): void
    {
        $contentsPath = __DIR__ . '/../../LICENSE.txt';
        $this->testContents = (string) file_get_contents($contentsPath);
    }

    /**
     * @Iterations(20)
     * @Revs(4)
     * @OutputTimeUnit("microseconds")
     * @Assert("mode(variant.time.avg) < 120 microseconds +/- 10%")
     */
    public function benchUtfStringRandomAccessWithUnicode(): void
    {
        $text = 'abcdefghijklmnopqrstuvwxyz
        áéíóúýěřťǔǐǒǎšďȟǰǩľžčǚň
        🦋😄😃😀😊😉😍😘😚😗😂👿😮😨😱😠😡😤😖😆😋👯
        P\xf8\xed\xb9ern\xec \xbelu\xbbou\xe8k\xfd k\xf3d \xfap\xecl \xef\xe1belsk\xe9 k\xf3dy
        xℤⅿↈⅬ⅀ↆℜℝ⅗ℾ℧ⅰℓⅯⅵⅣ⅒21⅞';

        $str1 = new UtfString($text);
        $str1->offsetGet(10);
        $str1->offsetGet(100);
        $str1->offsetGet(20);
        $str1->offsetGet(0);
    }
}
