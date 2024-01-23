<?php

declare(strict_types=1);

namespace PhpMyAdmin\SqlParser\Tests\Components;

use PhpMyAdmin\SqlParser\Parser;
use PhpMyAdmin\SqlParser\Parsers\IntoKeywords;
use PhpMyAdmin\SqlParser\Tests\TestCase;

class IntoKeywordTest extends TestCase
{
    public function testParse(): void
    {
        $component = IntoKeywords::parse(new Parser(), $this->getTokensList('OUTFILE "/tmp/outfile.txt"'));
        $this->assertEquals('OUTFILE', $component->type);
        $this->assertEquals('/tmp/outfile.txt', $component->dest);
    }

    public function testBuild(): void
    {
        $component = IntoKeywords::parse(new Parser(), $this->getTokensList('tbl(`col1`, `col2`)'));
        $this->assertEquals('tbl(`col1`, `col2`)', $component->build());
    }

    public function testBuildValues(): void
    {
        $component = IntoKeywords::parse(new Parser(), $this->getTokensList('@a1, @a2, @a3'));
        $this->assertEquals('@a1, @a2, @a3', $component->build());
    }

    public function testBuildOutfile(): void
    {
        $component = IntoKeywords::parse(new Parser(), $this->getTokensList('OUTFILE "/tmp/outfile.txt"'));
        $this->assertEquals('OUTFILE "/tmp/outfile.txt"', $component->build());
    }

    public function testParseErr1(): void
    {
        $component = IntoKeywords::parse(new Parser(), $this->getTokensList('OUTFILE;'));
        $this->assertEquals('OUTFILE', $component->type);
    }
}
