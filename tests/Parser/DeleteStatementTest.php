<?php

namespace SqlParser\Tests\Parser;

use SqlParser\Tests\TestCase;

class DeleteStatementTest extends TestCase
{

    /**
     * @dataProvider testDeleteProvider
     */
    public function testDelete($test)
    {
        $this->runParserTest($test);
    }

    public function testDeleteProvider()
    {
        return array(
            array('parser/parseDelete'),
            array('parser/parseDelete2'),
            array('parser/parseDelete3'),
            array('parser/parseDelete4'),
            array('parser/parseDelete5'),
            array('parser/parseDelete6'),
            array('parser/parseDelete7'),
            array('parser/parseDelete8'),
            array('parser/parseDelete9'),
            array('parser/parseDelete10'),
        );
    }
}
