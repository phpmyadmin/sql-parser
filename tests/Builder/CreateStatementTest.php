<?php

namespace SqlParser\Tests\Builder;

use SqlParser\Parser;
use SqlParser\Components\DataType;
use SqlParser\Components\Expression;
use SqlParser\Components\CreateDefinition;
use SqlParser\Components\Key;
use SqlParser\Components\OptionsArray;
use SqlParser\Statements\CreateStatement;

use SqlParser\Tests\TestCase;

class CreateStatementTest extends TestCase
{

    public function testBuilder()
    {
        $parser = new Parser(
            'CREATE USER "jeffrey"@"localhost" IDENTIFIED BY "mypass"'
        );
        $stmt = $parser->statements[0];
        $this->assertEquals(
            'CREATE USER "jeffrey"@"localhost" IDENTIFIED BY "mypass"',
            $stmt->build()
        );
    }

    public function testBuilderDatabase()
    {
        $parser = new Parser(
            'CREATE DATABASE `mydb` ' .
            'DEFAULT CHARACTER SET = utf8 DEFAULT COLLATE = utf8_general_ci'
        );
        $stmt = $parser->statements[0];

        $this->assertEquals(
            'CREATE DATABASE `mydb` ' .
            'DEFAULT CHARACTER SET=utf8 DEFAULT COLLATE=utf8_general_ci',
            $stmt->build()
        );
    }

    public function testBuilderTable()
    {
        $stmt = new CreateStatement();

        $stmt->name = new Expression('', 'test', '');
        $stmt->options = new OptionsArray(array('TABLE'));
        $stmt->fields = array(
            new CreateDefinition(
                'id',
                new OptionsArray(array('NOT NULL', 'AUTO_INCREMENT')),
                new DataType('INT', array(11), new OptionsArray(array('UNSIGNED')))
            ),
            new CreateDefinition(
                '',
                null,
                new Key('', array(array('name' => 'id')), 'PRIMARY KEY')
            )
        );

        $this->assertEquals(
            "CREATE TABLE `test` (\n" .
            "  `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT,\n" .
            "  PRIMARY KEY (`id`)\n" .
            ") ",
            $stmt->build()
        );

        $query =
            "CREATE TABLE `jos_core_acl_aro` (\n" .
            "  `id` int(11) NOT NULL,\n" .
            "  `section_value` varchar(240) NOT NULL DEFAULT '0',\n" .
            "  `value` varchar(240) NOT NULL DEFAULT '',\n" .
            "  `order_value` int(11) NOT NULL DEFAULT '0',\n" .
            "  `name` varchar(255) NOT NULL DEFAULT '',\n" .
            "  `hidden` int(11) NOT NULL DEFAULT '0',\n" .
            "  PRIMARY KEY (`id`),\n" .
            "  UNIQUE KEY `jos_section_value_value_aro` (`section_value`(100),`value`(15)) USING BTREE,\n" .
            "  KEY `jos_gacl_hidden_aro` (`hidden`)\n" .
            ") ENGINE=InnoDB DEFAULT CHARSET=latin1";
        $parser = new Parser($query);
        $this->assertEquals($query, $parser->statements[0]->build());
    }

    public function testBuilderPartitions()
    {
        $query = 'CREATE TABLE ts (' . "\n"
            . '  `id` int,' . "\n"
            . '  `purchased` date' . "\n"
            . ') ' . "\n"
            . 'PARTITION BY RANGE(YEAR(purchased))' . "\n"
            . 'PARTITIONS 3' . "\n"
            . 'SUBPARTITION BY HASH(TO_DAYS(purchased))' . "\n"
            . 'SUBPARTITIONS 2' . "\n"
            . '(' . "\n"
            . 'PARTITION p0 VALUES LESS THAN (1990) (' . "\n"
            . 'SUBPARTITION s0,' . "\n"
            . 'SUBPARTITION s1' . "\n"
            . '),' . "\n"
            . 'PARTITION p1 VALUES LESS THAN (2000) (' . "\n"
            . 'SUBPARTITION s2,' . "\n"
            . 'SUBPARTITION s3' . "\n"
            . '),' . "\n"
            . 'PARTITION p2 VALUES LESS THAN MAXVALUE (' . "\n"
            . 'SUBPARTITION s4,' . "\n"
            . 'SUBPARTITION s5' . "\n"
            . ')' . "\n"
            . ')';
        $parser = new Parser($query);
        $this->assertEquals($query, $parser->statements[0]->build());
    }

    public function testBuilderView()
    {
        $parser = new Parser(
            'CREATE VIEW myView (vid, vfirstname) AS ' .
            'SELECT id, first_name FROM employee WHERE id = 1'
        );
        $stmt = $parser->statements[0];

        $this->assertEquals(
            'CREATE VIEW myView (vid, vfirstname) AS  ' .
            'SELECT id, first_name FROM employee WHERE id = 1 ',
            $stmt->build()
        );
    }

    public function testBuilderTrigger()
    {
        $stmt = new CreateStatement();

        $stmt->options = new OptionsArray(array('TRIGGER'));
        $stmt->name = new Expression('ins_sum');
        $stmt->entityOptions = new OptionsArray(array('BEFORE', 'INSERT'));
        $stmt->table = new Expression('account');
        $stmt->body = 'SET @sum = @sum + NEW.amount';

        $this->assertEquals(
            'CREATE TRIGGER ins_sum BEFORE INSERT ON account ' .
            'FOR EACH ROW SET @sum = @sum + NEW.amount',
            $stmt->build()
        );
    }

    public function testBuilderRoutine()
    {
        $parser = new Parser(
            'CREATE FUNCTION test (IN `i` INT) RETURNS VARCHAR ' .
            'BEGIN ' .
            'DECLARE name VARCHAR DEFAULT ""; ' .
            'SELECT name INTO name FROM employees WHERE id = i; ' .
            'RETURN name; ' .
            'END'
        );
        $stmt = $parser->statements[0];

        $this->assertEquals(
            'CREATE FUNCTION test (IN `i` INT) RETURNS VARCHAR ' .
            'BEGIN ' .
            'DECLARE name VARCHAR DEFAULT ""; ' .
            'SELECT name INTO name FROM employees WHERE id = i; ' .
            'RETURN name; ' .
            'END',
            $stmt->build()
        );
    }
}
