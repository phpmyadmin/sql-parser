<?php

declare(strict_types=1);

namespace PhpMyAdmin\SqlParser\Tests\Components;

use PhpMyAdmin\SqlParser\Components\Expression;
use PhpMyAdmin\SqlParser\Components\OrderKeyword;
use PhpMyAdmin\SqlParser\Components\OrderSortKeyword;
use PhpMyAdmin\SqlParser\Parsers\OrderKeywords;
use PhpMyAdmin\SqlParser\Tests\TestCase;

class OrderKeywordTest extends TestCase
{
    public function testBuildAll(): void
    {
        $this->assertEquals(
            'a ASC, b DESC',
            OrderKeywords::buildAll(
                [
                    new OrderKeyword(new Expression('a'), OrderSortKeyword::Asc),
                    new OrderKeyword(new Expression('b'), OrderSortKeyword::Desc),
                ],
            ),
        );
    }
}
