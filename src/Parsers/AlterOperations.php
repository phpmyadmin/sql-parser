<?php

declare(strict_types=1);

namespace PhpMyAdmin\SqlParser\Parsers;

use PhpMyAdmin\SqlParser\Components\AlterOperation;
use PhpMyAdmin\SqlParser\Parseable;
use PhpMyAdmin\SqlParser\Parser;
use PhpMyAdmin\SqlParser\Token;
use PhpMyAdmin\SqlParser\TokensList;
use PhpMyAdmin\SqlParser\TokenType;

use function array_key_exists;
use function in_array;
use function is_int;
use function is_string;

/**
 * Parses an alter operation.
 */
final class AlterOperations implements Parseable
{
    /**
     * All database options.
     */
    public const DATABASE_OPTIONS = [
        'CHARACTER SET' => [
            1,
            'var',
        ],
        'CHARSET' => [
            1,
            'var',
        ],
        'DEFAULT CHARACTER SET' => [
            1,
            'var',
        ],
        'DEFAULT CHARSET' => [
            1,
            'var',
        ],
        'UPGRADE' => [
            1,
            'var',
        ],
        'COLLATE' => [
            2,
            'var',
        ],
        'DEFAULT COLLATE' => [
            2,
            'var',
        ],
    ];

    /**
     * All table options.
     */
    public const TABLE_OPTIONS = [
        'ENGINE' => [
            1,
            'var=',
        ],
        'ALGORITHM' => [
            1,
            'var=',
        ],
        'AUTO_INCREMENT' => [
            1,
            'var=',
        ],
        'AVG_ROW_LENGTH' => [
            1,
            'var',
        ],
        'COALESCE PARTITION' => [
            1,
            'var',
        ],
        'LOCK' => [
            1,
            'var=',
        ],
        'MAX_ROWS' => [
            1,
            'var',
        ],
        'ROW_FORMAT' => [
            1,
            'var',
        ],
        'COMMENT' => [
            1,
            'var',
        ],
        'ADD' => 1,
        'ALTER' => 1,
        'ANALYZE' => 1,
        'CHANGE' => 1,
        'CHARSET' => 1,
        'CHECK' => 1,
        'CONVERT' => 1,
        'DEFAULT CHARSET' => 1,
        'DISABLE' => 1,
        'DISCARD' => 1,
        'DROP' => 1,
        'ENABLE' => 1,
        'IMPORT' => 1,
        'MODIFY' => 1,
        'OPTIMIZE' => 1,
        'ORDER' => 1,
        'REBUILD' => 1,
        'REMOVE' => 1,
        'RENAME' => 1,
        'REORGANIZE' => 1,
        'REPAIR' => 1,
        'UPGRADE' => 1,

        'COLUMN' => 2,
        'CONSTRAINT' => 2,
        'DEFAULT' => 2,
        'BY' => 2,
        'FOREIGN' => 2,
        'FULLTEXT' => 2,
        'KEY' => 2,
        'KEYS' => 2,
        'PARTITION' => 2,
        'PARTITION BY' => 2,
        'PARTITIONING' => 2,
        'PRIMARY KEY' => 2,
        'SPATIAL' => 2,
        'TABLESPACE' => 2,
        'INDEX' => [
            2,
            'var',
        ],

        'CHARACTER SET' => 3,
        'TO' => [
            3,
            'var',
        ],
    ];

    /**
     * All user options.
     */
    public const USER_OPTIONS = [
        'ATTRIBUTE' => [
            1,
            'var',
        ],
        'COMMENT' => [
            1,
            'var',
        ],
        'REQUIRE' => [
            1,
            'var',
        ],

        'IDENTIFIED VIA' => [
            2,
            'var',
        ],
        'IDENTIFIED WITH' => [
            2,
            'var',
        ],
        'PASSWORD' => [
            2,
            'var',
        ],
        'WITH' => [
            2,
            'var',
        ],

        'BY' => [
            4,
            'expr',
        ],

        'ACCOUNT' => 1,
        'DEFAULT' => 1,

        'LOCK' => 2,
        'UNLOCK' => 2,

        'IDENTIFIED' => 3,
    ];

    /**
     * All view options.
     */
    public const VIEW_OPTIONS = ['AS' => 1];

