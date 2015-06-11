<?php

use SqlParser\Parser;
use SqlParser\Utils\Routine;

class RoutineTest extends TestCase
{

    /**
     * @dataProvider getParametersProvider
     */
    public function testGetParameters($query, array $expected)
    {
        $parser = new Parser($query);
        $this->assertEquals($expected, Routine::getParameters($parser->statements[0]));
    }

    public function getParametersProvider()
    {
        return array(
            array(
                'CREATE PROCEDURE `foo`() SET @A=0',
                array(
                    'num' => 0,
                    'dir' => array(),
                    'name' => array(),
                    'type' => array(),
                    'length' => array(),
                    'opts' => array()
                )
            ),
            array(
                'CREATE DEFINER=`user\\`@`somehost``(` FUNCTION `foo```(`baz` INT) BEGIN SELECT NULL; END',
                array(
                    'num' => 1,
                    'dir' => array(
                        0 => ''
                    ),
                    'name' => array(
                        0 => 'baz'
                    ),
                    'type' => array(
                        0 => 'INT'
                    ),
                    'length' => array(
                        0 => ''
                    ),
                    'opts' => array(
                        0 => ''
                    )
                )
            ),
            array(
                'CREATE PROCEDURE `foo`(IN `baz\\)` INT(25) zerofill unsigned) BEGIN SELECT NULL; END',
                array(
                    'num' => 1,
                    'dir' => array(
                        0 => 'IN'
                    ),
                    'name' => array(
                        0 => 'baz\\)'
                    ),
                    'type' => array(
                        0 => 'INT'
                    ),
                    'length' => array(
                        0 => '25'
                    ),
                    'opts' => array(
                        0 => 'UNSIGNED ZEROFILL'
                    )
                )
            ),
            array(
                'CREATE PROCEDURE `foo`(IN `baz\\` INT(001) zerofill, out bazz varchar(15) charset utf8) BEGIN SELECT NULL; END',
                array(
                    'num' => 2,
                    'dir' => array(
                        0 => 'IN',
                        1 => 'OUT'
                    ),
                    'name' => array(
                        0 => 'baz\\',
                        1 => 'bazz'
                    ),
                    'type' => array(
                        0 => 'INT',
                        1 => 'VARCHAR'
                    ),
                    'length' => array(
                        0 => '1',
                        1 => '15'
                    ),
                    'opts' => array(
                        0 => 'ZEROFILL',
                        1 => 'utf8'
                    )
                )
            ),
        );
    }

}
