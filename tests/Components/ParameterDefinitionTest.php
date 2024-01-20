<?php

declare(strict_types=1);

namespace PhpMyAdmin\SqlParser\Tests\Components;

use PhpMyAdmin\SqlParser\Parser;
use PhpMyAdmin\SqlParser\Parsers\ParameterDefinitions;
use PhpMyAdmin\SqlParser\Tests\TestCase;

class ParameterDefinitionTest extends TestCase
{
    public function testParse(): void
    {
        $component = ParameterDefinitions::parse(
            new Parser(),
            $this->getTokensList('(a INT, b INT'),
        );
        $this->assertEquals('a', $component[0]->name);
        $this->assertEquals('b', $component[1]->name);
    }

    public function testParseComplex(): void
    {
        $parser = new Parser();
        $component = ParameterDefinitions::parse(
            $parser,
            $this->getTokensList('CREATE DEFINER=`root`@`%` PROCEDURE `foo`( $bar int )'),
        );
        $this->assertEquals('$bar', $component[0]->name);
    }
}
