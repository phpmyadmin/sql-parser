<?php

declare(strict_types=1);

namespace PhpMyAdmin\SqlParser\Parsers;

use PhpMyAdmin\SqlParser\Parseable;
use PhpMyAdmin\SqlParser\Parser;
use PhpMyAdmin\SqlParser\TokensList;
use PhpMyAdmin\SqlParser\Translator;
use RuntimeException;
use Stringable;

use function implode;

/**
 * `UNION` keyword builder.
 */
final class UnionKeywords implements Parseable
{
    /**
     * Parses the tokens contained in the given list in the context of the given parser.
     *
     * @param Parser               $parser  the parser that serves as context
     * @param TokensList           $list    the list of tokens that are being parsed
     * @param array<string, mixed> $options parameters for parsing
     *
     * @throws RuntimeException not implemented yet.
     */
    public static function parse(Parser $parser, TokensList $list, array $options = []): never
    {
        throw new RuntimeException(Translator::gettext('Not implemented yet.'));
    }

    /** @param list<array{string|Stringable,string|Stringable}> $component the component to be built */
    public static function buildAll(array $component): string
    {
        $tmp = [];
        foreach ($component as $componentPart) {
            $tmp[] = $componentPart[0] . ' ' . $componentPart[1];
        }

        return implode(' ', $tmp);
    }
}
