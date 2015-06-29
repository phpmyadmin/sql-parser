<?php

namespace SqlParser\Tests\Fragments;

use SqlParser\Parser;
use SqlParser\Fragments\OptionsFragment;

use SqlParser\Tests\TestCase;

class OptionsFragmentTest extends TestCase
{

    public function testParse()
    {
        $fragment = OptionsFragment::parse(
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
                ),
                3 => 'C',
            ),
            $fragment->options
        );
    }

    public function testMerge()
    {
        $fragment = new OptionsFragment(array('a'));
        $fragment->merge(array('b', 'c'));
        $this->assertEquals($fragment->options, array('a', 'b', 'c'));
    }

    public function testBuild()
    {
        $fragment = new OptionsFragment(
            array(
                'ALL',
                'SQL_CALC_FOUND_ROWS',
                array(
                    'name' => 'MAX_STATEMENT_TIME',
                    'value' => '42',
                ),
            )
        );
        $this->assertEquals(
            OptionsFragment::build($fragment),
            'ALL SQL_CALC_FOUND_ROWS MAX_STATEMENT_TIME=42'
        );
    }
}
