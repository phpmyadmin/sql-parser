<?php

declare(strict_types=1);

namespace PhpMyAdmin\SqlParser\Parsers;

use AllowDynamicProperties;
use PhpMyAdmin\SqlParser\Components\Expression;
use PhpMyAdmin\SqlParser\Exceptions\ParserException;
use PhpMyAdmin\SqlParser\Parseable;
use PhpMyAdmin\SqlParser\Parser;
use PhpMyAdmin\SqlParser\Token;
use PhpMyAdmin\SqlParser\TokensList;
use PhpMyAdmin\SqlParser\TokenType;

use function implode;
use function in_array;
use function rtrim;
use function strlen;
use function trim;

/**
 * Parses a reference to an expression (column, table or database name, function
 * call, mathematical expression, etc.).
 */
#[AllowDynamicProperties]
final class Expressions implements Parseable
{
    /**
     * List of allowed reserved keywords in expressions.
     */
    private const ALLOWED_KEYWORDS = [
        'AND',
        'AS',
        'BETWEEN',
        'CASE',
        'DUAL',
        'DIV',
        'IS',
        'MOD',
        'NOT',
        'NOT NULL',
        'NULL',
        'OR',
        'OVER',
        'REGEXP',
        'RLIKE',
        'XOR',
    ];

    /**
     * Possible options:.
     *
     *      `field`
     *
     *          First field to be filled.
     *          If this is not specified, it takes the value of `parseField`.
     *
     *      `parseField`
     *
     *          Specifies the type of the field parsed. It may be `database`,
     *          `table` or `column`. These expressions may not include
     *          parentheses.
     *
     *      `breakOnAlias`
     *
     *          If not empty, breaks when the alias occurs (it is not included).
     *
     *      `breakOnParentheses`
     *
     *          If not empty, breaks when the first parentheses occurs.
     *
     *      `parenthesesDelimited`
     *
     *          If not empty, breaks after last parentheses occurred.
     *
     * @param Parser               $parser  the parser that serves as context
     * @param TokensList           $list    the list of tokens that are being parsed
     * @param array<string, mixed> $options parameters for parsing
     *
     * @throws ParserException
     */
    public static function parse(Parser $parser, TokensList $list, array $options = []): Expression|null
    {
        $ret = new Expression();

        /**
         * Whether current tokens make an expression or a table reference.
         */
        $isExpr = false;

        /**
         * Whether a period was previously found.
         */
        $dot = false;

        /**
         * Whether an alias is expected. Is 2 if `AS` keyword was found.
         */
        $alias = false;

        /**
         * Counts brackets.
         */
        $brackets = 0;

        /**
         * Keeps track of the last two previous tokens.
         */
        $prev = [
            null,
            null,
        ];

        // When a field is parsed, no parentheses are expected.
        if (! empty($options['parseField'])) {
            $options['breakOnParentheses'] = true;
            $options['field'] = $options['parseField'];
        }

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
                // If the token is a closing C comment from a MySQL command, it must be ignored.
                if ($isExpr && $token->token !== '*/') {
                    $ret->expr .= $token->token;
                }

                continue;
            }

            if ($token->type === TokenType::Keyword) {
                if (($brackets > 0) && empty($ret->subquery) && ! empty(Parser::STATEMENT_PARSERS[$token->keyword])) {
                    // A `(` was previously found and this keyword is the
                    // beginning of a statement, so this is a subquery.
                    $ret->subquery = $token->keyword;
                } elseif (
                    ($token->flags & Token::FLAG_KEYWORD_FUNCTION)
                    && (empty($options['parseField'])
                    && ! $alias)
                ) {
                    $isExpr = true;
                } elseif (($token->flags & Token::FLAG_KEYWORD_RESERVED) && ($brackets === 0)) {
                    if (! in_array($token->keyword, self::ALLOWED_KEYWORDS, true)) {
                        // A reserved keyword that is not allowed in the
                        // expression was found so the expression must have
                        // ended and a new clause is starting.
                        break;
                    }

                    if ($token->keyword === 'AS') {
                        if (! empty($options['breakOnAlias'])) {
                            break;
                        }

                        if ($alias) {
                            $parser->error('An alias was expected.', $token);
                            break;
                        }

                        $alias = true;
                        continue;
                    }

                    if ($token->keyword === 'CASE') {
                        // For a use of CASE like
                        // 'SELECT a = CASE .... END, b=1, `id`, ... FROM ...'
                        $tempCaseExpr = CaseExpressions::parse($parser, $list);
                        $ret->expr .= $tempCaseExpr->build();
                        $isExpr = true;
                        continue;
                    }

                    $isExpr = true;
                } elseif (
                    $brackets === 0 && strlen((string) $ret->expr) > 0 && ! $alias
                    && ($ret->table === null || $ret->table === '')
                ) {
                    /* End of expression */
                    break;
                }
            }

            if (
                ($token->type === TokenType::Number)
                || ($token->type === TokenType::Bool)
                || (($token->type === TokenType::Symbol)
                && ($token->flags & Token::FLAG_SYMBOL_VARIABLE))
                || (($token->type === TokenType::Symbol)
                && ($token->flags & Token::FLAG_SYMBOL_PARAMETER))
                || (($token->type === TokenType::Operator)
                && ($token->value !== '.'))
            ) {
                if (! empty($options['parseField'])) {
                    break;
                }

                // Numbers, booleans and operators (except dot) are usually part
                // of expressions.
                $isExpr = true;
            }

