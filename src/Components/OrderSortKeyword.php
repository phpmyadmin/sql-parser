<?php

declare(strict_types=1);

namespace PhpMyAdmin\SqlParser\Components;

enum OrderSortKeyword: string
{
    case Asc = 'ASC';
    case Desc = 'DESC';
}
