<?php

namespace SqlParser\Tests\Components;

use SqlParser\Parser;
use SqlParser\Components\FieldDefinition;

use SqlParser\Tests\TestCase;

class FieldDefinitionTest extends TestCase
{

    public function testParse()
    {
        $component = FieldDefinition::parse(
            new Parser(),
            $this->getTokensList('(str TEXT, FULLTEXT INDEX indx (str)')
        );
        $this->assertEquals('str', $component[0]->name);
        $this->assertEquals('FULLTEXT INDEX', $component[1]->key->type);
        $this->assertEquals('indx', $component[1]->key->name);
        $this->assertEquals('FULLTEXT INDEX `indx` (`str`)', $component[1]);
    }

    public function testBuild() {
        $parser = new Parser(
            'CREATE TABLE `payment` (' .
            '-- snippet' . "\n" .
            '`customer_id` smallint(5) unsigned NOT NULL,' .
            'CONSTRAINT `fk_payment_customer` FOREIGN KEY (`customer_id`) REFERENCES `customer` (`customer_id`) ON UPDATE CASCADE,' .
            ') ENGINE=InnoDB")'
        );
        $this->assertEquals(
            'CONSTRAINT `fk_payment_customer` FOREIGN KEY (`customer_id`) REFERENCES `customer` (`customer_id`) ON UPDATE CASCADE',
            FieldDefinition::build($parser->statements[0]->fields[1])
        );
    }
}
