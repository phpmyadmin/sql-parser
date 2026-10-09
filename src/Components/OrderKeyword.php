<?php

declare(strict_types=1);

namespace PhpMyAdmin\SqlParser\Components;

use PhpMyAdmin\SqlParser\SerializableComponent;

/**
 * `ORDER BY` keyword parser.
 */
final class OrderKeyword extends SerializableComponent
{
    /**
     * The expression that is used for ordering.
     */
    public Expression|null $expr = null;

    /**
     * The order type.
     */
    public OrderSortKeyword $type;

    /**
     * @param Expression|null  $expr the expression that we are sorting by
     * @param OrderSortKeyword $type the sorting type
     */
    public function __construct(Expression|null $expr = null, OrderSortKeyword $type = OrderSortKeyword::Asc)
    {
        $this->expr = $expr;
        $this->type = $type;
    }

    public function build(): string
    {
        return $this->expr . ' ' . $this->type->value;
    }
}
