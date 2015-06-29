<?php

namespace SqlParser\Tests\Builder;

use SqlParser\Builder;
use SqlParser\Fragments\OptionsFragment;
use SqlParser\Fragments\FieldFragment;
use SqlParser\Fragments\WhereKeyword;
use SqlParser\Fragments\LimitKeyword;
use SqlParser\Statements\SelectStatement;

use SqlParser\Tests\TestCase;

class StatementTest extends TestCase
{

    public function testBuilder()
    {
        $stmt = new SelectStatement();

        $stmt->options = new OptionsFragment(array('DISTINCT'));

        $stmt->expr[] = new FieldFragment('sakila', 'film', 'film_id', 'fid');
        $stmt->expr[] = new FieldFragment('COUNT(film_id)');

        $stmt->from[] = new FieldFragment('', 'film', '');
        $stmt->from[] = new FieldFragment('', 'actor', '');

        $stmt->where[] = new WhereKeyword('film_id > 10');
        $stmt->where[] = new WhereKeyword('OR');
        $stmt->where[] = new WhereKeyword('actor.age > 25');

        $stmt->limit = new LimitKeyword(1, 10);

        $builder = new Builder($stmt);

        $this->assertEquals(
            'SELECT DISTINCT sakila.film.film_id AS fid, COUNT(film_id) ' .
            'FROM film, actor ' .
            'WHERE film_id > 10 OR actor.age > 25 ' .
            'LIMIT 10, 1 ',
            $builder->query
        );
    }
}
