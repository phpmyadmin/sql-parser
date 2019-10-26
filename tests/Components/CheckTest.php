<?php
declare(strict_types=1);

namespace PhpMyAdmin\SqlParser\Tests\Components;

use PhpMyAdmin\SqlParser\Components\Check;
use PhpMyAdmin\SqlParser\Parser;
use PhpMyAdmin\SqlParser\Tests\TestCase;

class CheckTest extends TestCase
{
    public function testParse()
    {
        $component = Check::parse(
            new Parser(),
            $this->getTokensList('(json_valid(customer_data))')
        );
        $this->assertEquals('(json_valid(customer_data))', $component->rule);
    }

    public function testBuild()
    {
        $parser = new Parser(
            'CREATE TABLE `payment` (' .
            '-- snippet' . "\n" .
            '`customer_id` smallint(5) unsigned NOT NULL,' .
            '`customer_data` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL CHECK (json_valid(customer_data)),' .
            ') ENGINE=InnoDB"'
        );
        $this->assertContains(
            Check::build($parser->statements[0]->fields[1]->check),
            'CHECK (json_valid(customer_data))'
        );
    }
}
