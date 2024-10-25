<?php

declare(strict_types=1);

namespace PhpMyAdmin\SqlParser\Tests\Components;

use PhpMyAdmin\SqlParser\Parser;
use PhpMyAdmin\SqlParser\Parsers\Conditions;
use PhpMyAdmin\SqlParser\Tests\TestCase;

class ConditionTest extends TestCase
{
    public function testParse(): void
    {
        $component = Conditions::parse(new Parser(), $this->getTokensList('/* id = */ id = 10'));
        $this->assertEquals('id = 10', $component[0]->expr);
    }

    public function testParseBetween(): void
    {
        $component = Conditions::parse(
            new Parser(),
            $this->getTokensList('(id BETWEEN 10 AND 20) OR (id BETWEEN 30 AND 40)'),
        );
        $this->assertEquals('(id BETWEEN 10 AND 20)', $component[0]->expr);
        $this->assertEquals('OR', $component[1]->expr);
        $this->assertEquals('(id BETWEEN 30 AND 40)', $component[2]->expr);
    }

    public function testParseAnd(): void
    {
        $component = Conditions::parse(new Parser(), $this->getTokensList("`col` LIKE 'AND'"));
        $this->assertEquals("`col` LIKE 'AND'", Conditions::buildAll($component));
    }
}
