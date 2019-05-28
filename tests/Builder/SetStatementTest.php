<?php

namespace PhpMyAdmin\SqlParser\Tests\Builder;

use PhpMyAdmin\SqlParser\Parser;
use PhpMyAdmin\SqlParser\Tests\TestCase;

class SetStatementTest extends TestCase
{
    public function testBuilderView()
    {
        /* Assertion 1 */
        $query = 'SET CHARACTER SET \'utf8\'';

        $parser = new Parser($query);
        $stmt = $parser->statements[0];

        $this->assertEquals(
            $query,
            $stmt->build()
        );

        /* Assertion 2 */
        $query = 'SET CHARSET \'utf8\'';

        $parser = new Parser($query);
        $stmt = $parser->statements[0];

        $this->assertEquals(
            $query,
            $stmt->build()
        );

        /* Assertion 3 */
        $query = 'SET NAMES \'utf8\'';

        $parser = new Parser($query);
        $stmt = $parser->statements[0];

        $this->assertEquals(
            $query,
            $stmt->build()
        );

        /* Assertion 4 */
        $query = 'SET NAMES \'utf8\' COLLATE \'utf8_general_ci\'';

        $parser = new Parser($query);
        $stmt = $parser->statements[0];

        $this->assertEquals(
            'SET NAMES \'utf8\'  COLLATE \'utf8_general_ci\'',
            $stmt->build()
        );

        /* Assertion 5 */
        $query = 'SET NAMES \'utf8\' DEFAULT';

        $parser = new Parser($query);
        $stmt = $parser->statements[0];

        $this->assertEquals(
            'SET NAMES \'utf8\'  DEFAULT',
            $stmt->build()
        );
    }
}
