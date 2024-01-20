<?php

declare(strict_types=1);

namespace PhpMyAdmin\SqlParser;

/**
 * Defines a class that offers the parse() static method.
 */
interface Parseable
{
    /**
     * Parses the tokens contained in the given list in the context of the given parser.
     *
     * @param Parser               $parser  the parser that serves as context
     * @param TokensList           $list    the list of tokens that are being parsed
     * @param array<string, mixed> $options parameters for parsing
     *
     * @return Component|Component[]|null
     */
    public static function parse(Parser $parser, TokensList $list, array $options = []): Component|array|null;
}
