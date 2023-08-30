<?php

declare(strict_types=1);

namespace PhpMyAdmin\SqlParser\Statements;

use PhpMyAdmin\SqlParser\Components\ArrayObj;
use PhpMyAdmin\SqlParser\Components\Expression;
use PhpMyAdmin\SqlParser\Components\ExpressionArray;
use PhpMyAdmin\SqlParser\Components\OptionsArray;
use PhpMyAdmin\SqlParser\Components\SetOperation;
use PhpMyAdmin\SqlParser\Parser;
use PhpMyAdmin\SqlParser\Statement;
use PhpMyAdmin\SqlParser\Token;
use PhpMyAdmin\SqlParser\TokensList;

use function strlen;
use function trim;

/**
 * `LOAD` statement.
 *
 * LOAD DATA [LOW_PRIORITY | CONCURRENT] [LOCAL] INFILE 'file_name'
 *   [REPLACE | IGNORE]
 *   INTO TABLE tbl_name
 *   [PARTITION (partition_name,...)]
 *   [CHARACTER SET charset_name]
 *   [{FIELDS | COLUMNS}
 *       [TERMINATED BY 'string']
 *       [[OPTIONALLY] ENCLOSED BY 'char']
 *       [ESCAPED BY 'char']
 *   ]
 *   [LINES
 *       [STARTING BY 'string']
 *       [TERMINATED BY 'string']
 *  ]
 *   [IGNORE number {LINES | ROWS}]
 *   [(col_name_or_user_var,...)]
 *   [SET col_name = expr,...]
 */
class LoadStatement extends Statement
{
    /**
     * Options for `LOAD` statements and their slot ID.
     *
     * @var array<string, int|array<int, int|string>>
     * @psalm-var array<string, (positive-int|array{positive-int, ('var'|'var='|'expr'|'expr=')})>
     */
    public static $statementOptions = [
        'LOW_PRIORITY' => 1,
        'CONCURRENT' => 1,
        'LOCAL' => 2,
    ];

    /**
     * FIELDS/COLUMNS Options for `LOAD DATA...INFILE` statements.
     *
     * @var array<string, int|array<int, int|string>>
     * @psalm-var array<string, (positive-int|array{positive-int, ('var'|'var='|'expr'|'expr=')})>
     */
    public static $statementFieldsOptions = [
        'TERMINATED BY' => [
            1,
            'expr',
        ],
        'OPTIONALLY' => 2,
        'ENCLOSED BY' => [
            3,
            'expr',
        ],
        'ESCAPED BY' => [
            4,
            'expr',
        ],
    ];

    /**
     * LINES Options for `LOAD DATA...INFILE` statements.
     *
     * @var array<string, int|array<int, int|string>>
     * @psalm-var array<string, (positive-int|array{positive-int, ('var'|'var='|'expr'|'expr=')})>
     */
    public static $statementLinesOptions = [
        'STARTING BY' => [
            1,
            'expr',
        ],
        'TERMINATED BY' => [
            2,
            'expr',
        ],
    ];

    /**
     * File name being used to load data.
     *
     * @var Expression|null
     */
    public $fileName;

    /**
     * Table used as destination for this statement.
     *
     * @var Expression|null
     */
    public $table;

    /**
     * Partitions used as source for this statement.
     *
     * @var ArrayObj|null
     */
    public $partition;

    /**
     * Character set used in this statement.
     *
     * @var Expression|null
     */
    public $charsetName;

    /**
     * Options for FIELDS/COLUMNS keyword.
     *
     * @see LoadStatement::$statementFieldsOptions
     *
     * @var OptionsArray|null
     */
    public $fieldsOptions;

    /**
     * Whether to use `FIELDS` or `COLUMNS` while building.
     *
     * @var string|null
     */
    public $fieldsKeyword;

    /**
     * Options for OPTIONS keyword.
     *
     * @see LoadStatement::$statementLinesOptions
     *
     * @var OptionsArray|null
     */
    public $linesOptions;

    /**
     * Column names or user variables.
     *
     * @var Expression[]|null
     */
    public array|null $columnNamesOrUserVariables = null;

    /**
     * SET clause's updated values(optional).
     *
     * @var SetOperation[]|null
     */
    public array|null $set = null;

    /**
     * Ignore 'number' LINES/ROWS.
     *
     * @var Expression|null
     */
    public $ignoreNumber;

    /**
     * REPLACE/IGNORE Keyword.
     *
     * @var string|null
     */
    public $replaceIgnore;

    /**
     * LINES/ROWS Keyword.
     *
     * @var string|null
     */
    public $linesRows;

    public function build(): string
    {
        $ret = 'LOAD DATA ' . $this->options
            . ' INFILE ' . $this->fileName;

        if ($this->replaceIgnore !== null) {
            $ret .= ' ' . trim($this->replaceIgnore);
        }

        $ret .= ' INTO TABLE ' . $this->table;

        if ($this->partition !== null && strlen((string) $this->partition) > 0) {
            $ret .= ' PARTITION ' . ArrayObj::build($this->partition);
        }

        if ($this->charsetName !== null) {
            $ret .= ' CHARACTER SET ' . $this->charsetName;
        }

        if ($this->fieldsKeyword !== null) {
            $ret .= ' ' . $this->fieldsKeyword . ' ' . $this->fieldsOptions;
        }

        if ($this->linesOptions !== null && strlen((string) $this->linesOptions) > 0) {
            $ret .= ' LINES ' . $this->linesOptions;
        }

        if ($this->ignoreNumber !== null) {
            $ret .= ' IGNORE ' . $this->ignoreNumber . ' ' . $this->linesRows;
        }

        if ($this->columnNamesOrUserVariables !== null && $this->columnNamesOrUserVariables !== []) {
            $ret .= ' ' . Expression::buildAll($this->columnNamesOrUserVariables);
        }

        if ($this->set !== null && $this->set !== []) {
            $ret .= ' SET ' . SetOperation::buildAll($this->set);
        }

        return $ret;
    }

