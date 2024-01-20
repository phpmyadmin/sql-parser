<?php

declare(strict_types=1);

namespace PhpMyAdmin\SqlParser\Parsers;

use PhpMyAdmin\SqlParser\Components\CaseExpression;
use PhpMyAdmin\SqlParser\Parseable;
use PhpMyAdmin\SqlParser\Parser;
use PhpMyAdmin\SqlParser\Token;
use PhpMyAdmin\SqlParser\TokensList;
use PhpMyAdmin\SqlParser\TokenType;

/**
 * Parses a reference to a CASE expression.
 */
final class CaseExpressions implements Parseable
{
    /**
     * @param Parser               $parser  the parser that serves as context
     * @param TokensList           $list    the list of tokens that are being parsed
     * @param array<string, mixed> $options parameters for parsing
     */
    public static function parse(Parser $parser, TokensList $list, array $options = []): CaseExpression
    {
        $ret = new CaseExpression();

        /**
         * State of parser.
         */
        $state = 0;

        /**
         * Syntax type (type 0 or type 1).
         */
        $type = 0;

        ++$list->idx; // Skip 'CASE'

        for (; $list->idx < $list->count; ++$list->idx) {
            /**
             * Token parsed at this moment.
             */
            $token = $list->tokens[$list->idx];

            // Skipping whitespaces and comments.
            if (($token->type === TokenType::Whitespace) || ($token->type === TokenType::Comment)) {
                continue;
            }

            if ($state === 0) {
                if ($token->type === TokenType::Keyword) {
                    switch ($token->keyword) {
                        case 'WHEN':
                            ++$list->idx; // Skip 'WHEN'
                            $newCondition = Conditions::parse($parser, $list);
                            $type = 1;
                            $state = 1;
                            $ret->conditions[] = $newCondition;
                            break;
                        case 'ELSE':
                            ++$list->idx; // Skip 'ELSE'
                            $ret->elseResult = Expressions::parse($parser, $list);
                            $state = 0; // last clause of CASE expression
                            break;
                        case 'END':
                            $state = 3; // end of CASE expression
                            ++$list->idx;
                            break 2;
                        default:
                            $parser->error('Unexpected keyword.', $token);
                            break 2;
                    }
                } else {
                    $ret->value = Expressions::parse($parser, $list);
                    $type = 0;
                    $state = 1;
                }
            } elseif ($state === 1) {
                if ($type === 0) {
                    if ($token->type === TokenType::Keyword) {
                        switch ($token->keyword) {
                            case 'WHEN':
                                ++$list->idx; // Skip 'WHEN'
                                $newValue = Expressions::parse($parser, $list);
                                $state = 2;
                                $ret->compareValues[] = $newValue;
                                break;
                            case 'ELSE':
                                ++$list->idx; // Skip 'ELSE'
                                $ret->elseResult = Expressions::parse($parser, $list);
                                $state = 0; // last clause of CASE expression
                                break;
                            case 'END':
                                $state = 3; // end of CASE expression
                                ++$list->idx;
                                break 2;
                            default:
                                $parser->error('Unexpected keyword.', $token);
                                break 2;
                        }
                    }
                } elseif ($token->type === TokenType::Keyword && $token->keyword === 'THEN') {
                    ++$list->idx; // Skip 'THEN'
                    $newResult = Expressions::parse($parser, $list);
                    $state = 0;
                    $ret->results[] = $newResult;
                } elseif ($token->type === TokenType::Keyword) {
                    $parser->error('Unexpected keyword.', $token);
                    break;
                }
            } elseif ($state === 2) {
                if ($type === 0) {
                    if ($token->type === TokenType::Keyword && $token->keyword === 'THEN') {
                        ++$list->idx; // Skip 'THEN'
                        $newResult = Expressions::parse($parser, $list);
                        $ret->results[] = $newResult;
                        $state = 1;
                    } elseif ($token->type === TokenType::Keyword) {
                        $parser->error('Unexpected keyword.', $token);
                        break;
                    }
                }
            }
        }

        if ($state !== 3) {
            $parser->error('Unexpected end of CASE expression', $list->tokens[$list->idx - 1]);
        } else {
            // Parse for alias of CASE expression
            $asFound = false;
            for (; $list->idx < $list->count; ++$list->idx) {
                $token = $list->tokens[$list->idx];

                // End of statement.
                if ($token->type === TokenType::Delimiter) {
                    break;
                }

                // Skipping whitespaces and comments.
                if (($token->type === TokenType::Whitespace) || ($token->type === TokenType::Comment)) {
                    continue;
                }

                // Handle optional AS keyword before alias
                if ($token->type === TokenType::Keyword && $token->keyword === 'AS') {
                    if ($asFound || ! empty($ret->alias)) {
                        $parser->error('Potential duplicate alias of CASE expression.', $token);
                        break;
                    }

                    $asFound = true;
                    continue;
                }

                if (
                    $asFound
                    && $token->type === TokenType::Keyword
                    && ($token->flags & Token::FLAG_KEYWORD_RESERVED || $token->flags & Token::FLAG_KEYWORD_FUNCTION)
                ) {
                    $parser->error('An alias expected after AS but got ' . $token->value, $token);
                    $asFound = false;
                    break;
                }

                if (
                    $asFound
                    || $token->type === TokenType::String
                    || ($token->type === TokenType::Symbol && ! $token->flags & Token::FLAG_SYMBOL_VARIABLE)
                    || $token->type === TokenType::None
                ) {
                    // An alias is expected (the keyword `AS` was previously found).
                    if (! empty($ret->alias)) {
                        $parser->error('An alias was previously found.', $token);
                        break;
                    }

                    $ret->alias = $token->value;
                    $asFound = false;

                    continue;
                }

                break;
            }

            if ($asFound) {
                $parser->error('An alias was expected after AS.', $list->tokens[$list->idx - 1]);
            }

            $ret->expr = $ret->build();
        }

        --$list->idx;

        return $ret;
    }
}
