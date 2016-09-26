<?php

namespace SqlParser\Tests\Components;

use SqlParser\Parser;
use SqlParser\Components\CaseExpression;

use SqlParser\Tests\TestCase;

class CaseExpressionTest extends TestCase
{

    public function testParseBuild()
    {
        $caseExprQuery = 'case 1 when 1 then "Some" else "Other" end';
        $component = CaseExpression::parse(
            new Parser(),
            $this->getTokensList($caseExprQuery));
        $this->assertEquals(
            CaseExpression::build($component),
            'CASE 1 WHEN 1 THEN "Some" ELSE "Other" END'
        );
    }

    public function testParseBuild2()
    {
        $caseExprQuery = 'case when 1=1 then "India" else "Other" end';
        $component = CaseExpression::parse(
            new Parser(),
            $this->getTokensList($caseExprQuery));
        $this->assertEquals(
            CaseExpression::build($component),
            'CASE WHEN 1=1 THEN "India" ELSE "Other" END'
        );
    }
}