    /**
     * All event options.
     */
    public const EVENT_OPTIONS = [
        'ON SCHEDULE' => 1,
        'EVERY' => [
            2,
            'expr',
        ],
        'AT' => [
            2,
            'expr',
        ],
        'STARTS' => [
            3,
            'expr',
        ],
        'ENDS' => [
            4,
            'expr',
        ],
        'ON COMPLETION PRESERVE' => 5,
        'ON COMPLETION NOT PRESERVE' => 5,
        'RENAME' => 6,
        'TO' => [7, 'expr', ['parseField' => 'table', 'breakOnAlias' => true]],
        'ENABLE' => 8,
        'DISABLE' => 8,
        'DISABLE ON SLAVE' => 8,
        'COMMENT' => [
            9,
            'var',
        ],
        'DO' => 10,
    ];

    /**
     * @param Parser               $parser  the parser that serves as context
     * @param TokensList           $list    the list of tokens that are being parsed
     * @param array<string, mixed> $options parameters for parsing
     */
    public static function parse(Parser $parser, TokensList $list, array $options = []): AlterOperation
    {
        $ret = new AlterOperation();

        /**
         * Counts brackets.
         */
        $brackets = 0;

        /**
         * The state of the parser.
         *
         * Below are the states of the parser.
         *
         *      0 ---------------------[ options ]---------------------> 1
         *
         *      1 ----------------------[ field ]----------------------> 2
         *
         *      1 -------------[ PARTITION / PARTITION BY ]------------> 3
         *
         *      2 -------------------------[ , ]-----------------------> 0
         */
        $state = 0;

        /**
         * partition state.
         */
        $partitionState = 0;

        for (; $list->idx < $list->count; ++$list->idx) {
            /**
             * Token parsed at this moment.
             */
            $token = $list->tokens[$list->idx];

            // End of statement.
            if ($token->type === TokenType::Delimiter) {
                break;
            }

            // Skipping comments.
            if ($token->type === TokenType::Comment) {
                continue;
            }

            // Skipping whitespaces.
            if ($token->type === TokenType::Whitespace) {
                if ($state === 2) {
                    // When parsing the unknown part, the whitespaces are
                    // included to not break anything.
                    $ret->unknown[] = $token;
                    continue;
                }
            }

            if ($state === 0) {
                $ret->options = OptionsArrays::parse($parser, $list, $options);

                // Not only when aliasing but also when parsing the body of an event, we just list the tokens of the
                // body in the unknown tokens list, as they define their own statements.
                if ($ret->options->has('AS') || $ret->options->has('DO')) {
                    for (; $list->idx < $list->count; ++$list->idx) {
                        if ($list->tokens[$list->idx]->type === TokenType::Delimiter) {
                            break;
                        }

                        $ret->unknown[] = $list->tokens[$list->idx];
                    }

                    break;
                }

                $state = 1;
                if ($ret->options->has('PARTITION') || $token->value === 'PARTITION BY') {
                    $state = 3;
                    $list->getPrevious(); // in order to check whether it's partition or partition by.
                }
            } elseif ($state === 1) {
                $ret->field = Expressions::parse(
                    $parser,
                    $list,
                    [
                        'breakOnAlias' => true,
                        'parseField' => 'column',
                    ],
                );
                if ($ret->field === null) {
                    // No field was read. We go back one token so the next
                    // iteration will parse the same token, but in state 2.
                    --$list->idx;
                }

                // If the operation is a RENAME COLUMN, now we have detected the field to rename, we need to parse
                // again the options to get the new name of the column.
                if ($ret->options->has('RENAME') && $ret->options->has('COLUMN')) {
                    $nextOptions = OptionsArrays::parse($parser, $list, $options);
                    $ret->options->merge($nextOptions);
                }

                $state = 2;
            } elseif ($state === 2) {
                if (is_string($token->value) || is_int($token->value)) {
                    $arrayKey = $token->value;
                } else {
                    $arrayKey = $token->token;
                }

                if ($token->type === TokenType::Operator) {
                    if ($token->value === '(') {
                        ++$brackets;
                    } elseif ($token->value === ')') {
                        --$brackets;
                    } elseif (($token->value === ',') && ($brackets === 0)) {
                        break;
                    }
                } elseif (! self::checkIfTokenQuotedSymbol($token) && $token->type !== TokenType::String) {
                    if (isset(Parser::STATEMENT_PARSERS[$arrayKey]) && Parser::STATEMENT_PARSERS[$arrayKey] !== '') {
                        $list->idx++; // Ignore the current token
                        $nextToken = $list->getNext();

                        if ($token->value === 'SET' && $nextToken !== null && $nextToken->value === '(') {
                            // To avoid adding the tokens between the SET() parentheses to the unknown tokens
                            $list->getNextOfTypeAndValue(TokenType::Operator, ')');
                        } elseif ($token->value === 'SET' && $nextToken !== null && $nextToken->value === 'DEFAULT') {
                            // to avoid adding the `DEFAULT` token to the unknown tokens.
                            ++$list->idx;
                        } else {
                            // We have reached the end of ALTER operation and suddenly found
                            // a start to new statement, but have not found a delimiter between them
                            $parser->error(
                                'A new statement was found, but no delimiter between it and the previous one.',
                                $token,
                            );
                            break;
                        }
                    } elseif (
                        (array_key_exists($arrayKey, self::DATABASE_OPTIONS)
                        || array_key_exists($arrayKey, self::TABLE_OPTIONS))
                        && ! self::checkIfColumnDefinitionKeyword($arrayKey)
                    ) {
                        // This alter operation has finished, which means a comma
                        // was missing before start of new alter operation
                        $parser->error('Missing comma before start of a new alter operation.', $token);
                        break;
                    }
                }

                $ret->unknown[] = $token;
            } elseif ($state === 3) {
                if ($partitionState === 0) {
                    $list->idx++; // Ignore the current token
                    $nextToken = $list->getNext();
                    if (
                        ($token->type === TokenType::Keyword)
                        && (($token->keyword === 'PARTITION BY')
                        || ($token->keyword === 'PARTITION' && $nextToken && $nextToken->value !== '('))
                    ) {
                        $partitionState = 1;
                    } elseif (($token->type === TokenType::Keyword) && ($token->keyword === 'PARTITION')) {
                        $partitionState = 2;
                    }

                    --$list->idx; // to decrease the idx by one, because the last getNext returned and increased it.

                    // reverting the effect of the getNext
                    $list->getPrevious();
                    $list->getPrevious();

                    ++$list->idx; // to index the idx by one, because the last getPrevious returned and decreased it.
                } elseif ($partitionState === 1) {
                    // Fetch the next token in a way the current index is reset to manage whitespaces in "field".
                    $currIdx = $list->idx;
                    ++$list->idx;
                    $nextToken = $list->getNext();
                    $list->idx = $currIdx;
                    // Building the expression used for partitioning.
                    if (empty($ret->field)) {
                        $ret->field = '';
                    }

                    if (
                        $token->type === TokenType::Operator
                        && $token->value === '('
                        && $nextToken
                        && $nextToken->keyword === 'PARTITION'
                    ) {
                        $partitionState = 2;
                        --$list->idx; // Current idx is on "(". We need a step back for ArrayObj::parse incoming.
                    } else {
                        $ret->field .= $token->type === TokenType::Whitespace ? ' ' : $token->token;
                    }
                } elseif ($partitionState === 2) {
                    $ret->partitions = ArrayObjs::parse(
                        $parser,
                        $list,
                        ['type' => PartitionDefinitions::class],
                    );
                }
            }
        }

        if ($ret->options->isEmpty()) {
            $parser->error('Unrecognized alter operation.', $list->tokens[$list->idx]);
        }

        --$list->idx;

        return $ret;
    }

    /**
     * Check if token's value is one of the common keywords
     * between column and table alteration
     *
     * @param string $tokenValue Value of current token
     */
    private static function checkIfColumnDefinitionKeyword(string $tokenValue): bool
    {
        $commonOptions = [
            'AUTO_INCREMENT',
            'COMMENT',
            'DEFAULT',
            'CHARACTER SET',
            'COLLATE',
            'PRIMARY',
            'UNIQUE',
            'PRIMARY KEY',
            'UNIQUE KEY',
        ];

        // Since these options can be used for
        // both table as well as a specific column in the table
        return in_array($tokenValue, $commonOptions);
    }

    /**
     * Check if token is symbol and quoted with backtick
     *
     * @param Token $token token to check
     */
    private static function checkIfTokenQuotedSymbol(Token $token): bool
    {
        return $token->type === TokenType::Symbol && $token->flags === Token::FLAG_SYMBOL_BACKTICK;
    }
}
