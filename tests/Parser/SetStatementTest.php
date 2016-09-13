<?php

namespace SqlParser\Tests\Parser;

use SqlParser\Tests\TestCase;

class SetStatementTest extends TestCase
{

    /**
     * @dataProvider testSetProvider
     */
    public function testSet($test)
    {
        $this->runParserTest($test);
    }

    public function testSetProvider()
    {
        return array(
            array('parser/parseSetCharset'),
            array('parser/parseSetCharsetError'),
            array('parser/parseSetCharacterSet'),
            array('parser/parseSetCharacterSetError'),
            array('parser/parseSetNames'),
            array('parser/parseSetNamesError'),
            array('parser/parseSetError1'),
        );
    }
}
