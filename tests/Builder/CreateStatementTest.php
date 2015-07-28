<?php

namespace SqlParser\Tests\Builder;

use SqlParser\Parser;
use SqlParser\Components\DataType;
use SqlParser\Components\Expression;
use SqlParser\Components\FieldDefinition;
use SqlParser\Components\Key;
use SqlParser\Components\OptionsArray;
use SqlParser\Statements\CreateStatement;

use SqlParser\Tests\TestCase;

class CreateStatementTest extends TestCase
{

    public function testBuilderNull()
    {
        $stmt = new CreateStatement();
        $stmt->options = new OptionsArray();
        $this->assertEquals('', $stmt->build());
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
            new FieldDefinition(
                'id',
                new OptionsArray(array('NOT NULL', 'AUTO_INCREMENT')),
                new DataType('INT', array(11), new OptionsArray(array('UNSIGNED')))
            ),
            new FieldDefinition(
                '',
                null,
                new Key('', array('id'), 'PRIMARY KEY')
            )
        );

        $this->assertEquals(
            "CREATE TABLE `test` (\n" .
            "`id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,\n" .
            "PRIMARY KEY (`id`)\n" .
            ") ",
            $stmt->build()
        );
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
