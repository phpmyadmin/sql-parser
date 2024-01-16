<?php

declare(strict_types=1);

namespace PhpMyAdmin\SqlParser\Statements;

use PhpMyAdmin\SqlParser\Parser;
use PhpMyAdmin\SqlParser\Statement;
use PhpMyAdmin\SqlParser\Token;
use PhpMyAdmin\SqlParser\TokensList;
use PhpMyAdmin\SqlParser\TokenType;

/**
 * Not implemented (yet) statements.
 *
 * The `after` function makes the parser jump straight to the first delimiter.
 */
class NotImplementedStatement extends Statement
{
    /**
     * The part of the statement that can't be parsed.
     *
     * @var Token[]
     */
    public array $unknown = [];

    public function build(): string
    {
        // Building the parsed part of the query (if any).
        $query = parent::build() . ' ';

        // Rebuilding the unknown part from tokens.
        foreach ($this->unknown as $token) {
            $query .= $token->token;
        }

        return $query;
    }

    /**
     * @param Parser     $parser the instance that requests parsing
     * @param TokensList $list   the list of tokens to be parsed
     */
    public function parse(Parser $parser, TokensList $list): void
    {
        for (; $list->idx < $list->count; ++$list->idx) {
            if ($list->tokens[$list->idx]->type === TokenType::Delimiter) {
                break;
            }

            $this->unknown[] = $list->tokens[$list->idx];
        }
    }
}
