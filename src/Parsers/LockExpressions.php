<?php

declare(strict_types=1);

namespace PhpMyAdmin\SqlParser\Parsers;

use PhpMyAdmin\SqlParser\Components\LockExpression;
use PhpMyAdmin\SqlParser\Parseable;
use PhpMyAdmin\SqlParser\Parser;
use PhpMyAdmin\SqlParser\TokensList;
use PhpMyAdmin\SqlParser\TokenType;

/**
 * Parses a reference to a LOCK expression.
 */
final class LockExpressions implements Parseable
{
    /**
     * @param Parser               $parser  the parser that serves as context
     * @param TokensList           $list    the list of tokens that are being parsed
     * @param array<string, mixed> $options parameters for parsing
     */
    public static function parse(Parser $parser, TokensList $list, array $options = []): LockExpression
    {
        $ret = new LockExpression();

        /**
         * The state of the parser.
         *
         * Below are the states of the parser.
         *
         *      0 ---------------- [ tbl_name ] -----------------> 1
         *      1 ---------------- [ lock_type ] ----------------> 2
         *      2 -------------------- [ , ] --------------------> break
         */
        $state = 0;

        $prevToken = null;

        for (; $list->idx < $list->count; ++$list->idx) {
            /**
             * Token parsed at this moment.
             */
            $token = $list->tokens[$list->idx];

            // End of statement.
            if (
                $token->type === TokenType::Delimiter
                || ($token->type === TokenType::Operator
                && $token->value === ',')
            ) {
                break;
            }

            if ($state === 0) {
                $ret->table = Expressions::parse($parser, $list, ['parseField' => 'table']);
                $state = 1;
            } elseif ($state === 1) {
                // parse lock type
                $ret->type = self::parseLockType($parser, $list);
                $state = 2;
            }

            $prevToken = $token;
        }

        // 2 is the only valid end state
        if ($state !== 2) {
            $parser->error('Unexpected end of LOCK expression.', $prevToken);
        }

        --$list->idx;

        return $ret;
    }

    private static function parseLockType(Parser $parser, TokensList $list): string
    {
        $lockType = '';

        /**
         * The state of the parser while parsing for lock type.
         *
         * Below are the states of the parser.
         *
         *      0 ---------------- [ READ ] -----------------> 1
         *      0 ------------- [ LOW_PRIORITY ] ------------> 2
         *      0 ---------------- [ WRITE ] ----------------> 3
         *      1 ---------------- [ LOCAL ] ----------------> 3
         *      2 ---------------- [ WRITE ] ----------------> 3
         */
        $state = 0;

        $prevToken = null;

        for (; $list->idx < $list->count; ++$list->idx) {
            /**
             * Token parsed at this moment.
             */
            $token = $list->tokens[$list->idx];

            // End of statement.
            if (
                $token->type === TokenType::Delimiter
                || ($token->type === TokenType::Operator
                && $token->value === ',')
            ) {
                --$list->idx;
                break;
            }

            // Skipping whitespaces and comments.
            if ($token->type === TokenType::Whitespace || $token->type === TokenType::Comment) {
                continue;
            }

            // We only expect keywords
            if ($token->type !== TokenType::Keyword) {
                $parser->error('Unexpected token.', $token);
                break;
            }

            if ($state === 0) {
                if ($token->keyword === 'READ') {
                    $state = 1;
                } elseif ($token->keyword === 'LOW_PRIORITY') {
                    $state = 2;
                } elseif ($token->keyword === 'WRITE') {
                    $state = 3;
                } else {
                    $parser->error('Unexpected keyword.', $token);
                    break;
                }

                $lockType .= $token->keyword;
            } elseif ($state === 1) {
                if ($token->keyword !== 'LOCAL') {
                    $parser->error('Unexpected keyword.', $token);
                    break;
                }

                $lockType .= ' ' . $token->keyword;
                $state = 3;
            } elseif ($state === 2) {
                if ($token->keyword !== 'WRITE') {
                    $parser->error('Unexpected keyword.', $token);
                    break;
                }

                $lockType .= ' ' . $token->keyword;
                $state = 3; // parsing over
            }

            $prevToken = $token;
        }

        // Only  two possible end states
        if ($state !== 1 && $state !== 3) {
            $parser->error('Unexpected end of LOCK expression.', $prevToken);
        }

        return $lockType;
    }
}
