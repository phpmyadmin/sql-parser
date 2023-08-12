<?php

declare(strict_types=1);

namespace PhpMyAdmin\SqlParser\Tests\Misc;

use PhpMyAdmin\SqlParser\Tests\TestCase;
use PHPUnit\Framework\Attributes\DataProvider;

class ParameterTest extends TestCase
{
    #[DataProvider('parameterProvider')]
    public function testParameter(string $test): void
    {
        $this->runParserTest($test);
    }

    /**
     * @return string[][]
     */
    public static function parameterProvider(): array
    {
        return [
            ['misc/parseParameter'],
        ];
    }
}
