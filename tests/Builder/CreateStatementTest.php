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
        $query = 'CREATE VIEW myView (vid, vfirstname) AS ' .
            'SELECT id, first_name FROM employee WHERE id = 1';

        $parser = new Parser($query);
        $stmt = $parser->statements[0];

        $this->assertEquals(
            'CREATE VIEW myView (vid, vfirstname) AS  ' .
            'SELECT id, first_name FROM employee WHERE id = 1 ',
            $stmt->build()
        );
    }
}
