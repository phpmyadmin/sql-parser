<?php

declare(strict_types=1);

namespace PhpMyAdmin\SqlParser\Parsers;

use PhpMyAdmin\SqlParser\Components\IntoKeyword;
use PhpMyAdmin\SqlParser\Parseable;
use PhpMyAdmin\SqlParser\Parser;
use PhpMyAdmin\SqlParser\Token;
use PhpMyAdmin\SqlParser\TokensList;
use PhpMyAdmin\SqlParser\TokenType;

/**
 * `INTO` keyword parser.
 */
final class IntoKeywords implements Parseable
{
    /**
     * @param Parser               $parser  the parser that serves as context
     * @param TokensList           $list    the list of tokens that are being parsed
     * @param array<string, mixed> $options parameters for parsing
     */
    public static function parse(Parser $parser, TokensList $list, array $options = []): IntoKeyword
    {
        $ret = new IntoKeyword();

        /**
         * The state of the parser.
         *
         * Below are the states of the parser.
         *
         *      0 -----------------------[ name ]----------------------> 1
         *      0 ---------------------[ OUTFILE ]---------------------> 2
         *
         *      1 ------------------------[ ( ]------------------------> (END)
         *
         *      2 ---------------------[ filename ]--------------------> 1
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

            if (($token->type === TokenType::Keyword) && ($token->flags & Token::FLAG_KEYWORD_RESERVED)) {
                if (($state === 0) && ($token->keyword === 'OUTFILE')) {
                    $ret->type = 'OUTFILE';
                    $state = 2;
                    continue;
                }

                // No other keyword is expected except for $state = 4, which expects `LINES`
                if ($state !== 4) {
                    break;
                }
            }

            if ($state === 0) {
                if (
                    (isset($options['fromInsert'])
                    && $options['fromInsert'])
                    || (isset($options['fromReplace'])
                    && $options['fromReplace'])
                ) {
                    $ret->dest = Expressions::parse(
                        $parser,
                        $list,
                        [
                            'parseField' => 'table',
                            'breakOnAlias' => true,
                        ],
                    );
                } else {
                    $ret->values = ExpressionArray::parse($parser, $list);
                }

                $state = 1;
            } elseif ($state === 1) {
                if (($token->type === TokenType::Operator) && ($token->value === '(')) {
                    $ret->columns = ArrayObjs::parse($parser, $list)->values;
                    ++$list->idx;
                }

                break;
            } elseif ($state === 2) {
                $ret->dest = $token->value;

                $state = 3;
            } elseif ($state === 3) {
                $ret->parseFileOptions($parser, $list, $token->keyword);
                $state = 4;
            } elseif ($state === 4) {
                if ($token->type === TokenType::Keyword && $token->keyword !== 'LINES') {
                    break;
                }

                $ret->parseFileOptions($parser, $list, $token->keyword);
                $state = 5;
            }
        }

        --$list->idx;

        return $ret;
    }
}
