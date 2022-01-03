<?php

declare(strict_types=1);

namespace PhpMyAdmin\SqlParser;

use Stringable;

/**
 * A component (of a statement) is a part of a statement that is common to multiple query types.
 *
 * Defines a component that is later extended to parse specialized components or keywords.
 *
 * There is a small difference between *Component and *Keyword classes: usually, *Component parsers can be reused
 * in multiple situations and *Keyword parsers count on the *Component classes to do their job.
 */
interface Component extends Stringable
{
    /**
     * Parses the tokens contained in the given list in the context of the given parser.
     *
     * @param Parser     $parser  the parser that serves as context
     * @param TokensList $list    the list of tokens that are being parsed
     * @param array      $options parameters for parsing
     *
     * @return mixed
     */
    public static function parse(Parser $parser, TokensList $list, array $options = []);

    /**
     * Builds the string representation of a component of this type.
     *
     * In other words, this function represents the inverse function of {@see Component::parse()}.
     *
     * @param mixed $component the component to be built
     * @param array $options   parameters for building
     *
     * @return mixed
     */
    public static function build($component, array $options = []);
}
