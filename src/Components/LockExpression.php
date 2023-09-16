<?php

declare(strict_types=1);

namespace PhpMyAdmin\SqlParser\Components;

use PhpMyAdmin\SqlParser\Component;
use PhpMyAdmin\SqlParser\Parser;
use PhpMyAdmin\SqlParser\Token;
use PhpMyAdmin\SqlParser\TokensList;

use function implode;

/**
 * Parses a reference to a LOCK expression.
 */
final class LockExpression implements Component
{
    /**
     * The table to be locked.
     *
     * @var Expression
     */
    public $table;

    /**
     * The type of lock to be applied.
     *
     * @var string
     */
    public $type;

    /**
     * @param Parser               $parser  the parser that serves as context
     * @param TokensList           $list    the list of tokens that are being parsed
     * @param array<string, mixed> $options parameters for parsing
     */
    public static function parse(Parser $parser, TokensList $list, array $options = []): LockExpression
    {
        $ret = new static();

        /**
         * The state of the parser.
         *
         * Below are the states of the parser.
         *
         *      0 ---------------- [ tbl_name ] -----------------> 1
         *      1 ---------------- [ lock_type ] ----------------> 2
         *      2 -------------------- [ , ] --------------------> break
         *
         * @var int
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
                $token->type === Token::TYPE_DELIMITER
                || ($token->type === Token::TYPE_OPERATOR
                && $token->value === ',')
            ) {
                break;
            }

            if ($state === 0) {
                $ret->table = Expression::parse($parser, $list, ['parseField' => 'table']);
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

    public function build(): string
    {
        return $this->table . ' ' . $this->type;
    }

    /**
     * @param LockExpression[] $component the component to be built
     */
    public static function buildAll(array $component): string
    {
        return implode(', ', $component);
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
         *
         * @var int
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
                $token->type === Token::TYPE_DELIMITER
                || ($token->type === Token::TYPE_OPERATOR
                && $token->value === ',')
            ) {
                --$list->idx;
                break;
            }

            // Skipping whitespaces and comments.
            if ($token->type === Token::TYPE_WHITESPACE || $token->type === Token::TYPE_COMMENT) {
                continue;
            }

            // We only expect keywords
            if ($token->type !== Token::TYPE_KEYWORD) {
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

    public function __toString(): string
    {
        return $this->build();
    }
}
