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

    public function testBuilderWithComments(): void
    {
        $query = 'ALTER /* comment */ TABLE `actor` ' .
            'ADD PRIMARY KEY (`actor_id`), -- comment at the end of the line' . "\n" .
            'ADD KEY `idx_actor_last_name` (`last_name`) -- and that is the last comment.';

        $expectedQuery = 'ALTER TABLE `actor` ' .
            'ADD PRIMARY KEY (`actor_id`), ' .
            'ADD KEY `idx_actor_last_name` (`last_name`)';

        $parser = new Parser($query);
        $stmt = $parser->statements[0];

        $this->assertEquals($expectedQuery, $stmt->build());
    }

    public function testBuilderWithCommentsOnOptions(): void
    {
        $query = 'ALTER EVENT `myEvent` /* comment */ ' .
            'ON SCHEDULE -- Comment at the end of the line' . "\n" .
            'AT "2023-01-01 01:23:45"';

        $expectedQuery = 'ALTER EVENT `myEvent` ' .
            'ON SCHEDULE AT "2023-01-01 01:23:45"';

        $parser = new Parser($query);
        $stmt = $parser->statements[0];

        $this->assertEquals($expectedQuery, $stmt->build());
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

        $this->assertEquals('ALTER TABLE t1 PARTITION BY  HASH(id) PARTITIONS 8', $stmt->build());

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
            'ALTER TABLE p PARTITION BY  LINEAR KEY ALGORITHM=2 (id) PARTITIONS 32',
            $stmt->build()
        );

        $parser = new Parser('ALTER TABLE t1 DROP PARTITION p0, p1;');
        $stmt = $parser->statements[0];

        $this->assertEquals(
            'ALTER TABLE t1 DROP PARTITION  p0, p1',
            $stmt->build()
        );
    }

    public function testBuilderEventWithDefiner(): void
    {
        $query = 'ALTER DEFINER=user EVENT myEvent ENABLE';
        $parser = new Parser($query);
        $stmt = $parser->statements[0];
        $this->assertEquals($query, $stmt->build());
    }
}
