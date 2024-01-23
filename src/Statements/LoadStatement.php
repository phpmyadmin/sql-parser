<?php

declare(strict_types=1);

namespace PhpMyAdmin\SqlParser\Statements;

use PhpMyAdmin\SqlParser\Components\ArrayObj;
use PhpMyAdmin\SqlParser\Components\Expression;
use PhpMyAdmin\SqlParser\Components\OptionsArray;
use PhpMyAdmin\SqlParser\Components\SetOperation;
use PhpMyAdmin\SqlParser\Parser;
use PhpMyAdmin\SqlParser\Parsers\ArrayObjs;
use PhpMyAdmin\SqlParser\Parsers\ExpressionArray;
use PhpMyAdmin\SqlParser\Parsers\Expressions;
use PhpMyAdmin\SqlParser\Parsers\OptionsArrays;
use PhpMyAdmin\SqlParser\Parsers\SetOperations;
use PhpMyAdmin\SqlParser\Statement;
use PhpMyAdmin\SqlParser\TokensList;
use PhpMyAdmin\SqlParser\TokenType;

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
    public static array $statementOptions = [
        'LOW_PRIORITY' => 1,
        'CONCURRENT' => 1,
        'LOCAL' => 2,
    ];

    /**
     * FIELDS/COLUMNS Options for `LOAD DATA...INFILE` statements.
     */
    private const STATEMENT_FIELDS_OPTIONS = [
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
     */
    private const STATEMENT_LINES_OPTIONS = [
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
     */
    public Expression|null $fileName = null;

    /**
     * Table used as destination for this statement.
     */
    public Expression|null $table = null;

    /**
     * Partitions used as source for this statement.
     */
    public ArrayObj|null $partition = null;

    /**
     * Character set used in this statement.
     */
    public Expression|null $charsetName = null;

    /**
     * Options for FIELDS/COLUMNS keyword.
     *
     * @see LoadStatement::STATEMENT_FIELDS_OPTIONS
     */
    public OptionsArray|null $fieldsOptions = null;

    /**
     * Whether to use `FIELDS` or `COLUMNS` while building.
     */
    public string|null $fieldsKeyword = null;

    /**
     * Options for OPTIONS keyword.
     *
     * @see LoadStatement::STATEMENT_LINES_OPTIONS
     */
    public OptionsArray|null $linesOptions = null;

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
     */
    public Expression|null $ignoreNumber = null;

    /**
     * REPLACE/IGNORE Keyword.
     */
    public string|null $replaceIgnore = null;

    /**
     * LINES/ROWS Keyword.
     */
    public string|null $linesRows = null;

    public function build(): string
    {
        $ret = 'LOAD DATA ' . $this->options
            . ' INFILE ' . $this->fileName;

        if ($this->replaceIgnore !== null) {
            $ret .= ' ' . trim($this->replaceIgnore);
        }

        $ret .= ' INTO TABLE ' . $this->table;

        if ($this->partition !== null && strlen((string) $this->partition) > 0) {
            $ret .= ' PARTITION ' . $this->partition->build();
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
            $ret .= ' ' . Expressions::buildAll($this->columnNamesOrUserVariables);
        }

        if ($this->set !== null && $this->set !== []) {
            $ret .= ' SET ' . SetOperations::buildAll($this->set);
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
        $this->options = OptionsArrays::parse($parser, $list, static::$statementOptions);
        ++$list->idx;

        /**
         * The state of the parser.
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
                if ($token->type === TokenType::Keyword && $token->keyword !== 'INFILE') {
                    $parser->error('Unexpected keyword.', $token);
                    break;
                }

                if ($token->type !== TokenType::Keyword) {
                    $parser->error('Unexpected token.', $token);
                    break;
                }

                ++$list->idx;
                $this->fileName = Expressions::parse(
                    $parser,
                    $list,
                    ['parseField' => 'file'],
                );
                $state = 1;
            } elseif ($state === 1) {
                if ($token->type === TokenType::Keyword) {
                    if ($token->keyword === 'REPLACE' || $token->keyword === 'IGNORE') {
                        $this->replaceIgnore = trim($token->keyword);
                    } elseif ($token->keyword === 'INTO') {
                        $state = 2;
                    }
                }
            } elseif ($state === 2) {
                if ($token->type !== TokenType::Keyword || $token->keyword !== 'TABLE') {
                    $parser->error('Unexpected token.', $token);
                    break;
                }

                ++$list->idx;
                $this->table = Expressions::parse($parser, $list, ['parseField' => 'table', 'breakOnAlias' => true]);
                $state = 3;
            } elseif ($state >= 3 && $state <= 7) {
                if ($token->type === TokenType::Keyword) {
                    $newState = $this->parseKeywordsAccordingToState($parser, $list, $state);
                    if ($newState === $state) {
                        // Avoid infinite loop
                        break;
                    }
                } elseif ($token->type === TokenType::Operator && $token->token === '(') {
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
    public function parseFileOptions(Parser $parser, TokensList $list, string $keyword = 'FIELDS'): void
    {
        ++$list->idx;

        if ($keyword === 'FIELDS' || $keyword === 'COLUMNS') {
            // parse field options
            $this->fieldsOptions = OptionsArrays::parse($parser, $list, self::STATEMENT_FIELDS_OPTIONS);

            $this->fieldsKeyword = $keyword;
        } else {
            // parse line options
            $this->linesOptions = OptionsArrays::parse($parser, $list, self::STATEMENT_LINES_OPTIONS);
        }
    }

    public function parseKeywordsAccordingToState(Parser $parser, TokensList $list, int $state): int
    {
        $token = $list->tokens[$list->idx];

        switch ($state) {
            case 3:
                if ($token->keyword === 'PARTITION') {
                    ++$list->idx;
                    $this->partition = ArrayObjs::parse($parser, $list);

                    return 4;
                }

                // no break
            case 4:
                if ($token->keyword === 'CHARACTER SET') {
                    ++$list->idx;
                    $this->charsetName = Expressions::parse($parser, $list);

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

                    $this->ignoreNumber = Expressions::parse($parser, $list);
                    $nextToken = $list->getNextOfType(TokenType::Keyword);

                    if (
                        $nextToken->type === TokenType::Keyword
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
                    $this->set = SetOperations::parse($parser, $list);

                    return 8;
                }

                // no break
            default:
        }

        return $state;
    }
}
