<?php

declare(strict_types=1);

namespace PhpMyAdmin\SqlParser\Tests\Misc;

use PhpMyAdmin\SqlParser\Tests\TestCase;
use PHPUnit\Framework\Attributes\DataProvider;

class BugsTest extends TestCase
{
    #[DataProvider('bugProvider')]
    public function testBug(string $test): void
    {
        $this->runParserTest($test);
    }

    /**
     * @return string[][]
     */
    public static function bugProvider(): array
    {
        return [
            ['bugs/gh9'],
            ['bugs/gh14'],
            ['bugs/gh16'],
            ['bugs/gh317'],
            ['bugs/gh508'],
            ['bugs/pma11800'],
            ['bugs/pma11836'],
            ['bugs/pma11843'],
            ['bugs/pma11879'],
        ];
    }
}
