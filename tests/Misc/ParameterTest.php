<?php

namespace PhpMyAdmin\SqlParser\Tests\Misc;

use PhpMyAdmin\SqlParser\Tests\TestCase;

class ParameterTest extends TestCase
{
    /**
     * @dataProvider testParameterProvider
     *
     * @param mixed $test
     */
    public function testParameter($test)
    {
        $this->runParserTest($test);
    }

    public function testParameterProvider()
    {
        return array(
            array('misc/parseParameter'),
        );
    }
}
