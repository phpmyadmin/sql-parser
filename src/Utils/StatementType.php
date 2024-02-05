<?php

declare(strict_types=1);

namespace PhpMyAdmin\SqlParser\Utils;

enum StatementType: string
{
    case Alter = 'ALTER';
    case Analyze = 'ANALYZE';
    case Call = 'CALL';
    case Check = 'CHECK';
    case Checksum = 'CHECKSUM';
    case Create = 'CREATE';
    case Delete = 'DELETE';
    case Drop = 'DROP';
    case Explain = 'EXPLAIN';
    case Insert = 'INSERT';
    case Load = 'LOAD';
    case Optimize = 'OPTIMIZE';
    case Repair = 'REPAIR';
    case Replace = 'REPLACE';
    case Select = 'SELECT';
    case Set = 'SET';
    case Show = 'SHOW';
    case Update = 'UPDATE';
}
