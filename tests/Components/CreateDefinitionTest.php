<?php

namespace PhpMyAdmin\SqlParser\Tests\Components;

use PhpMyAdmin\SqlParser\Components\CreateDefinition;
use PhpMyAdmin\SqlParser\Parser;
use PhpMyAdmin\SqlParser\Tests\TestCase;

class CreateDefinitionTest extends TestCase
{
    public function testParse()
    {
        $component = CreateDefinition::parse(
            new Parser(),
            $this->getTokensList('(str TEXT, FULLTEXT INDEX indx (str))')
        );
        $this->assertEquals('str', $component[0]->name);
        $this->assertEquals('FULLTEXT INDEX', $component[1]->key->type);
        $this->assertEquals('indx', $component[1]->key->name);
        $this->assertEquals('FULLTEXT INDEX `indx` (`str`)', (string) $component[1]);
    }

    public function testParseErr1()
    {
        $parser = new Parser();
        $component = CreateDefinition::parse(
            $parser,
            $this->getTokensList('(str TEXT, FULLTEXT INDEX indx (str)')
        );
        $this->assertCount(2, $component);

        $this->assertEquals(
            'A closing bracket was expected.',
            $parser->errors[0]->getMessage()
        );
    }

    public function testParseErr2()
    {
        $parser = new Parser();
        $component = CreateDefinition::parse(
            $parser,
            $this->getTokensList(')')
        );

        $this->assertEquals(
            'An opening bracket was expected.',
            $parser->errors[0]->getMessage()
        );
    }

    public function testBuild()
    {
        $parser = new Parser(
            'CREATE TABLE `payment` (' .
            '-- snippet' . "\n" .
            '`customer_id` smallint(5) unsigned NOT NULL,' .
            'CONSTRAINT `fk_payment_customer` FOREIGN KEY (`customer_id`) REFERENCES `customer` (`customer_id`) ON UPDATE CASCADE' .
            ') ENGINE=InnoDB"'
        );
        $this->assertEquals(
            'CONSTRAINT `fk_payment_customer` FOREIGN KEY (`customer_id`) REFERENCES `customer` (`customer_id`) ON UPDATE CASCADE',
            CreateDefinition::build($parser->statements[0]->fields[1])
        );
    }
}
