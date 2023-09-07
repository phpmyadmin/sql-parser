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
     * @Assert("mode(variant.time.avg) < 100 milliseconds +/- 10%")
     * @Assert("mode(variant.time.avg) > 30 milliseconds +/- 10%")
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
}
