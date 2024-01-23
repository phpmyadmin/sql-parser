<?php

declare(strict_types=1);

namespace PhpMyAdmin\SqlParser\Parsers;

use PhpMyAdmin\SqlParser\Components\ArrayObj;
use PhpMyAdmin\SqlParser\Parseable;
use PhpMyAdmin\SqlParser\Parser;
use PhpMyAdmin\SqlParser\Token;
use PhpMyAdmin\SqlParser\TokensList;
use PhpMyAdmin\SqlParser\TokenType;
use PhpMyAdmin\SqlParser\Translator;

use function count;
use function sprintf;

/**
 * `VALUES` keyword parser.
 */
final class Array2d implements Parseable
{
    /**
     * @param Parser               $parser  the parser that serves as context
     * @param TokensList           $list    the list of tokens that are being parsed
     * @param array<string, mixed> $options parameters for parsing
     *
     * @return ArrayObj[]
     */
    public static function parse(Parser $parser, TokensList $list, array $options = []): array
    {
        $ret = [];

        /**
         * The number of values in each set.
         */
        $count = -1;

        /**
         * The state of the parser.
         *
         * Below are the states of the parser.
         *
         *      0 ----------------------[ array ]----------------------> 1
         *
         *      1 ------------------------[ , ]------------------------> 0
         *      1 -----------------------[ else ]----------------------> (END)
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

            // No keyword is expected.
            if (($token->type === TokenType::Keyword) && ($token->flags & Token::FLAG_KEYWORD_RESERVED)) {
                break;
            }

            if ($state === 0) {
                if ($token->value !== '(') {
                    break;
                }

                /** @var ArrayObj $arr */
                $arr = ArrayObjs::parse($parser, $list, $options);
                $arrCount = count($arr->values);
                if ($count === -1) {
                    $count = $arrCount;
                } elseif ($arrCount !== $count) {
                    $parser->error(
                        sprintf(
                            Translator::gettext('%1$d values were expected, but found %2$d.'),
                            $count,
                            $arrCount,
                        ),
                        $token,
                    );
                }

                $ret[] = $arr;
                $state = 1;
            } elseif ($state === 1) {
                if ($token->value !== ',') {
                    break;
                }

                $state = 0;
            }
        }

        if ($state === 0) {
            $parser->error('An opening bracket followed by a set of values was expected.', $list->tokens[$list->idx]);
        }

        --$list->idx;

        return $ret;
    }
}
