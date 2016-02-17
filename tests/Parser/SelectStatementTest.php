<?php

namespace SqlParser\Tests\Parser;

use SqlParser\Tests\TestCase;

use SqlParser\Parser;

class SelectStatementTest extends TestCase
{

    public function testSelectOptions()
    {
        $data = $this->getData('parser/parseSelect');
        $parser = new Parser($data['query']);
        $stmt = $parser->statements[0];
        $this->assertEquals(10, $stmt->options->has('MAX_STATEMENT_TIME'));
    }

    /**
     * @dataProvider testSelectProvider
     */
    public function testSelect($test)
    {
        $this->runParserTest($test);
    }

    public function testSelectProvider()
    {
        return array(
            array('parser/parseSelect2'),
            array('parser/parseSelect3'),
            array('parser/parseSelect4'),
            array('parser/parseSelectErr1'),
            array('parser/parseSelectNested'),
        );
    }
}
