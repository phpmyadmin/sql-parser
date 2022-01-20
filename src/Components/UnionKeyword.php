<?php

declare(strict_types=1);

namespace PhpMyAdmin\SqlParser\Components;

use PhpMyAdmin\SqlParser\Component;
use PhpMyAdmin\SqlParser\Parser;
use PhpMyAdmin\SqlParser\TokensList;
use PhpMyAdmin\SqlParser\Translator;
use RuntimeException;

use function implode;

/**
 * `UNION` keyword builder.
 */
final class UnionKeyword implements Component
{
    /**
     * Parses the tokens contained in the given list in the context of the given parser.
     *
     * @param Parser               $parser  the parser that serves as context
     * @param TokensList           $list    the list of tokens that are being parsed
     * @param array<string, mixed> $options parameters for parsing
     *
     * @return mixed
     *
     * @throws RuntimeException not implemented yet.
     */
    public static function parse(Parser $parser, TokensList $list, array $options = [])
    {
        throw new RuntimeException(Translator::gettext('Not implemented yet.'));
    }

    /**
     * @param array<UnionKeyword[]> $component the component to be built
     * @param array<string, mixed>  $options   parameters for building
     */
    public static function build($component, array $options = []): string
    {
        $tmp = [];
        foreach ($component as $componentPart) {
            $tmp[] = $componentPart[0] . ' ' . $componentPart[1];
        }

        return implode(' ', $tmp);
    }

    public function __toString(): string
    {
        return static::build($this);
    }
}
