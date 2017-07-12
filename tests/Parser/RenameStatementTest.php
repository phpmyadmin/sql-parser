<?php

namespace PhpMyAdmin\SqlParser\Tests\Parser;

use PhpMyAdmin\SqlParser\Tests\TestCase;

class RenameStatementTest extends TestCase
{
    /**
     * @dataProvider testRenameProvider
     *
     * @param mixed $test
     */
    public function testRename($test)
    {
        $this->runParserTest($test);
    }

    public function testRenameProvider()
    {
        return array(
            array('parser/parseRename'),
            array('parser/parseRename2'),
            array('parser/parseRenameErr1'),
            array('parser/parseRenameErr2'),
            array('parser/parseRenameErr3'),
            array('parser/parseRenameErr4'),
            array('parser/parseRenameErr5'),
        );
    }
}
