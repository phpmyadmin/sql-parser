<?php

namespace PhpMyAdmin\SqlParser\Tests\Builder;

use PhpMyAdmin\SqlParser\Parser;
use PhpMyAdmin\SqlParser\Tests\TestCase;

class SetStatementTest extends TestCase
{
    public function testBuilderView()
    {
        /* Assertion 1 */
        $query = 'SET CHARACTER SET \'utf8\';';

        $parser = new Parser($query);
        $stmt = $parser->statements[0];

        $this->assertEquals(
            'SET CHARACTER SET \'utf8\' ',
            $stmt->build()
        );

        /* Assertion 2 */
        $query = 'SET CHARSET \'utf8\';';

        $parser = new Parser($query);
        $stmt = $parser->statements[0];

        $this->assertEquals(
            'SET CHARSET \'utf8\' ',
            $stmt->build()
        );

        /* Assertion 3 */
        $query = 'SET NAMES \'utf8\';';

        $parser = new Parser($query);
        $stmt = $parser->statements[0];

        $this->assertEquals(
            'SET NAMES \'utf8\' ',
            $stmt->build()
        );
    }
}
