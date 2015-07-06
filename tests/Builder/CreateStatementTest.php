<?php

namespace SqlParser\Tests\Builder;

use SqlParser\Parser;
use SqlParser\Fragments\DataTypeFragment;
use SqlParser\Fragments\FieldFragment;
use SqlParser\Fragments\FieldDefFragment;
use SqlParser\Fragments\KeyFragment;
use SqlParser\Fragments\OptionsFragment;
use SqlParser\Statements\CreateStatement;

use SqlParser\Tests\TestCase;

class CreateStatementTest extends TestCase
{

    public function testBuilderNull()
    {
        $stmt = new CreateStatement();
        $stmt->options = new OptionsFragment();
        $this->assertEquals('', $stmt->build());
    }

    public function testBuilderTable()
    {
        $stmt = new CreateStatement();

        $stmt->name = new FieldFragment('', 'test', '');
        $stmt->options = new OptionsFragment(array('TABLE'));
        $stmt->fields = array(
            new FieldDefFragment(
                'id',
                new OptionsFragment(array('NOT NULL', 'AUTO_INCREMENT')),
                new DataTypeFragment('INT', array(11), new OptionsFragment(array('UNSIGNED')))
            ),
            new FieldDefFragment(
                '',
                null,
                new KeyFragment('', array('id'), 'PRIMARY KEY')
            )
        );

        $this->assertEquals(
            'CREATE TABLE `test` (' .
            '`id` INT (11) UNSIGNED NOT NULL AUTO_INCREMENT, ' .
            'PRIMARY KEY (`id`)' .
            ') ',
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

        $stmt->options = new OptionsFragment(array('TRIGGER'));
        $stmt->name = new FieldFragment('ins_sum');
        $stmt->entityOptions = new OptionsFragment(array('BEFORE', 'INSERT'));
        $stmt->table = new FieldFragment('account');
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
