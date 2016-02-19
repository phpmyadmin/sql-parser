<?php

namespace SqlParser\Tests\Utils;

use SqlParser\Utils\Formatter;

use SqlParser\Tests\TestCase;

class FormatTest extends TestCase
{
    /**
     * @dataProvider formatQueries
     */
    public function testFormat($query, $expected)
    {
        $this->assertEquals(
            $expected,
            Formatter::format($query, array('type' => 'html'))
        );
    }

    public function formatQueries()
    {
        return array(
            array(
                'SELECT 1',
                '<span class="sql-reserved">SELECT</span>' . "\n" .
                '  <span class="sql-number">1</span>'
            ),
        );
    }
}
