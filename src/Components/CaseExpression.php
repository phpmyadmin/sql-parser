<?php

declare(strict_types=1);

namespace PhpMyAdmin\SqlParser\Components;

use PhpMyAdmin\SqlParser\Component;
use PhpMyAdmin\SqlParser\Context;
use PhpMyAdmin\SqlParser\Parser;
use PhpMyAdmin\SqlParser\Token;
use PhpMyAdmin\SqlParser\TokensList;

use function count;

/**
 * Parses a reference to a CASE expression.
 */
final class CaseExpression implements Component
{
    /**
     * The value to be compared.
     *
     * @var Expression|null
     */
    public $value;

    /**
     * The conditions in WHEN clauses.
     *
     * @var Condition[][]
     */
    public $conditions = [];

    /**
     * The results matching with the WHEN clauses.
     *
     * @var Expression[]
     */
    public $results = [];

    /**
     * The values to be compared against.
     *
     * @var Expression[]
     */
    public $compareValues = [];

    /**
     * The result in ELSE section of expr.
     *
     * @var Expression|null
     */
    public $elseResult;

    /**
     * The alias of this CASE statement.
     *
     * @var string|null
     */
    public $alias;

    /**
     * The sub-expression.
     *
     * @var string
     */
    public $expr = '';

    /**
     * @param Parser               $parser  the parser that serves as context
     * @param TokensList           $list    the list of tokens that are being parsed
     * @param array<string, mixed> $options parameters for parsing
     */
    public static function parse(Parser $parser, TokensList $list, array $options = []): CaseExpression
    {
        $ret = new static();

        /**
         * State of parser.
         *
         * @var int
         */
        $state = 0;

        /**
         * Syntax type (type 0 or type 1).
         *
         * @var int
         */
        $type = 0;

        ++$list->idx; // Skip 'CASE'

        for (; $list->idx < $list->count; ++$list->idx) {
            /**
             * Token parsed at this moment.
             */
            $token = $list->tokens[$list->idx];

            // Skipping whitespaces and comments.
            if (($token->type === Token::TYPE_WHITESPACE) || ($token->type === Token::TYPE_COMMENT)) {
                continue;
            }

            if ($state === 0) {
                if ($token->type === Token::TYPE_KEYWORD) {
                    switch ($token->keyword) {
                        case 'WHEN':
                            ++$list->idx; // Skip 'WHEN'
                            $newCondition = Condition::parse($parser, $list);
                            $type = 1;
                            $state = 1;
                            $ret->conditions[] = $newCondition;
                            break;
                        case 'ELSE':
                            ++$list->idx; // Skip 'ELSE'
                            $ret->elseResult = Expression::parse($parser, $list);
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
                    $ret->value = Expression::parse($parser, $list);
                    $type = 0;
                    $state = 1;
                }
            } elseif ($state === 1) {
                if ($type === 0) {
                    if ($token->type === Token::TYPE_KEYWORD) {
                        switch ($token->keyword) {
                            case 'WHEN':
                                ++$list->idx; // Skip 'WHEN'
                                $newValue = Expression::parse($parser, $list);
                                $state = 2;
                                $ret->compareValues[] = $newValue;
                                break;
                            case 'ELSE':
                                ++$list->idx; // Skip 'ELSE'
                                $ret->elseResult = Expression::parse($parser, $list);
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
                } elseif ($token->type === Token::TYPE_KEYWORD && $token->keyword === 'THEN') {
                    ++$list->idx; // Skip 'THEN'
                    $newResult = Expression::parse($parser, $list);
                    $state = 0;
                    $ret->results[] = $newResult;
                } elseif ($token->type === Token::TYPE_KEYWORD) {
                    $parser->error('Unexpected keyword.', $token);
                    break;
                }
            } elseif ($state === 2) {
                if ($type === 0) {
                    if ($token->type === Token::TYPE_KEYWORD && $token->keyword === 'THEN') {
                        ++$list->idx; // Skip 'THEN'
                        $newResult = Expression::parse($parser, $list);
                        $ret->results[] = $newResult;
                        $state = 1;
                    } elseif ($token->type === Token::TYPE_KEYWORD) {
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
                if ($token->type === Token::TYPE_DELIMITER) {
                    break;
                }

                // Skipping whitespaces and comments.
                if (($token->type === Token::TYPE_WHITESPACE) || ($token->type === Token::TYPE_COMMENT)) {
                    continue;
                }

                // Handle optional AS keyword before alias
                if ($token->type === Token::TYPE_KEYWORD && $token->keyword === 'AS') {
                    if ($asFound || ! empty($ret->alias)) {
                        $parser->error('Potential duplicate alias of CASE expression.', $token);
                        break;
                    }

                    $asFound = true;
                    continue;
                }

                if (
                    $asFound
                    && $token->type === Token::TYPE_KEYWORD
                    && ($token->flags & Token::FLAG_KEYWORD_RESERVED || $token->flags & Token::FLAG_KEYWORD_FUNCTION)
                ) {
                    $parser->error('An alias expected after AS but got ' . $token->value, $token);
                    $asFound = false;
                    break;
                }

                if (
                    $asFound
                    || $token->type === Token::TYPE_STRING
                    || ($token->type === Token::TYPE_SYMBOL && ! $token->flags & Token::FLAG_SYMBOL_VARIABLE)
                    || $token->type === Token::TYPE_NONE
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

    public function build(): string
    {
        $ret = 'CASE ';
        if (isset($this->value)) {
            // Syntax type 0
            $ret .= $this->value . ' ';
            $valuesCount = count($this->compareValues);
            $resultsCount = count($this->results);
            for ($i = 0; $i < $valuesCount && $i < $resultsCount; ++$i) {
                $ret .= 'WHEN ' . $this->compareValues[$i] . ' ';
                $ret .= 'THEN ' . $this->results[$i] . ' ';
            }
        } else {
            // Syntax type 1
            $valuesCount = count($this->conditions);
            $resultsCount = count($this->results);
            for ($i = 0; $i < $valuesCount && $i < $resultsCount; ++$i) {
                $ret .= 'WHEN ' . Condition::buildAll($this->conditions[$i]) . ' ';
                $ret .= 'THEN ' . $this->results[$i] . ' ';
            }
        }

        if (isset($this->elseResult)) {
            $ret .= 'ELSE ' . $this->elseResult . ' ';
        }

        $ret .= 'END';

        if ($this->alias) {
            $ret .= ' AS ' . Context::escape($this->alias);
        }

        return $ret;
    }

    public function __toString(): string
    {
        return $this->build();
    }
}
