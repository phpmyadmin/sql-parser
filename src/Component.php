<?php

declare(strict_types=1);

namespace PhpMyAdmin\SqlParser;

use Stringable;

/**
 * Defines a component that is later extended to parse specialized components or keywords.
 *
 * There is a small difference between *Component and *Keyword classes: usually, *Component parsers can be reused in
 * multiple situations and *Keyword parsers count on the *Component classes to do their job.
 *
 * A component (of a statement) is a part of a statement that is common to multiple query types.
 */
interface Component extends Stringable
{
    /**
     * Builds the string representation of a component of this type.
     *
     * In other words, this function represents the inverse function of {@see Component::parse()}.
     */
    public function build(): string;
}
