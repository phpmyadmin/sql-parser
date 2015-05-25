<?php

namespace SqlParser\Fragments;

use SqlParser\Fragment;
use SqlParser\Lexer;
use SqlParser\Parser;
use SqlParser\Token;
use SqlParser\TokensList;

/**
 * `SELECT` keyword parser.
 */
class SelectKeyword extends Fragment
{

    /**
     * @param Parser $parser
     * @param TokensList $list
     * @param array $options
     *
     * @return FieldFragment[]
     */
    public static function parse(Parser $parser, TokensList $list, array $options = array())
    {
        $ret = array();

        $expr = null;

        /** @var bool Whether an alias is expected. */
        $alias = false;

        for (; $list->idx < $list->count; ++$list->idx) {
            /** @var Token Token parsed at this moment. */
            $token = $list->tokens[$list->idx];

            // End of statement.
            if ($token->type === Token::TYPE_DELIMITER) {
                break;
            }

            // Skipping whitespaces and comments.
            if (($token->type === Token::TYPE_WHITESPACE) || ($token->type === Token::TYPE_COMMENT)) {
                continue;
            }

            if ($token->type === Token::TYPE_KEYWORD) {
                // No keyword is expected other than `AS`.
                if ($token->value !== 'AS') {
                    break;
                }
            }

            if (($token->type === Token::TYPE_OPERATOR) && ($token->value === ',')) {
                $ret[] = $expr;
            } elseif (($token->type === Token::TYPE_KEYWORD) && ($token->value === 'AS')) {
                $alias = true;
            } else {
                if ($alias) {
                    $expr->alias = $token->value;
                    $alias = false;
                } else {
                    $expr = FieldFragment::parse($parser, $list);
                    if ($expr === null) {
                        break;
                    }
                }
            }

        }

        // Last iteration was not processed.
        if (!empty($expr->tokens)) {
            if ($alias) {
                $parser->error('Alias was expected.', $token);
            }
            $ret[] = $expr;
        }

        --$list->idx;
        return $ret;
    }
}
