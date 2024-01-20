<?php

declare(strict_types=1);

namespace PhpMyAdmin\SqlParser\Parsers;

use PhpMyAdmin\SqlParser\Components\FunctionCall;
use PhpMyAdmin\SqlParser\Parseable;
use PhpMyAdmin\SqlParser\Parser;
use PhpMyAdmin\SqlParser\TokensList;
use PhpMyAdmin\SqlParser\TokenType;

/**
 * Parses a function call.
 */
final class FunctionCalls implements Parseable
{
    /**
     * @param Parser               $parser  the parser that serves as context
     * @param TokensList           $list    the list of tokens that are being parsed
     * @param array<string, mixed> $options parameters for parsing
     */
    public static function parse(Parser $parser, TokensList $list, array $options = []): FunctionCall
    {
        $ret = new FunctionCall();

        /**
         * The state of the parser.
         *
         * Below are the states of the parser.
         *
         *      0 ----------------------[ name ]-----------------------> 1
         *
         *      1 --------------------[ parameters ]-------------------> (END)
         */
        $state = 0;

        for (; $list->idx < $list->count; ++$list->idx) {
            /**
             * Token parsed at this moment.
             */
            $token = $list->tokens[$list->idx];

            // End of statement.
            if ($token->type === TokenType::Delimiter) {
                --$list->idx; // Let last token to previous one to avoid "This type of clause was previously parsed."
                break;
            }

            // Skipping whitespaces and comments.
            if (($token->type === TokenType::Whitespace) || ($token->type === TokenType::Comment)) {
                continue;
            }

            if ($state === 0) {
                if ($token->type === TokenType::Operator && $token->value === '(') {
                    --$list->idx; // ArrayObj needs to start with `(`
                    $state = 1;
                    continue;// do not add this token to the name
                }

                $ret->name .= $token->value;
            } elseif ($state === 1) {
                    $ret->parameters = ArrayObjs::parse($parser, $list);
                break;
            }
        }

        return $ret;
    }
}
