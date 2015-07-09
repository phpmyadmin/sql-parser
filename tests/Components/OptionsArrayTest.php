<?php

namespace SqlParser\Tests\Components;

use SqlParser\Parser;
use SqlParser\Components\OptionsArray;

use SqlParser\Tests\TestCase;

class OptionsArrayTest extends TestCase
{

    public function testParse()
    {
        $component = OptionsArray::parse(
            new Parser(),
            $this->getTokensList('A B = (test) C'),
            array(
                'A' => 1,
                'B' => array(2, 'var'),
                'C' => 3,
            )
        );
        $this->assertEquals(
            array(
                1 => 'A',
                2 => array(
                    'name' => 'B',
                    'value' => 'test',
                    'value_' => 'test',
                    'equal' => false,
                ),
                3 => 'C',
            ),
            $component->options
        );
    }

    public function testMerge()
    {
        $component = new OptionsArray(array('a'));
        $component->merge(array('b', 'c'));
        $this->assertEquals($component->options, array('a', 'b', 'c'));
    }

    public function testBuild()
    {
        $component = new OptionsArray(
            array(
                'ALL',
                'SQL_CALC_FOUND_ROWS',
                array(
                    'name' => 'MAX_STATEMENT_TIME',
                    'value' => '42',
                    'value_' => '42',
                    'equal' => true,
                ),
            )
        );
        $this->assertEquals(
            OptionsArray::build($component),
            'ALL SQL_CALC_FOUND_ROWS MAX_STATEMENT_TIME=42'
        );
    }
}
