<?php

namespace SqlParser\Tests\Components;

use SqlParser\Parser;
use SqlParser\Components\Expression;
use SqlParser\Components\OrderKeyword;

use SqlParser\Tests\TestCase;

class OrderKeywordTest extends TestCase
{

    public function testBuild()
    {
        $this->assertEquals(
            OrderKeyword::build(
                array(
                    new OrderKeyword(new Expression('a'), 'ASC'),
                    new OrderKeyword(new Expression('b'), 'DESC')
                )
            ),
            'a ASC, b DESC'
        );
    }
}
