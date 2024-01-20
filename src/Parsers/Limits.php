<?php

declare(strict_types=1);

namespace PhpMyAdmin\SqlParser\Parsers;

use PhpMyAdmin\SqlParser\Components\Limit;
use PhpMyAdmin\SqlParser\Parseable;
use PhpMyAdmin\SqlParser\Parser;
use PhpMyAdmin\SqlParser\Token;
use PhpMyAdmin\SqlParser\TokensList;
use PhpMyAdmin\SqlParser\TokenType;

/**
 * `LIMIT` keyword parser.
 */
final class Limits implements Parseable
{
    /**
     * @param Parser               $parser  the parser that serves as context
     * @param TokensList           $list    the list of tokens that are being parsed
     * @param array<string, mixed> $options parameters for parsing
     */
    public static function parse(Parser $parser, TokensList $list, array $options = []): Limit
    {
        $ret = new Limit();

        $offset = false;

        for (; $list->idx < $list->count; ++$list->idx) {
            /**
             * Token parsed at this moment.
             */
            $token = $list->tokens[$list->idx];

            // End of statement.
            if ($token->type === TokenType::Delimiter) {
                break;
            }

            // Skipping whitespaces and comments.
            if (($token->type === TokenType::Whitespace) || ($token->type === TokenType::Comment)) {
                continue;
            }

            if (($token->type === TokenType::Keyword) && ($token->flags & Token::FLAG_KEYWORD_RESERVED)) {
                break;
            }

            if ($token->type === TokenType::Keyword && $token->keyword === 'OFFSET') {
                if ($offset) {
                    $parser->error('An offset was expected.', $token);
                }

                $offset = true;
                continue;
            }

            if (($token->type === TokenType::Operator) && ($token->value === ',')) {
                $ret->offset = $ret->rowCount;
                $ret->rowCount = 0;
                continue;
            }

            // Skip if not a number or a bind parameter (?)
            if (
                ! ($token->type === TokenType::Number
                    || ($token->type === TokenType::Symbol && ($token->flags & Token::FLAG_SYMBOL_PARAMETER)))
            ) {
                break;
            }

            if ($offset) {
                $ret->offset = $token->value;
                $offset = false;
            } else {
                $ret->rowCount = $token->value;
            }
        }

        if ($offset) {
            $parser->error('An offset was expected.', $list->tokens[$list->idx - 1]);
        }

        --$list->idx;

        return $ret;
    }
}
