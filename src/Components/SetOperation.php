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
 * `SET` keyword parser.
 */
final class SetOperation implements Component
{
    /**
     * The name of the column that is being updated.
     *
     * @var string
     */
    public $column;

    /**
     * The new value.
     *
     * @var string
     */
    public $value;

    /**
     * @param string $column Field's name..
     * @param string $value  new value
     */
    public function __construct($column = '', $value = '')
    {
        $this->column = $column;
        $this->value = $value;
    }

    /**
     * @param Parser               $parser  the parser that serves as context
     * @param TokensList           $list    the list of tokens that are being parsed
     * @param array<string, mixed> $options parameters for parsing
     *
     * @return SetOperation[]
     */
    public static function parse(Parser $parser, TokensList $list, array $options = []): array
    {
        $ret = [];

        $expr = new static();

        /**
         * The state of the parser.
         *
         * Below are the states of the parser.
         *
         *      0 ---------------------[ col_name ]--------------------> 0
         *      0 ---------------------[ = or := ]---------------------> 1
         *      1 -----------------------[ value ]---------------------> 1
         *      1 ------------------------[ , ]------------------------> 0
         *
         * @var int
         */
        $state = 0;

        /**
         * Token when the parser has seen the latest comma
         *
         * @var Token
         */
        $commaLastSeenAt = null;

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

            // No keyword is expected.
            if (
                ($token->type === TokenType::Keyword)
                && ($token->flags & Token::FLAG_KEYWORD_RESERVED)
                && ($state === 0)
            ) {
                break;
            }

            if ($state === 0) {
                if (in_array($token->token, ['=', ':='], true)) {
                    $state = 1;
                } elseif ($token->value !== ',') {
                    $expr->column .= $token->token;
                } elseif ($token->value === ',') {
                    $commaLastSeenAt = $token;
                }
            } elseif ($state === 1) {
                $tmp = Expression::parse(
                    $parser,
                    $list,
                    ['breakOnAlias' => true]
                );
                if ($tmp === null) {
                    $parser->error('Missing expression.', $token);
                    break;
                }

                $expr->column = trim($expr->column);
                $expr->value = $tmp->expr;
                $ret[] = $expr;
                $expr = new static();
                $state = 0;
                $commaLastSeenAt = null;
            }
        }

        --$list->idx;

        // We saw a comma, but didn't see a column-value pair after it
        if ($commaLastSeenAt !== null) {
            $parser->error('Unexpected token.', $commaLastSeenAt);
        }

        return $ret;
    }

    public function build(): string
    {
        return $this->column . ' = ' . $this->value;
    }

    /**
     * @param SetOperation[] $component the component to be built
     */
    public static function buildAll(array $component): string
    {
        return implode(', ', $component);
    }

    public function __toString(): string
    {
        return $this->build();
    }
}
