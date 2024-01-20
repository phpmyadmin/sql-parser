<?php

declare(strict_types=1);

namespace PhpMyAdmin\SqlParser\Parsers;

use PhpMyAdmin\SqlParser\Components\ParameterDefinition;
use PhpMyAdmin\SqlParser\Parseable;
use PhpMyAdmin\SqlParser\Parser;
use PhpMyAdmin\SqlParser\TokensList;
use PhpMyAdmin\SqlParser\TokenType;

use function implode;

/**
 * The definition of a parameter of a function or procedure.
 */
final class ParameterDefinitions implements Parseable
{
    /**
     * @param Parser               $parser  the parser that serves as context
     * @param TokensList           $list    the list of tokens that are being parsed
     * @param array<string, mixed> $options parameters for parsing
     *
     * @return ParameterDefinition[]
     */
    public static function parse(Parser $parser, TokensList $list, array $options = []): array
    {
        $ret = [];

        $expr = new ParameterDefinition();

        /**
         * The state of the parser.
         *
         * Below are the states of the parser.
         *
         *      0 -----------------------[ ( ]------------------------> 1
         *
         *      1 ----------------[ IN / OUT / INOUT ]----------------> 1
         *      1 ----------------------[ name ]----------------------> 2
         *
         *      2 -------------------[ data type ]--------------------> 3
         *
         *      3 ------------------------[ , ]-----------------------> 1
         *      3 ------------------------[ ) ]-----------------------> (END)
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
                if (($token->type === TokenType::Operator) && ($token->value === '(')) {
                    $state = 1;
                }
            } elseif ($state === 1) {
                if (($token->value === 'IN') || ($token->value === 'OUT') || ($token->value === 'INOUT')) {
                    $expr->inOut = $token->value;
                    ++$list->idx;
                } elseif ($token->value === ')') {
                    ++$list->idx;
                    break;
                } else {
                    $expr->name = $token->value;
                    $state = 2;
                }
            } elseif ($state === 2) {
                $expr->type = DataTypes::parse($parser, $list);
                $state = 3;
            } else {
                $ret[] = $expr;
                $expr = new ParameterDefinition();
                if ($token->value === ',') {
                    $state = 1;
                } elseif ($token->value === ')') {
                    ++$list->idx;
                    break;
                }
            }
        }

        // Last iteration was not saved.
        if (isset($expr->name) && ($expr->name !== '')) {
            $ret[] = $expr;
        }

        --$list->idx;

        return $ret;
    }

    /** @param ParameterDefinition[] $component the component to be built */
    public static function buildAll(array $component): string
    {
        return '(' . implode(', ', $component) . ')';
    }
}
