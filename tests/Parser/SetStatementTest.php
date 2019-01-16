<?php
declare(strict_types=1);

namespace PhpMyAdmin\SqlParser\Tests\Parser;

use PhpMyAdmin\SqlParser\Tests\TestCase;

class SetStatementTest extends TestCase
{
    /**
     * @dataProvider setProvider
     *
     * @param mixed $test
     */
    public function testSet($test)
    {
        $this->runParserTest($test);
    }

    public function setProvider()
    {
        return [
            ['parser/parseSetCharset'],
            ['parser/parseSetCharsetError'],
            ['parser/parseSetCharacterSet'],
            ['parser/parseSetCharacterSetError'],
            ['parser/parseSetNames'],
            ['parser/parseSetNamesError'],
            ['parser/parseSetError1'],
        ];
    }
}
