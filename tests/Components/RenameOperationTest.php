<?php

declare(strict_types=1);

namespace PhpMyAdmin\SqlParser\Tests\Components;

use PhpMyAdmin\SqlParser\Components\Parsers\RenameOperations;
use PhpMyAdmin\SqlParser\Parser;
use PhpMyAdmin\SqlParser\Tests\TestCase;

class RenameOperationTest extends TestCase
{
    public function testBuildAll(): void
    {
        $component = RenameOperations::parse(new Parser(), $this->getTokensList('a TO b, c TO d'));
        $this->assertEquals(RenameOperations::buildAll($component), 'a TO b, c TO d');
    }
}
