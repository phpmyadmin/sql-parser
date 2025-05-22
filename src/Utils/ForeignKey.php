<?php

declare(strict_types=1);

namespace PhpMyAdmin\SqlParser\Utils;

final class ForeignKey
{
    /**
     * @param string[] $indexList
     * @param string[] $refIndexList
     */
    public function __construct(
        public string|null $constraint = null,
        public array $indexList = [],
        public string|null $refDbName = null,
        public string|null $refTableName = null,
        public array $refIndexList = [],
        public string|null $onUpdate = null,
        public string|null $onDelete = null,
    ) {
    }
}
