<?php

declare(strict_types=1);

namespace PhpMyAdmin\SqlParser\Parsers;

use PhpMyAdmin\SqlParser\Components\GroupKeyword;
use PhpMyAdmin\SqlParser\Parseable;
use PhpMyAdmin\SqlParser\Parser;
use PhpMyAdmin\SqlParser\TokensList;
use PhpMyAdmin\SqlParser\TokenType;

use function implode;

/**
 * `GROUP BY` keyword parser.
 */
final class GroupKeywords implements Parseable
{
    /**
     * @param Parser               $parser  the parser that serves as context
     * @param TokensList           $list    the list of tokens that are being parsed
     * @param array<string, mixed> $options parameters for parsing
     *
     * @return GroupKeyword[]
     */
    public static function parse(Parser $parser, TokensList $list, array $options = []): array
    {
        $ret = [];

        $expr = new GroupKeyword();

        /**
         * The state of the parser.
         *
         * Below are the states of the parser.
         *
         *      0 --------------------[ expression ]-------------------> 1
         *
         *      1 ------------------------[ , ]------------------------> 0
         *      1 -------------------[ ASC / DESC ]--------------------> 1
         */
        $state = 0;

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

            if ($state === 0) {
                $expr->expr = Expressions::parse($parser, $list);
                $state = 1;
            } else {
                if (
                    ($token->type === TokenType::Keyword)
                    && (($token->keyword === 'ASC') || ($token->keyword === 'DESC'))
                ) {
                    $expr->type = $token->keyword;
                } elseif (($token->type === TokenType::Operator) && ($token->value === ',')) {
                    if (! empty($expr->expr)) {
                        $ret[] = $expr;
                    }

                    $expr = new GroupKeyword();
                    $state = 0;
                } else {
                    break;
                }
            }
        }

        // Last iteration was not processed.
        if (! empty($expr->expr)) {
            $ret[] = $expr;
        }

        --$list->idx;

        return $ret;
    }

    /** @param GroupKeyword[] $component the component to be built */
    public static function buildAll(array $component): string
    {
        return implode(', ', $component);
    }
}
