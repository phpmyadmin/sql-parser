<?php
declare(strict_types=1);

namespace PhpMyAdmin\SqlParser\Tests\Parser;

use PhpMyAdmin\SqlParser\Tests\TestCase;

class CallStatementTest extends TestCase
{
    /**
     * @dataProvider callProvider
     *
     * @param mixed $test
     */
    public function testCall($test)
    {
        $this->runParserTest($test);
    }

    public function callProvider()
    {
        return [
            ['parser/parseCall'],
            ['parser/parseCall2'],
            ['parser/parseCall3'],
        ];
    }
}
