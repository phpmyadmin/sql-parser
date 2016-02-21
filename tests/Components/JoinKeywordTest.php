<?php

namespace SqlParser\Tests\Components;

use SqlParser\Parser;
use SqlParser\Components\JoinKeyword;

use SqlParser\Tests\TestCase;

class JoinKeywordTest extends TestCase
{

    public function testParseIncomplete()
    {
        $component = JoinKeyword::parse(new Parser(), $this->getTokensList('JOIN a'));
        $this->assertEquals(1, count($component));
        $this->assertEquals('a', $component[0]->expr->expr);
        $this->assertEquals(null, $component[0]->on);
        $this->assertEquals(null, $component[0]->using);
    }

    public function testParseIncompleteUsing()
    {
        $component = JoinKeyword::parse(new Parser(), $this->getTokensList('JOIN table2 USING (id)'));
        $this->assertEquals(1, count($component));
        $this->assertEquals('table2', $component[0]->expr->expr);
        $this->assertEquals(null, $component[0]->on);
        $this->assertEquals(array('id'), $component[0]->using->values);
    }

    public function testBuild()
    {
        $component = JoinKeyword::parse(
            new Parser(),
            $this->getTokensList(
                'LEFT JOIN (t2 CROSS JOIN t3 CROSS JOIN t4) '.
                'ON (t2.a=t1.a AND t3.b=t1.b AND t4.c=t1.c)'
            )
        );
        $this->assertEquals(
            'LEFT JOIN (t2 CROSS JOIN t3 CROSS JOIN t4) '.
            'ON (t2.a=t1.a AND t3.b=t1.b AND t4.c=t1.c)',
            JoinKeyword::build($component)
        );
    }
}
