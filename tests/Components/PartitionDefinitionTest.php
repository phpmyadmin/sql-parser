<?php

namespace PhpMyAdmin\SqlParser\Tests\Components;

use PhpMyAdmin\SqlParser\Components\PartitionDefinition;
use PhpMyAdmin\SqlParser\Parser;
use PhpMyAdmin\SqlParser\Tests\TestCase;

class PartitionDefinitionTest extends TestCase
{
    public function testParse()
    {
        $component = PartitionDefinition::parse(
            new Parser(),
            $this->getTokensList('PARTITION p0 VALUES LESS THAN(1990)')
        );
        $this->assertFalse($component->isSubpartition);
        $this->assertEquals('p0', $component->name);
        $this->assertEquals('LESS THAN', $component->type);
        $this->assertEquals('(1990)', $component->expr->expr);
    }
}
