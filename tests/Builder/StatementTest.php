<?php

declare(strict_types=1);

namespace PhpMyAdmin\SqlParser\Tests\Builder;

use PhpMyAdmin\SqlParser\Components\Condition;
use PhpMyAdmin\SqlParser\Components\Expression;
use PhpMyAdmin\SqlParser\Components\Limit;
use PhpMyAdmin\SqlParser\Components\OptionsArray;
use PhpMyAdmin\SqlParser\Parser;
use PhpMyAdmin\SqlParser\Statements\SelectStatement;
use PhpMyAdmin\SqlParser\Tests\TestCase;

class StatementTest extends TestCase
{
    public function testBuilder(): void
    {
        $stmt = new SelectStatement();

        $stmt->options = new OptionsArray(['DISTINCT']);

        $stmt->expr[] = new Expression('sakila', 'film', 'film_id', 'fid');
        $stmt->expr[] = new Expression('COUNT(film_id)');

        $stmt->from[] = new Expression('', 'film', '');
        $stmt->from[] = new Expression('', 'actor', '');

        $stmt->where[] = new Condition('film_id > 10');
        $stmt->where[] = new Condition('OR');
        $stmt->where[] = new Condition('actor.age > 25');

        $stmt->limit = new Limit(1, 10);

        $this->assertEquals(
            'SELECT DISTINCT `sakila`.`film`.`film_id` AS `fid`, COUNT(film_id) ' .
            'FROM `film`, `actor` ' .
            'WHERE film_id > 10 OR actor.age > 25 ' .
            'LIMIT 10, 1',
            (string) $stmt
        );
    }

    /**
     * @psalm-param array<string, array{
     *   alias: (string|null),
     *   tables: array<string, array{alias: (string|null), columns: array<string, string>}>
     * }> $expected
     *
     * @dataProvider getAliasesProvider
     */
    public function testGetAliases(string $query, string $db, array $expected): void
    {
        $parser = new Parser($query);
        $this->assertInstanceOf(SelectStatement::class, $parser->statements[0]);
        $this->assertEquals($expected, $parser->statements[0]->getAliases($db));
    }

    /**
     * @psalm-return list<array{string, string, array<string, array{
     *   alias: (string|null),
     *   tables: array<string, array{alias: (string|null), columns: array<string, string>}>
     * }>}>
     */
    public static function getAliasesProvider(): array
    {
        return [
            [
                'select * from (select 1) tbl',
                'mydb',
                [],
            ],
            [
                'select i.name as `n`,abcdef gh from qwerty i',
                'mydb',
                [
                    'mydb' => [
                        'alias' => null,
                        'tables' => [
                            'qwerty' => [
                                'alias' => 'i',
                                'columns' => [
                                    'name' => 'n',
                                    'abcdef' => 'gh',
                                ],
                            ],
                        ],
                    ],
                ],
            ],
            [
                'select film_id id,title from film',
                'sakila',
                [
                    'sakila' => [
                        'alias' => null,
                        'tables' => [
                            'film' => [
                                'alias' => null,
                                'columns' => ['film_id' => 'id'],
                            ],
                        ],
                    ],
                ],
            ],
            [
                'select `sakila`.`A`.`actor_id` as aid,`F`.`film_id` `fid`,'
                . 'last_update updated from `sakila`.actor A join `film_actor` as '
                . '`F` on F.actor_id = A.`actor_id`',
                'sakila',
                [
                    'sakila' => [
                        'alias' => null,
                        'tables' => [
                            'film_actor' => [
                                'alias' => 'F',
                                'columns' => [
                                    'film_id' => 'fid',
                                    'last_update' => 'updated',
                                ],
                            ],
                            'actor' => [
                                'alias' => 'A',
                                'columns' => [
                                    'actor_id' => 'aid',
                                    'last_update' => 'updated',
                                ],
                            ],
                        ],
                    ],
                ],
            ],
            [
                'SELECT film_id FROM (SELECT * FROM film) as f;',
                'sakila',
                [],
            ],
            [
                'SELECT 1',
                '',
                [],
            ],
            [
                'SELECT * FROM orders AS ord WHERE 1',
                'db',
                [
                    'db' => [
                        'alias' => null,
                        'tables' => [
                            'orders' => [
                                'alias' => 'ord',
                                'columns' => [],
                            ],
                        ],
                    ],
                ],
            ],
        ];
    }
}
