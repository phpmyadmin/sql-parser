<?php

declare(strict_types=1);

namespace PhpMyAdmin\SqlParser\Tests\Components;

use PhpMyAdmin\SqlParser\Components\CaseExpression;
use PhpMyAdmin\SqlParser\Parser;
use PhpMyAdmin\SqlParser\Parsers\CaseExpressions;
use PhpMyAdmin\SqlParser\Tests\TestCase;

class CaseExpressionTest extends TestCase
{
    public function testParseBuild(): void
    {
        $caseExprQuery = 'case 1 when 1 then "Some" else "Other" end';
        $component = CaseExpressions::parse(
            new Parser(),
            $this->getTokensList($caseExprQuery),
        );
        $this->assertEquals(
            'CASE 1 WHEN 1 THEN "Some" ELSE "Other" END',
            $component->build(),
        );
    }

    public function testParseBuild2(): void
    {
        $caseExprQuery = 'case when 1=1 then "India" else "Other" end';
        $component = CaseExpressions::parse(
            new Parser(),
            $this->getTokensList($caseExprQuery),
        );
        $this->assertEquals(
            'CASE WHEN 1=1 THEN "India" ELSE "Other" END',
            $component->build(),
        );
    }

    public function testParseBuild3(): void
    {
        $caseExprQuery = 'case 1 when 1 then "Some" '
            . 'when 2 then "SomeOther" else "Other" end';
        $component = CaseExpressions::parse(
            new Parser(),
            $this->getTokensList($caseExprQuery),
        );
        $this->assertEquals(
            'CASE 1 WHEN 1 THEN "Some" WHEN 2 THEN "SomeOther" ELSE "Other" END',
            $component->build(),
        );
    }

    public function testParseBuild4(): void
    {
        $caseExprQuery = 'case 1 when 1 then "Some" '
            . 'when 2 then "SomeOther" end';
        $component = CaseExpressions::parse(
            new Parser(),
            $this->getTokensList($caseExprQuery),
        );
        $this->assertEquals(
            'CASE 1 WHEN 1 THEN "Some" WHEN 2 THEN "SomeOther" END',
            $component->build(),
        );
    }

    public function testParseBuild5(): void
    {
        $caseExprQuery = 'case when 1=1 then "Some" '
            . 'when 1=2 then "SomeOther" else "Other" end';
        $component = CaseExpressions::parse(
            new Parser(),
            $this->getTokensList($caseExprQuery),
        );
        $this->assertEquals(
            'CASE WHEN 1=1 THEN "Some" WHEN 1=2 THEN "SomeOther" ELSE "Other" END',
            $component->build(),
        );
    }

    public function testParseBuild6(): void
    {
        $caseExprQuery = 'case when 1=1 then "Some" '
            . 'when 1=2 then "SomeOther" end';
        $component = CaseExpressions::parse(
            new Parser(),
            $this->getTokensList($caseExprQuery),
        );
        $this->assertEquals(
            'CASE WHEN 1=1 THEN "Some" WHEN 1=2 THEN "SomeOther" END',
            $component->build(),
        );
    }

    public function testParseBuild7(): void
    {
        $caseExprQuery = 'case when 1=1 then "Some" '
            . 'when 1=2 then "SomeOther" end AS foo';
        $component = CaseExpressions::parse(
            new Parser(),
            $this->getTokensList($caseExprQuery),
        );
        $this->assertEquals(
            'CASE WHEN 1=1 THEN "Some" WHEN 1=2 THEN "SomeOther" END AS `foo`',
            $component->build(),
        );
    }

    public function testParseBuild8(): void
    {
        $caseExprQuery = 'case when 1=1 then "Some" '
            . 'when 1=2 then "SomeOther" end foo';
        $component = CaseExpressions::parse(
            new Parser(),
            $this->getTokensList($caseExprQuery),
        );
        $this->assertEquals(
            'CASE WHEN 1=1 THEN "Some" WHEN 1=2 THEN "SomeOther" END AS `foo`',
            $component->build(),
        );
    }

    public function testBuildWithIncompleteCaseExpression(): void
    {
        $incompleteCaseExpressionComponent = new CaseExpression();
        $this->assertEquals('CASE END', $incompleteCaseExpressionComponent->build());
    }
}
