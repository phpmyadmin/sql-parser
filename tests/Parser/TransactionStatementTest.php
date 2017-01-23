<?php

namespace PhpMyAdmin\SqlParser\Tests\Parser;

use PhpMyAdmin\SqlParser\Tests\TestCase;

class TransactionStatementTest extends TestCase
{
    /**
     * @dataProvider testTransactionProvider
     */
    public function testTransaction($test)
    {
        $this->runParserTest($test);
    }

    public function testTransactionProvider()
    {
        return array(
            array('parser/parseTransaction'),
            array('parser/parseTransaction2'),
            array('parser/parseTransactionErr1'),
        );
    }
}
