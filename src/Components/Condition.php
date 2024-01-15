<?php

declare(strict_types=1);

namespace PhpMyAdmin\SqlParser\Components;

use PhpMyAdmin\SqlParser\Component;
use PhpMyAdmin\SqlParser\Parser;
use PhpMyAdmin\SqlParser\Token;
use PhpMyAdmin\SqlParser\TokensList;
use PhpMyAdmin\SqlParser\TokenType;

use function implode;
use function in_array;
use function trim;

/**
 * `WHERE` keyword parser.
 */
final class Condition implements Component
{
    /**
     * Logical operators that can be used to delimit expressions.
     */
    private const DELIMITERS = [
        '&&',
        '||',
        'AND',
        'OR',
        'XOR',
    ];

    /**
     * List of allowed reserved keywords in conditions.
     */
    private const ALLOWED_KEYWORDS = [
        'ALL' => 1,
        'AND' => 1,
        'BETWEEN' => 1,
        'COLLATE' => 1,
        'EXISTS' => 1,
        'IF' => 1,
        'IN' => 1,
        'INTERVAL' => 1,
        'IS' => 1,
        'LIKE' => 1,
        'MATCH' => 1,
        'NOT IN' => 1,
        'NOT NULL' => 1,
        'NOT' => 1,
        'NULL' => 1,
        'OR' => 1,
        'REGEXP' => 1,
        'RLIKE' => 1,
        'SOUNDS' => 1,
        'XOR' => 1,
    ];

    /**
     * Identifiers recognized.
     *
     * @var array<int, mixed>
     */
    public $identifiers = [];

    /**
     * Whether this component is an operator.
     *
     * @var bool
     */
    public $isOperator = false;

    /**
     * The condition.
     *
     * @var string
     */
    public $expr;

    /** @param string $expr the condition or the operator */
    public function __construct(string|null $expr = null)
    {
        $this->expr = trim((string) $expr);
    }

    /**
     * @param Parser               $parser  the parser that serves as context
     * @param TokensList           $list    the list of tokens that are being parsed
     * @param array<string, mixed> $options parameters for parsing
     *
     * @return Condition[]
     */
    public static function parse(Parser $parser, TokensList $list, array $options = []): array
    {
        $ret = [];

        $expr = new static();

        /**
         * Counts brackets.
         *
         * @var int
         */
        $brackets = 0;

        /**
         * Whether there was a `BETWEEN` keyword before or not.
         *
         * It is required to keep track of them because their structure contains
         * the keyword `AND`, which is also an operator that delimits
         * expressions.
         *
         * @var bool
         */
        $betweenBefore = false;

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
            if ($token->type === TokenType::Comment) {
                continue;
            }

            // Replacing all whitespaces (new lines, tabs, etc.) with a single
            // space character.
            if ($token->type === TokenType::Whitespace) {
                $expr->expr .= ' ';
                continue;
            }

            // Conditions are delimited by logical operators.
            if (in_array($token->value, self::DELIMITERS, true)) {
                if ($betweenBefore && ($token->value === 'AND')) {
                    // The syntax of keyword `BETWEEN` is hard-coded.
                    $betweenBefore = false;
                } else {
                    // The expression ended.
                    $expr->expr = trim($expr->expr);
                    if (! empty($expr->expr)) {
                        $ret[] = $expr;
                    }

                    // Adding the operator.
                    $expr = new static($token->value);
                    $expr->isOperator = true;
                    $ret[] = $expr;

                    // Preparing to parse another condition.
                    $expr = new static();
                    continue;
                }
            }

            if (
                ($token->type === TokenType::Keyword)
                && ($token->flags & Token::FLAG_KEYWORD_RESERVED)
                && ! ($token->flags & Token::FLAG_KEYWORD_FUNCTION)
            ) {
                if ($token->value === 'BETWEEN') {
                    $betweenBefore = true;
                }

                if (($brackets === 0) && empty(self::ALLOWED_KEYWORDS[$token->value])) {
                    break;
                }
            }

            if ($token->type === TokenType::Operator) {
                if ($token->value === '(') {
                    ++$brackets;
                } elseif ($token->value === ')') {
                    if ($brackets === 0) {
                        break;
                    }

                    --$brackets;
                }
            }

            $expr->expr .= $token->token;
            if (
                ($token->type !== TokenType::None)
                && (($token->type !== TokenType::Keyword)
                || ($token->flags & Token::FLAG_KEYWORD_RESERVED))
                && ($token->type !== TokenType::String)
                && ($token->type !== TokenType::Symbol || ($token->flags & Token::FLAG_SYMBOL_PARAMETER))
            ) {
                continue;
            }

            if (in_array($token->value, $expr->identifiers)) {
                continue;
            }

            $expr->identifiers[] = $token->value;
        }

        // Last iteration was not processed.
        $expr->expr = trim($expr->expr);
        if (! empty($expr->expr)) {
            $ret[] = $expr;
        }

        --$list->idx;

        return $ret;
    }

    public function build(): string
    {
        return $this->expr;
    }

    /** @param Condition[] $component the component to be built */
    public static function buildAll(array $component): string
    {
        return implode(' ', $component);
    }

    public function __toString(): string
    {
        return $this->build();
    }
}