    /**
     * @param Parser     $parser the instance that requests parsing
     * @param TokensList $list   the list of tokens to be parsed
     */
    public function parse(Parser $parser, TokensList $list): void
    {
        ++$list->idx; // Skipping `LOAD DATA`.

        // parse any options if provided
        $this->options = OptionsArray::parse($parser, $list, static::$statementOptions);
        ++$list->idx;

        /**
         * The state of the parser.
         *
         * @var int
         */
        $state = 0;

        for (; $list->idx < $list->count; ++$list->idx) {
            /**
             * Token parsed at this moment.
             */
            $token = $list->tokens[$list->idx];

            // End of statement.
            if ($token->type === Token::TYPE_DELIMITER) {
                break;
            }

            // Skipping whitespaces and comments.
            if (($token->type === Token::TYPE_WHITESPACE) || ($token->type === Token::TYPE_COMMENT)) {
                continue;
            }

            if ($state === 0) {
                if ($token->type === Token::TYPE_KEYWORD && $token->keyword !== 'INFILE') {
                    $parser->error('Unexpected keyword.', $token);
                    break;
                }

                if ($token->type !== Token::TYPE_KEYWORD) {
                    $parser->error('Unexpected token.', $token);
                    break;
                }

                ++$list->idx;
                $this->fileName = Expression::parse(
                    $parser,
                    $list,
                    ['parseField' => 'file']
                );
                $state = 1;
            } elseif ($state === 1) {
                if ($token->type === Token::TYPE_KEYWORD) {
                    if ($token->keyword === 'REPLACE' || $token->keyword === 'IGNORE') {
                        $this->replaceIgnore = trim($token->keyword);
                    } elseif ($token->keyword === 'INTO') {
                        $state = 2;
                    }
                }
            } elseif ($state === 2) {
                if ($token->type !== Token::TYPE_KEYWORD || $token->keyword !== 'TABLE') {
                    $parser->error('Unexpected token.', $token);
                    break;
                }

                ++$list->idx;
                $this->table = Expression::parse($parser, $list, ['parseField' => 'table']);
                $state = 3;
            } elseif ($state >= 3 && $state <= 7) {
                if ($token->type === Token::TYPE_KEYWORD) {
                    $newState = $this->parseKeywordsAccordingToState($parser, $list, $state);
                    if ($newState === $state) {
                        // Avoid infinite loop
                        break;
                    }
                } elseif ($token->type === Token::TYPE_OPERATOR && $token->token === '(') {
                    $this->columnNamesOrUserVariables
                        = ExpressionArray::parse($parser, $list);
                    $state = 7;
                } else {
                    $parser->error('Unexpected token.', $token);
                    break;
                }
            }
        }

        --$list->idx;
    }

    /**
     * @param Parser     $parser  The parser
     * @param TokensList $list    A token list
     * @param string     $keyword The keyword
     */
    public function parseFileOptions(Parser $parser, TokensList $list, $keyword = 'FIELDS'): void
    {
        ++$list->idx;

        if ($keyword === 'FIELDS' || $keyword === 'COLUMNS') {
            // parse field options
            $this->fieldsOptions = OptionsArray::parse($parser, $list, static::$statementFieldsOptions);

            $this->fieldsKeyword = $keyword;
        } else {
            // parse line options
            $this->linesOptions = OptionsArray::parse($parser, $list, static::$statementLinesOptions);
        }
    }

    /**
     * @param Parser     $parser
     * @param TokensList $list
     * @param int        $state
     */
    public function parseKeywordsAccordingToState($parser, $list, $state): int
    {
        $token = $list->tokens[$list->idx];

        switch ($state) {
            case 3:
                if ($token->keyword === 'PARTITION') {
                    ++$list->idx;
                    $this->partition = ArrayObj::parse($parser, $list);

                    return 4;
                }

                // no break
            case 4:
                if ($token->keyword === 'CHARACTER SET') {
                    ++$list->idx;
                    $this->charsetName = Expression::parse($parser, $list);

                    return 5;
                }

                // no break
            case 5:
                if ($token->keyword === 'FIELDS' || $token->keyword === 'COLUMNS' || $token->keyword === 'LINES') {
                    $this->parseFileOptions($parser, $list, $token->value);

                    return 6;
                }

                // no break
            case 6:
                if ($token->keyword === 'IGNORE') {
                    ++$list->idx;

                    $this->ignoreNumber = Expression::parse($parser, $list);
                    $nextToken = $list->getNextOfType(Token::TYPE_KEYWORD);

                    if (
                        $nextToken->type === Token::TYPE_KEYWORD
                        && (($nextToken->keyword === 'LINES')
                        || ($nextToken->keyword === 'ROWS'))
                    ) {
                        $this->linesRows = $nextToken->token;
                    }

                    return 7;
                }

                // no break
            case 7:
                if ($token->keyword === 'SET') {
                    ++$list->idx;
                    $this->set = SetOperation::parse($parser, $list);

                    return 8;
                }

                // no break
            default:
        }

        return $state;
    }
}
