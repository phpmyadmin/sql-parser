<?php

namespace PhpMyAdmin\SqlParser\Tests\Parser;

use PhpMyAdmin\SqlParser\Tests\TestCase;

class PurgeStatementTest extends TestCase
{
    /**
     * @dataProvider testPurgeProvider
     *
     * @param mixed $test
     */
    public function testPurge($test)
    {
        $this->runParserTest($test);
    }

    public function testPurgeProvider()
    {
        return [
            ['parser/parsePurge'],
            ['parser/parsePurge2'],
            ['parser/parsePurge3'],
            ['parser/parsePurge4'],
            ['parser/parsePurgeErr'],
            ['parser/parsePurgeErr2'],
            ['parser/parsePurgeErr3'],
        ];
    }
}
