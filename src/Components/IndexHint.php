<?php

declare(strict_types=1);

namespace PhpMyAdmin\SqlParser\Components;

use PhpMyAdmin\SqlParser\Component;
use PhpMyAdmin\SqlParser\Parsers\Expressions;

/**
 * Parses an Index hint.
 */
final class IndexHint implements Component
{
    /**
     * @param string       $type       The type of hint (USE/FORCE/IGNORE)
     * @param string       $indexOrKey What the hint is for (INDEX/KEY)
     * @param string|null  $for        The clause for which this hint is (JOIN/ORDER BY/GROUP BY)
     * @param Expression[] $indexes    List of indexes in this hint
     */
    public function __construct(
        public string $type = '',
        public string $indexOrKey = '',
        public string|null $for = null,
        public array $indexes = [],
    ) {
    }

    public function build(): string
    {
        $ret = $this->type . ' ' . $this->indexOrKey . ' ';
        if ($this->for !== null) {
            $ret .= 'FOR ' . $this->for . ' ';
        }

        return $ret . Expressions::buildAll($this->indexes);
    }

    public function __toString(): string
    {
        return $this->build();
    }
}
