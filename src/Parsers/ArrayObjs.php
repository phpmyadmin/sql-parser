<?php

declare(strict_types=1);

namespace PhpMyAdmin\SqlParser\Parsers;

use PhpMyAdmin\SqlParser\Component;
use PhpMyAdmin\SqlParser\Components\ArrayObj;
use PhpMyAdmin\SqlParser\Parseable;
use PhpMyAdmin\SqlParser\Parser;
use PhpMyAdmin\SqlParser\TokensList;
use PhpMyAdmin\SqlParser\TokenType;

use function implode;
use function strlen;
use function trim;

/**
 * Parses an array.
 */
final class ArrayObjs implements Parseable
{
    /**
     * @param Parser               $parser  the parser that serves as context
     * @param TokensList           $list    the list of tokens that are being parsed
     * @param array<string, mixed> $options parameters for parsing
     *
     * @return ArrayObj|Component[]
     */
    public static function parse(Parser $parser, TokensList $list, array $options = []): ArrayObj|array
    {
        $ret = empty($options['type']) ? new ArrayObj() : [];

        /**
         * The last raw expression.
         */
        $lastRaw = '';

        /**
         * The last value.
         */
        $lastValue = '';

        /**
         * Counts brackets.
         */
        $brackets = 0;

        /**
         * Last separator (bracket or comma).
         */
        $isCommaLast = false;

        for (; $list->idx < $list->count; ++$list->idx) {
            /**
             * Token parsed at this moment.
             */
            $token = $list->tokens[$list->idx];

            // End of statement.
            if ($token->type === TokenType::Delimiter) {
                if ($brackets > 0) {
                    $parser->error('A closing bracket was expected.', $token);
                }

                break;
            }

            // Skipping whitespaces and comments.
            if (($token->type === TokenType::Whitespace) || ($token->type === TokenType::Comment)) {
                $lastRaw .= $token->token;
                $lastValue = trim($lastValue) . ' ';
                continue;
            }

            if (($brackets === 0) && (($token->type !== TokenType::Operator) || ($token->value !== '('))) {
                $parser->error('An opening bracket was expected.', $token);
                break;
            }

            if ($token->type === TokenType::Operator) {
                if ($token->value === '(') {
                    if (++$brackets === 1) { // 1 is the base level.
                        continue;
                    }
                } elseif ($token->value === ')') {
                    if (--$brackets === 0) { // Array ended.
                        break;
                    }
                } elseif ($token->value === ',') {
                    if ($brackets === 1) {
                        $isCommaLast = true;
                        if (empty($options['type'])) {
                            $ret->raw[] = trim($lastRaw);
                            $ret->values[] = trim($lastValue);
                            $lastRaw = $lastValue = '';
                        }

                        continue;
                    }
                }
            }

            if (empty($options['type'])) {
                $lastRaw .= $token->token;
                $lastValue .= $token->value;
            } else {
                $ret[] = $options['type']::parse(
                    $parser,
                    $list,
                    empty($options['typeOptions']) ? [] : $options['typeOptions'],
                );
            }
        }

        // Handling last element.
        //
        // This is treated differently to treat the following cases:
        //
        //           => []
        //      [,]  => ['', '']
        //      []   => []
        //      [a,] => ['a', '']
        //      [a]  => ['a']
        $lastRaw = trim($lastRaw);
        if (empty($options['type']) && ((strlen($lastRaw) > 0) || ($isCommaLast))) {
            $ret->raw[] = $lastRaw;
            $ret->values[] = trim($lastValue);
        }

        return $ret;
    }

    /** @param ArrayObj[] $component the component to be built */
    public static function buildAll(array $component): string
    {
        return implode(', ', $component);
    }
}
