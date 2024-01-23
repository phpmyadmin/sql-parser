<?php

declare(strict_types=1);

namespace PhpMyAdmin\SqlParser\Parsers;

use PhpMyAdmin\SqlParser\Components\Reference;
use PhpMyAdmin\SqlParser\Parseable;
use PhpMyAdmin\SqlParser\Parser;
use PhpMyAdmin\SqlParser\TokensList;
use PhpMyAdmin\SqlParser\TokenType;

/**
 * `REFERENCES` keyword parser.
 */
final class References implements Parseable
{
    /**
     * All references options.
     */
    private const REFERENCES_OPTIONS = [
        'MATCH' => [
            1,
            'var',
        ],
        'ON DELETE' => [
            2,
            'var',
        ],
        'ON UPDATE' => [
            3,
            'var',
        ],
    ];

    /**
     * @param Parser               $parser  the parser that serves as context
     * @param TokensList           $list    the list of tokens that are being parsed
     * @param array<string, mixed> $options parameters for parsing
     */
    public static function parse(Parser $parser, TokensList $list, array $options = []): Reference
    {
        $ret = new Reference();

        /**
         * The state of the parser.
         *
         * Below are the states of the parser.
         *
         *      0 ----------------------[ table ]---------------------> 1
         *
         *      1 ---------------------[ columns ]--------------------> 2
         *
         *      2 ---------------------[ options ]--------------------> (END)
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
                $ret->table = Expressions::parse(
                    $parser,
                    $list,
                    [
                        'parseField' => 'table',
                        'breakOnAlias' => true,
                    ],
                );
                $state = 1;
            } elseif ($state === 1) {
                $ret->columns = ArrayObjs::parse($parser, $list)->values;
                $state = 2;
            } else {
                $ret->options = OptionsArrays::parse($parser, $list, self::REFERENCES_OPTIONS);
                ++$list->idx;
                break;
            }
        }

        --$list->idx;

        return $ret;
    }
}
