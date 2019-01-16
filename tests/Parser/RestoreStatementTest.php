<?php
declare(strict_types=1);

namespace PhpMyAdmin\SqlParser\Tests\Parser;

use PhpMyAdmin\SqlParser\Tests\TestCase;

class RestoreStatementTest extends TestCase
{
    /**
     * @dataProvider testRestoreProvider
     *
     * @param mixed $test
     */
    public function testRestore($test)
    {
        $this->runParserTest($test);
    }

    public function testRestoreProvider()
    {
        return [
            ['parser/parseRestore'],
        ];
    }
}
