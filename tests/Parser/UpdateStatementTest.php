<?php
declare(strict_types=1);

namespace PhpMyAdmin\SqlParser\Tests\Parser;

use PhpMyAdmin\SqlParser\Tests\TestCase;

class UpdateStatementTest extends TestCase
{
    /**
     * @dataProvider testUpdateProvider
     *
     * @param mixed $test
     */
    public function testUpdate($test)
    {
        $this->runParserTest($test);
    }

    public function testUpdateProvider()
    {
        return [
            ['parser/parseUpdate'],
            ['parser/parseUpdate2'],
            ['parser/parseUpdate3'],
            ['parser/parseUpdateErr'],
        ];
    }
}
