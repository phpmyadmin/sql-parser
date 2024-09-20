<?php

declare(strict_types=1);

namespace PhpMyAdmin\SqlParser\Tests\Utils;

use PhpMyAdmin\SqlParser\Parser;
use PhpMyAdmin\SqlParser\Statements\CreateStatement;
use PhpMyAdmin\SqlParser\Tests\TestCase;
use PhpMyAdmin\SqlParser\Utils\ForeignKeyData;
use PhpMyAdmin\SqlParser\Utils\Table;
use PHPUnit\Framework\Attributes\DataProvider;

class TableTest extends TestCase
{
    /** @param list<ForeignKeyData> $expected */
    #[DataProvider('getForeignKeysProvider')]
    public function testGetForeignKeys(string $query, array $expected): void
    {
        $parser = new Parser($query);
        $this->assertInstanceOf(CreateStatement::class, $parser->statements[0]);

        $result = $parser->statements[0]->getForeignKeys();
        $this->assertEquals($expected, $result);
    }

    /** @return list<array{string, list<ForeignKeyData>}> */
    public static function getForeignKeysProvider(): array
    {
        return [
            [
                'CREATE USER test',
                [],
            ],
            [
                'CREATE TABLE `payment` (
                  `payment_id` smallint(5) unsigned NOT NULL AUTO_INCREMENT,
                  `customer_id` smallint(5) unsigned NOT NULL,
                  `staff_id` tinyint(3) unsigned NOT NULL,
                  `rental_id` int(11) DEFAULT NULL,
                  `amount` decimal(5,2) NOT NULL,
                  `payment_date` datetime NOT NULL,
                  `last_update` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                  PRIMARY KEY (`payment_id`),
                  KEY `idx_fk_staff_id` (`staff_id`),
                  KEY `idx_fk_customer_id` (`customer_id`),
                  KEY `fk_payment_rental` (`rental_id`),
                  CONSTRAINT `fk_payment_customer` FOREIGN KEY (`customer_id`)
                      REFERENCES `customer` (`customer_id`) ON UPDATE CASCADE,
                  CONSTRAINT `fk_payment_rental` FOREIGN KEY (`rental_id`)
                      REFERENCES `rental` (`rental_id`) ON DELETE SET NULL ON UPDATE CASCADE,
                  CONSTRAINT `fk_payment_staff` FOREIGN KEY (`staff_id`)
                      REFERENCES `staff` (`staff_id`) ON UPDATE CASCADE
                ) ENGINE=InnoDB AUTO_INCREMENT=16050 DEFAULT CHARSET=utf8',
                [
                    new ForeignKeyData(
                        constraint: 'fk_payment_customer',
                        indexList: ['customer_id'],
                        refTableName: 'customer',
                        refIndexList: ['customer_id'],
                        onUpdate: 'CASCADE',
                    ),
                    new ForeignKeyData(
                        constraint: 'fk_payment_rental',
                        indexList: ['rental_id'],
                        refTableName: 'rental',
                        refIndexList: ['rental_id'],
                        onDelete: 'SET_NULL',
                        onUpdate: 'CASCADE',
                    ),
                    new ForeignKeyData(
                        constraint: 'fk_payment_staff',
                        indexList: ['staff_id'],
                        refTableName: 'staff',
                        refIndexList: ['staff_id'],
                        onUpdate: 'CASCADE',
                    ),
                ],
            ],
            [
                'CREATE TABLE `actor` (
                  `actor_id` smallint(5) unsigned NOT NULL AUTO_INCREMENT,
                  `first_name` varchar(45) NOT NULL,
                  `last_name` varchar(45) NOT NULL,
                  `last_update` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                  PRIMARY KEY (`actor_id`),
                  KEY `idx_actor_last_name` (`last_name`)
                ) ENGINE=InnoDB AUTO_INCREMENT=201 DEFAULT CHARSET=utf8',
                [],
            ],
            [
                'CREATE TABLE `address` (
                  `address_id` smallint(5) unsigned NOT NULL AUTO_INCREMENT,
                  `address` varchar(50) NOT NULL,
                  `address2` varchar(50) DEFAULT NULL,
                  `district` varchar(20) NOT NULL,
                  `city_id` smallint(5) unsigned NOT NULL,
                  `postal_code` varchar(10) DEFAULT NULL,
                  `phone` varchar(20) NOT NULL,
                  `last_update` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                  PRIMARY KEY (`address_id`),
                  KEY `idx_fk_city_id` (`city_id`),
                  CONSTRAINT `fk_address_city` FOREIGN KEY (`city_id`) REFERENCES `city` (`city_id`) ON UPDATE CASCADE
                ) ENGINE=InnoDB AUTO_INCREMENT=606 DEFAULT CHARSET=utf8',
                [
                    new ForeignKeyData(
                        constraint: 'fk_address_city',
                        indexList: ['city_id'],
                        refTableName: 'city',
                        refIndexList: ['city_id'],
                        onUpdate: 'CASCADE',
                    ),
                ],
            ],
        ];
    }

    /**
     * @param array<string, array<string, bool|string>> $expected
     * @psalm-param array<string, array{
     *   type: string,
     *   timestamp_not_null: bool,
     *   default_value?: string,
     *   default_current_timestamp?: bool,
     *   on_update_current_timestamp?: bool,
     *   expr?: string
     * }> $expected
     */
    #[DataProvider('getFieldsProvider')]
    public function testGetFields(string $query, array $expected): void
    {
        $parser = new Parser($query);
        $this->assertInstanceOf(CreateStatement::class, $parser->statements[0]);
        $this->assertEquals($expected, Table::getFields($parser->statements[0]));
    }

    /**
     * @return array<int, array<int, string|array<string, array<string, bool|string>>>>
     * @psalm-return list<array{string, array<string, array{
     *   type: string,
     *   timestamp_not_null: bool,
     *   default_value?: string,
     *   default_current_timestamp?: bool,
     *   on_update_current_timestamp?: bool,
     *   expr?: string
     * }>}>
     */
    public static function getFieldsProvider(): array
    {
        return [
            [
                'CREATE USER test',
                [],
            ],
            [
                'CREATE TABLE `address` (
                  `address_id` smallint(5) unsigned NOT NULL AUTO_INCREMENT,
                  `address` varchar(50) NOT NULL,
                  `address2` varchar(50) DEFAULT NULL,
                  `district` varchar(20) NOT NULL,
                  `city_id` smallint(5) unsigned NOT NULL,
                  `postal_code` varchar(10) DEFAULT NULL,
                  `phone` varchar(20) NOT NULL,
                  `last_update` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                  PRIMARY KEY (`address_id`),
                  KEY `idx_fk_city_id` (`city_id`),
                  CONSTRAINT `fk_address_city` FOREIGN KEY (`city_id`) REFERENCES `city` (`city_id`) ON UPDATE CASCADE
                ) ENGINE=InnoDB AUTO_INCREMENT=606 DEFAULT CHARSET=utf8',
                [
                    'address_id' => [
                        'type' => 'SMALLINT',
                        'timestamp_not_null' => false,
                    ],
                    'address' => [
                        'type' => 'VARCHAR',
                        'timestamp_not_null' => false,
                    ],
                    'address2' => [
                        'type' => 'VARCHAR',
                        'timestamp_not_null' => false,
                        'default_value' => 'NULL',
                    ],
                    'district' => [
                        'type' => 'VARCHAR',
                        'timestamp_not_null' => false,
                    ],
                    'city_id' => [
                        'type' => 'SMALLINT',
                        'timestamp_not_null' => false,
                    ],
                    'postal_code' => [
                        'type' => 'VARCHAR',
                        'timestamp_not_null' => false,
                        'default_value' => 'NULL',
                    ],
                    'phone' => [
                        'type' => 'VARCHAR',
                        'timestamp_not_null' => false,
                    ],
                    'last_update' => [
                        'type' => 'TIMESTAMP',
                        'timestamp_not_null' => true,
                        'default_value' => 'CURRENT_TIMESTAMP',
                        'default_current_timestamp' => true,
                        'on_update_current_timestamp' => true,
                    ],
                ],
            ],
            [
                'CREATE TABLE table1 (
                    a INT NOT NULL,
                    b VARCHAR(32),
                    c INT AS (a mod 10) VIRTUAL,
                    d VARCHAR(5) AS (left(b,5)) PERSISTENT
                )',
                [
                    'a' => [
                        'type' => 'INT',
                        'timestamp_not_null' => false,
                    ],
                    'b' => [
                        'type' => 'VARCHAR',
                        'timestamp_not_null' => false,
                    ],
                    'c' => [
                        'type' => 'INT',
                        'timestamp_not_null' => false,
                        'expr' => '(a mod 10)',
                    ],
                    'd' => [
                        'type' => 'VARCHAR',
                        'timestamp_not_null' => false,
                        'expr' => '(left(b,5))',
                    ],
                ],
            ],
        ];
    }
}
