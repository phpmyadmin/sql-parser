<?php

declare(strict_types=1);

namespace PhpMyAdmin\SqlParser\Tests\Components;

use PhpMyAdmin\SqlParser\Components\Expression;
use PhpMyAdmin\SqlParser\Parser;
use PhpMyAdmin\SqlParser\Parsers\PartitionDefinitions;
use PhpMyAdmin\SqlParser\Tests\TestCase;

class PartitionDefinitionTest extends TestCase
{
    public function testParse(): void
    {
        $component = PartitionDefinitions::parse(
            new Parser(),
            $this->getTokensList('PARTITION p0 VALUES LESS THAN(1990)'),
        );
        $this->assertFalse($component->isSubpartition);
        $this->assertEquals('p0', $component->name);
        $this->assertEquals('LESS THAN', $component->type);
        $this->assertInstanceOf(Expression::class, $component->expr);
        $this->assertEquals('(1990)', $component->expr->expr);
    }

    public function testParseNameWithUnderscore(): void
    {
        $component = PartitionDefinitions::parse(
            new Parser(),
            $this->getTokensList('PARTITION 2017_12 VALUES LESS THAN (\'2018-01-01 00:00:00\') ENGINE = MyISAM'),
        );
        $this->assertFalse($component->isSubpartition);
        $this->assertEquals('2017_12', $component->name);
        $this->assertEquals('LESS THAN', $component->type);
        $this->assertInstanceOf(Expression::class, $component->expr);
        $this->assertEquals('(\'2018-01-01 00:00:00\')', $component->expr->expr);
    }
}
