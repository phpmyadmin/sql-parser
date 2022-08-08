<?php

declare(strict_types=1);

namespace PhpMyAdmin\SqlParser\Tests\Builder;

use PhpMyAdmin\SqlParser\Parser;
use PhpMyAdmin\SqlParser\Tests\TestCase;

class AlterStatementTest extends TestCase
{
    public function testBuilder(): void
    {
        $query = 'ALTER TABLE `actor` ' .
            'ADD PRIMARY KEY (`actor_id`), ' .
            'ADD KEY `idx_actor_last_name` (`last_name`)';

        $parser = new Parser($query);
        $stmt = $parser->statements[0];

        $this->assertEquals($query, $stmt->build());
    }

    public function testBuilderCompressed(): void
    {
        $query = 'ALTER TABLE `user` CHANGE `message` `message` TEXT COMPRESSED';
        $parser = new Parser($query);
        $stmt = $parser->statements[0];
        $this->assertEquals($query, $stmt->build());
    }

    public function testBuilderPartitions(): void
    {
        $parser = new Parser('ALTER TABLE t1 PARTITION BY HASH(id) PARTITIONS 8');
        $stmt = $parser->statements[0];

        $this->assertEquals('ALTER TABLE t1 PARTITION BY  HASH(id) PARTITIONS 8 ', $stmt->build());

        $parser = new Parser('ALTER TABLE t1 ADD PARTITION (PARTITION p3 VALUES LESS THAN (2002))');
        $stmt = $parser->statements[0];

        $this->assertEquals(
            "ALTER TABLE t1 ADD PARTITION (\n" .
            "PARTITION p3 VALUES LESS THAN (2002)\n" .
            ')',
            $stmt->build()
        );

        $parser = new Parser('ALTER TABLE p PARTITION BY LINEAR KEY ALGORITHM=2 (id) PARTITIONS 32;');
        $stmt = $parser->statements[0];

        $this->assertEquals(
            'ALTER TABLE p PARTITION BY  LINEAR KEY ALGORITHM=2 (id) PARTITIONS 32 ',
            $stmt->build()
        );

        $parser = new Parser('ALTER TABLE t1 DROP PARTITION p0, p1;');
        $stmt = $parser->statements[0];

        $this->assertEquals(
            'ALTER TABLE t1 DROP PARTITION  p0, p1 ',
            $stmt->build()
        );
    }
}