            if ($token->type === TokenType::Operator) {
                if (! empty($options['breakOnParentheses']) && (($token->value === '(') || ($token->value === ')'))) {
                    // No brackets were expected.
                    break;
                }

                if ($token->value === '(') {
                    ++$brackets;
                    if (
                        empty($ret->function) && ($prev[1] !== null)
                        && (($prev[1]->type === TokenType::None)
                        || ($prev[1]->type === TokenType::Symbol)
                        || (($prev[1]->type === TokenType::Keyword)
                        && ($prev[1]->flags & Token::FLAG_KEYWORD_FUNCTION)))
                    ) {
                        $ret->function = $prev[1]->value;
                    }
                } elseif ($token->value === ')') {
                    if ($brackets === 0) {
                        // Not our bracket
                        break;
                    }

                    --$brackets;
                    if ($brackets === 0) {
                        if (! empty($options['parenthesesDelimited'])) {
                            // The current token is the last bracket, the next
                            // one will be outside the expression.
                            $ret->expr .= $token->token;
                            ++$list->idx;
                            break;
                        }
                    } elseif ($brackets < 0) {
                        // $parser->error('Unexpected closing bracket.', $token);
                        // $brackets = 0;
                        break;
                    }
                } elseif ($token->value === ',') {
                    // Expressions are comma-delimited.
                    if ($brackets === 0) {
                        break;
                    }
                }
            }

            // Saving the previous tokens.
            $prev[0] = $prev[1];
            $prev[1] = $token;

            if ($alias) {
                // An alias is expected (the keyword `AS` was previously found).
                if (! empty($ret->alias)) {
                    $parser->error('An alias was previously found.', $token);
                    break;
                }

                $ret->alias = $token->value;
                $alias = false;
            } elseif ($isExpr) {
                // Handling aliases.
                if (
                    $brackets === 0
                    && ($prev[0] === null
                        || (($prev[0]->type !== TokenType::Operator || $prev[0]->token === ')')
                            && ($prev[0]->type !== TokenType::Keyword
                                || ! ($prev[0]->flags & Token::FLAG_KEYWORD_RESERVED))))
                    && (($prev[1]->type === TokenType::String)
                        || ($prev[1]->type === TokenType::Symbol
                            && ! ($prev[1]->flags & Token::FLAG_SYMBOL_VARIABLE)
                            && ! ($prev[1]->flags & Token::FLAG_SYMBOL_PARAMETER))
                        || ($prev[1]->type === TokenType::None
                            && $prev[1]->token !== 'OVER'))
                ) {
                    if (! empty($ret->alias)) {
                        $parser->error('An alias was previously found.', $token);
                        break;
                    }

                    $ret->alias = $prev[1]->value;
                } else {
                    $currIdx = $list->idx;
                    --$list->idx;
                    $beforeToken = $list->getPrevious();
                    $list->idx = $currIdx;
                    // columns names tokens are of type NONE, or SYMBOL (`col`), and the columns options
                    // would start with a token of type KEYWORD, in that case, we want to have a space
                    // between the tokens.
                    if (
                        $ret->expr !== null &&
                        $beforeToken &&
                        ($beforeToken->type === TokenType::None ||
                        $beforeToken->type === TokenType::Symbol || $beforeToken->type === TokenType::String) &&
                        $token->type === TokenType::Keyword
                    ) {
                        $ret->expr = rtrim($ret->expr, ' ') . ' ';
                    }

                    $ret->expr .= $token->token;
                }
            } else {
                if (($token->type === TokenType::Operator) && ($token->value === '.')) {
                    // Found a `.` which means we expect a column name and
                    // the column name we parsed is actually the table name
                    // and the table name is actually a database name.
                    if (! empty($ret->database) || $dot) {
                        $parser->error('Unexpected dot.', $token);
                    }

                    $ret->database = $ret->table;
                    $ret->table = $ret->column;
                    $ret->column = null;
                    $dot = true;
                    $ret->expr .= $token->token;
                } else {
                    $field = empty($options['field']) ? 'column' : $options['field'];
                    if (empty($ret->$field)) {
                        $ret->$field = $token->value;
                        $ret->expr .= $token->token;
                        $dot = false;
                    } else {
                        // No alias is expected.
                        if (! empty($options['breakOnAlias'])) {
                            break;
                        }

                        if (! empty($ret->alias)) {
                            $parser->error('An alias was previously found.', $token);
                            break;
                        }

                        $ret->alias = $token->value;
                    }
                }
            }
        }

        if ($alias) {
            $parser->error('An alias was expected.', $list->tokens[$list->idx - 1]);
        }

        // White-spaces might be added at the end.
        $ret->expr = trim((string) $ret->expr);

        if ($ret->expr === '') {
            return null;
        }

        --$list->idx;

        return $ret;
    }

    /** @param Expression[] $component the component to be built */
    public static function buildAll(array $component): string
    {
        return implode(', ', $component);
    }
}
