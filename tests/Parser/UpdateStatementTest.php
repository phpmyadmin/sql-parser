<?php

declare(strict_types=1);

namespace PhpMyAdmin\SqlParser\Tests\Parser;

use PhpMyAdmin\SqlParser\Tests\TestCase;

class UpdateStatementTest extends TestCase
{
    /**
     * @dataProvider updateProvider
     */
    public function testUpdate(string $test): void
    {
        $this->runParserTest($test);
    }

    /**
     * @return string[][]
     */
    public function updateProvider(): array
    {
        return [
            ['parser/parseUpdate'],
            ['parser/parseUpdate2'],
            ['parser/parseUpdate3'],
            ['parser/parseUpdateErr'],
        ];
    }
}
