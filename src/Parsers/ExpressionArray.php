<?php

declare(strict_types=1);

namespace PhpMyAdmin\SqlParser\Parsers;

use PhpMyAdmin\SqlParser\Components\Expression;
use PhpMyAdmin\SqlParser\Exceptions\ParserException;
use PhpMyAdmin\SqlParser\Parseable;
use PhpMyAdmin\SqlParser\Parser;
use PhpMyAdmin\SqlParser\Token;
use PhpMyAdmin\SqlParser\TokensList;
use PhpMyAdmin\SqlParser\TokenType;

use function count;
use function implode;
use function preg_match;
use function strlen;
use function substr;

/**
 * Parses a list of expressions delimited by a comma.
 */
final class ExpressionArray implements Parseable
{
    /**
     * @param Parser               $parser  the parser that serves as context
     * @param TokensList           $list    the list of tokens that are being parsed
     * @param array<string, mixed> $options parameters for parsing
     *
     * @return Expression[]
     *
     * @throws ParserException
     */
    public static function parse(Parser $parser, TokensList $list, array $options = []): array
    {
        $ret = [];

        /**
         * The state of the parser.
         *
         * Below are the states of the parser.
         *
         *      0 ----------------------[ array ]---------------------> 1
         *
         *      1 ------------------------[ , ]------------------------> 0
         *      1 -----------------------[ else ]----------------------> (END)
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

            if (
                ($token->type === TokenType::Keyword)
                && ($token->flags & Token::FLAG_KEYWORD_RESERVED)
                && ((~$token->flags & Token::FLAG_KEYWORD_FUNCTION))
                && ($token->value !== 'DUAL')
                && ($token->value !== 'NULL')
                && ($token->value !== 'CASE')
                && ($token->value !== 'NOT')
            ) {
                // No keyword is expected.
                break;
            }

            if ($state === 0) {
                if ($token->type === TokenType::Keyword && $token->value === 'CASE') {
                    $expr = CaseExpressions::parse($parser, $list, $options);
                } else {
                    $expr = Expressions::parse($parser, $list, $options);
                }

                if ($expr === null) {
                    break;
                }

                $ret[] = $expr;
                $state = 1;
            } elseif ($state === 1) {
                if ($token->value !== ',') {
                    break;
                }

                $state = 0;
            }
        }

        if ($state === 0) {
            $parser->error('An expression was expected.', $list->tokens[$list->idx]);
        }

        --$list->idx;
        $retIndex = count($ret) - 1;
        if (isset($ret[$retIndex])) {
            $expr = $ret[$retIndex]->expr;
            if (preg_match('/\s*--\s.*$/', $expr, $matches)) {
                $found = $matches[0];
                $ret[$retIndex]->expr = substr($expr, 0, strlen($expr) - strlen($found));
            }
        }

        return $ret;
    }

    /** @param Expression[] $component the component to be built */
    public static function buildAll(array $component): string
    {
        return implode(', ', $component);
    }
}
