<?php
declare(strict_types=1);

namespace PhpMyAdmin\SqlParser\Tests\Misc;

use PhpMyAdmin\SqlParser\Tests\TestCase;

class ParameterTest extends TestCase
{
    /**
     * @dataProvider parameterProvider
     *
     * @param mixed $test
     */
    public function testParameter($test)
    {
        $this->runParserTest($test);
    }

    public function parameterProvider()
    {
        return [
            ['misc/parseParameter'],
        ];
    }
}
