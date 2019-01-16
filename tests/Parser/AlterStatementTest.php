<?php

namespace PhpMyAdmin\SqlParser\Tests\Parser;

use PhpMyAdmin\SqlParser\Tests\TestCase;

class AlterStatementTest extends TestCase
{
    /**
     * @dataProvider testAlterProvider
     *
     * @param mixed $test
     */
    public function testAlter($test)
    {
        $this->runParserTest($test);
    }

    public function testAlterProvider()
    {
        return array(
            array('parser/parseAlter'),
            array('parser/parseAlter2'),
            array('parser/parseAlter3'),
            array('parser/parseAlter4'),
            array('parser/parseAlter5'),
            array('parser/parseAlter6'),
            array('parser/parseAlter7'),
            array('parser/parseAlter8'),
            array('parser/parseAlterErr'),
            array('parser/parseAlterErr2'),
        );
    }
}
