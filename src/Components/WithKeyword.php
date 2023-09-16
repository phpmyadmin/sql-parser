<?php

declare(strict_types=1);

namespace PhpMyAdmin\SqlParser\Components;

use PhpMyAdmin\SqlParser\Component;
use PhpMyAdmin\SqlParser\Parser;
use PhpMyAdmin\SqlParser\TokensList;
use PhpMyAdmin\SqlParser\Translator;
use RuntimeException;

/**
 * `WITH` keyword builder.
 */
final class WithKeyword implements Component
{
    /** @var string */
    public $name;

    /** @var ArrayObj[] */
    public $columns = [];

    /** @var Parser|null */
    public $statement;

    public function __construct(string $name)
    {
        $this->name = $name;
    }

    /**
     * Parses the tokens contained in the given list in the context of the given parser.
     *
     * @param Parser               $parser  the parser that serves as context
     * @param TokensList           $list    the list of tokens that are being parsed
     * @param array<string, mixed> $options parameters for parsing
     *
     * @throws RuntimeException not implemented yet.
     */
    public static function parse(Parser $parser, TokensList $list, array $options = []): mixed
    {
        throw new RuntimeException(Translator::gettext('Not implemented yet.'));
    }

    public function build(): string
    {
        if (! isset($this->statement)) {
            throw new RuntimeException('No statement inside WITH');
        }

        $str = $this->name;

        if ($this->columns) {
            $str .= ArrayObj::buildAll($this->columns);
        }

        $str .= ' AS (';

        foreach ($this->statement->statements as $statement) {
            $str .= $statement->build();
        }

        $str .= ')';

        return $str;
    }

    public function __toString(): string
    {
        return $this->build();
    }
}
