<?php

declare(strict_types=1);

namespace PhpMyAdmin\SqlParser\Tests\Components;

use PhpMyAdmin\SqlParser\Parser;
use PhpMyAdmin\SqlParser\Parsers\JoinKeywords;
use PhpMyAdmin\SqlParser\Tests\TestCase;

class JoinKeywordTest extends TestCase
{
    public function testParseIncomplete(): void
    {
        $component = JoinKeywords::parse(new Parser(), $this->getTokensList('JOIN a'));
        $this->assertCount(1, $component);
        $this->assertNotNull($component[0]->expr);
        $this->assertEquals('a', $component[0]->expr->expr);
        $this->assertNull($component[0]->on);
        $this->assertNull($component[0]->using);
    }

    public function testParseIncompleteUsing(): void
    {
        $component = JoinKeywords::parse(new Parser(), $this->getTokensList('JOIN table2 USING (id)'));
        $this->assertCount(1, $component);
        $this->assertNotNull($component[0]->expr);
        $this->assertEquals('table2', $component[0]->expr->expr);
        $this->assertNull($component[0]->on);
        $this->assertNotNull($component[0]->using);
        $this->assertEquals(['id'], $component[0]->using->values);
    }

    public function testBuildAll(): void
    {
        $component = JoinKeywords::parse(
            new Parser(),
            $this->getTokensList(
                'LEFT JOIN (t2 CROSS JOIN t3 CROSS JOIN t4) ' .
                'ON (t2.a=t1.a AND t3.b=t1.b AND t4.c=t1.c)',
            ),
        );
        $this->assertEquals(
            'LEFT JOIN (t2 CROSS JOIN t3 CROSS JOIN t4) ' .
            'ON (t2.a=t1.a AND t3.b=t1.b AND t4.c=t1.c)',
            JoinKeywords::buildAll($component),
        );
    }
}
