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
     * The type of hint (USE/FORCE/IGNORE)
     */
    public string|null $type;

    /**
     * What the hint is for (INDEX/KEY)
     */
    public string|null $indexOrKey;

    /**
     * The clause for which this hint is (JOIN/ORDER BY/GROUP BY)
     */
    public string|null $for;

    /**
     * List of indexes in this hint
     *
     * @var Expression[]
     */
    public array $indexes = [];

    /**
     * @param string       $type       the type of hint (USE/FORCE/IGNORE)
     * @param string       $indexOrKey What the hint is for (INDEX/KEY)
     * @param string       $for        the clause for which this hint is (JOIN/ORDER BY/GROUP BY)
     * @param Expression[] $indexes    List of indexes in this hint
     */
    public function __construct(
        string|null $type = null,
        string|null $indexOrKey = null,
        string|null $for = null,
        array $indexes = [],
    ) {
        $this->type = $type;
        $this->indexOrKey = $indexOrKey;
        $this->for = $for;
        $this->indexes = $indexes;
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
