<?php

declare(strict_types=1);

namespace PhpMyAdmin\SqlParser\Tests\Components;

use PhpMyAdmin\SqlParser\Components\Limit;
use PhpMyAdmin\SqlParser\Tests\TestCase;
use PHPUnit\Framework\Attributes\DataProvider;

class LimitTest extends TestCase
{
    public function testBuildWithoutOffset(): void
    {
        $component = new Limit(1);
        $this->assertEquals('0, 1', $component->build());
    }

    public function testBuildWithOffset(): void
    {
        $component = new Limit(1, 2);
        $this->assertEquals('2, 1', $component->build());
    }

    #[DataProvider('parseProvider')]
    public function testParse(string $test): void
    {
        $this->runParserTest($test);
    }

    /** @return string[][] */
    public static function parseProvider(): array
    {
        return [
            ['parser/parseLimitErr1'],
            ['parser/parseLimitErr2'],
        ];
    }
}
