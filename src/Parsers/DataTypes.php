<?php

declare(strict_types=1);

namespace PhpMyAdmin\SqlParser\Parsers;

use PhpMyAdmin\SqlParser\Components\DataType;
use PhpMyAdmin\SqlParser\Parseable;
use PhpMyAdmin\SqlParser\Parser;
use PhpMyAdmin\SqlParser\Token;
use PhpMyAdmin\SqlParser\TokensList;
use PhpMyAdmin\SqlParser\TokenType;

use function strtoupper;

/**
 * Parses a data type.
 */
final class DataTypes implements Parseable
{
    /**
     * All data type options.
     */
    private const DATA_TYPE_OPTIONS = [
        'BINARY' => 1,
        'CHARACTER SET' => [
            2,
            'var',
        ],
        'CHARSET' => [
            2,
            'var',
        ],
        'COLLATE' => [
            3,
            'var',
        ],
        'UNSIGNED' => 4,
        'ZEROFILL' => 5,
    ];

    /**
     * @param Parser               $parser  the parser that serves as context
     * @param TokensList           $list    the list of tokens that are being parsed
     * @param array<string, mixed> $options parameters for parsing
     */
    public static function parse(Parser $parser, TokensList $list, array $options = []): DataType|null
    {
        $ret = new DataType();

        /**
         * The state of the parser.
         *
         * Below are the states of the parser.
         *
         *      0 -------------------[ data type ]--------------------> 1
         *
         *      1 ----------------[ size and options ]----------------> 2
         */
        $state = 0;

        for (; $list->idx < $list->count; ++$list->idx) {
            /**
             * Token parsed at this moment.
             */
            $token = $list->tokens[$list->idx];

            // Skipping whitespaces and comments.
            if (($token->type === TokenType::Whitespace) || ($token->type === TokenType::Comment)) {
                continue;
            }

            if ($state === 0) {
                $ret->name = strtoupper((string) $token->value);
                if (($token->type !== TokenType::Keyword) || (! ($token->flags & Token::FLAG_KEYWORD_DATA_TYPE))) {
                    $parser->error('Unrecognized data type.', $token);
                }

                $state = 1;
            } elseif ($state === 1) {
                if (($token->type === TokenType::Operator) && ($token->value === '(')) {
                    $parameters = ArrayObjs::parse($parser, $list);
                    ++$list->idx;
                    $ret->parameters = ($ret->name === 'ENUM') || ($ret->name === 'SET') ?
                        $parameters->raw : $parameters->values;
                }

                $ret->options = OptionsArrays::parse($parser, $list, self::DATA_TYPE_OPTIONS);
                ++$list->idx;
                break;
            }
        }

        if (empty($ret->name)) {
            return null;
        }

        --$list->idx;

        return $ret;
    }
}
