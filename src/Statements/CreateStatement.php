<?php

declare(strict_types=1);

namespace PhpMyAdmin\SqlParser\Statements;

use PhpMyAdmin\SqlParser\Components\ArrayObj;
use PhpMyAdmin\SqlParser\Components\CreateDefinition;
use PhpMyAdmin\SqlParser\Components\DataType;
use PhpMyAdmin\SqlParser\Components\Expression;
use PhpMyAdmin\SqlParser\Components\OptionsArray;
use PhpMyAdmin\SqlParser\Components\ParameterDefinition;
use PhpMyAdmin\SqlParser\Components\PartitionDefinition;
use PhpMyAdmin\SqlParser\Parser;
use PhpMyAdmin\SqlParser\Parsers\ArrayObjs;
use PhpMyAdmin\SqlParser\Parsers\CreateDefinitions;
use PhpMyAdmin\SqlParser\Parsers\DataTypes;
use PhpMyAdmin\SqlParser\Parsers\Expressions;
use PhpMyAdmin\SqlParser\Parsers\OptionsArrays;
use PhpMyAdmin\SqlParser\Parsers\ParameterDefinitions;
use PhpMyAdmin\SqlParser\Parsers\PartitionDefinitions;
use PhpMyAdmin\SqlParser\Statement;
use PhpMyAdmin\SqlParser\Token;
use PhpMyAdmin\SqlParser\TokensList;
use PhpMyAdmin\SqlParser\TokenType;

use function is_array;
use function trim;

/**
 * `CREATE` statement.
 */
class CreateStatement extends Statement
{
    /**
     * Options for `CREATE` statements.
     *
     * @var array<string, int|array<int, int|string>>
     * @psalm-var array<string, (positive-int|array{positive-int, ('var'|'var='|'expr'|'expr=')})>
     */
    public static array $statementOptions = [
        // CREATE TABLE
        'TEMPORARY' => 1,

        // CREATE VIEW
        'OR REPLACE' => 2,
        'ALGORITHM' => [
            3,
            'var=',
        ],
        // `DEFINER` is also used for `CREATE FUNCTION / PROCEDURE`
        'DEFINER' => [
            4,
            'expr=',
        ],
        // Used in `CREATE VIEW`
        'SQL SECURITY' => [
            5,
            'var',
        ],

        'DATABASE' => 6,
        'EVENT' => 6,
        'FUNCTION' => 6,
        'INDEX' => 6,
        'UNIQUE INDEX' => 6,
        'FULLTEXT INDEX' => 6,
        'SPATIAL INDEX' => 6,
        'PROCEDURE' => 6,
        'SERVER' => 6,
        'TABLE' => 6,
        'TABLESPACE' => 6,
        'TRIGGER' => 6,
        'USER' => 6,
        'VIEW' => 6,
        'SCHEMA' => 6,

        // CREATE TABLE
        'IF NOT EXISTS' => 7,
    ];

    /**
     * All database options.
     */
    private const DATABASE_OPTIONS = [
        'CHARACTER SET' => [
            1,
            'var=',
        ],
        'CHARSET' => [
            1,
            'var=',
        ],
        'DEFAULT CHARACTER SET' => [
            1,
            'var=',
        ],
        'DEFAULT CHARSET' => [
            1,
            'var=',
        ],
        'DEFAULT COLLATE' => [
            2,
            'var=',
        ],
        'COLLATE' => [
            2,
            'var=',
        ],
    ];

    /**
     * All table options.
     */
    private const TABLE_OPTIONS = [
        'ENGINE' => [
            1,
            'var=',
        ],
        'AUTO_INCREMENT' => [
            2,
            'var=',
        ],
        'AVG_ROW_LENGTH' => [
            3,
            'var',
        ],
        'CHARACTER SET' => [
            4,
            'var=',
        ],
        'CHARSET' => [
            4,
            'var=',
        ],
        'DEFAULT CHARACTER SET' => [
            4,
            'var=',
        ],
        'DEFAULT CHARSET' => [
            4,
            'var=',
        ],
        'CHECKSUM' => [
            5,
            'var',
        ],
        'DEFAULT COLLATE' => [
            6,
            'var=',
        ],
        'COLLATE' => [
            6,
            'var=',
        ],
        'COMMENT' => [
            7,
            'var=',
        ],
        'CONNECTION' => [
            8,
            'var',
        ],
        'DATA DIRECTORY' => [
            9,
            'var',
        ],
        'DELAY_KEY_WRITE' => [
            10,
            'var',
        ],
        'INDEX DIRECTORY' => [
            11,
            'var',
        ],
        'INSERT_METHOD' => [
            12,
            'var',
        ],
        'KEY_BLOCK_SIZE' => [
            13,
            'var',
        ],
        'MAX_ROWS' => [
            14,
            'var',
        ],
        'MIN_ROWS' => [
            15,
            'var',
        ],
        'PACK_KEYS' => [
            16,
            'var',
        ],
        'PASSWORD' => [
            17,
            'var',
        ],
        'ROW_FORMAT' => [
            18,
            'var',
        ],
        'TABLESPACE' => [
            19,
            'var',
        ],
        'STORAGE' => [
            20,
            'var',
        ],
        'UNION' => [
            21,
            'var',
        ],
        'PAGE_COMPRESSED' => [
            22,
            'var',
        ],
        'PAGE_COMPRESSION_LEVEL' => [
            23,
            'var',
        ],
    ];

    /**
     * All function options.
     */
    private const FUNCTION_OPTIONS = [
        'NOT' => [
            2,
            'var',
        ],
        'FUNCTION' => [
            3,
            'var=',
        ],
        'PROCEDURE' => [
            3,
            'var=',
        ],
        'CONTAINS SQL' => 4,
        'NO SQL' => 4,
        'READS SQL DATA' => 4,
        'MODIFIES SQL DATA' => 4,
        'SQL SECURITY' => [
            6,
            'var',
        ],
        'LANGUAGE' => [
            7,
            'var',
        ],
        'COMMENT' => [
            8,
            'var',
        ],

        'CREATE' => 1,
        'DETERMINISTIC' => 2,
    ];

    /**
     * All trigger options.
     */
    private const TRIGGER_OPTIONS = [
        'BEFORE' => 1,
        'AFTER' => 1,
        'INSERT' => 2,
        'UPDATE' => 2,
        'DELETE' => 2,
    ];

    /**
     * The name of the entity that is created.
     *
     * Used by all `CREATE` statements.
     */
    public Expression|null $name = null;

    /**
     * The options of the entity (table, procedure, function, etc.).
     *
     * Used by `CREATE TABLE`, `CREATE FUNCTION` and `CREATE PROCEDURE`.
     *
     * @see CreateStatement::TABLE_OPTIONS
     * @see CreateStatement::FUNCTION_OPTIONS
     * @see CreateStatement::TRIGGER_OPTIONS
     */
    public OptionsArray|null $entityOptions = null;

    /**
     * If `CREATE TABLE`, a list of columns and keys.
     * If `CREATE VIEW`, a list of columns.
     *
     * Used by `CREATE TABLE` and `CREATE VIEW`.
     *
     * @var CreateDefinition[]|ArrayObj|null
     */
    public array|ArrayObj|null $fields = null;

    /**
     * If `CREATE TABLE WITH`.
     * If `CREATE TABLE AS WITH`.
     * If `CREATE VIEW AS WITH`.
     *
     * Used by `CREATE TABLE`, `CREATE VIEW`
     */
    public WithStatement|null $with = null;

    /**
     * If `CREATE TABLE ... SELECT`.
     * If `CREATE VIEW AS ` ... SELECT`.
     *
     * Used by `CREATE TABLE`, `CREATE VIEW`
     */
    public SelectStatement|null $select = null;

    /**
     * If `CREATE TABLE ... LIKE`.
     *
     * Used by `CREATE TABLE`
     */
    public Expression|null $like = null;

    /**
     * Expression used for partitioning.
     */
    public string|null $partitionBy = null;

    /**
     * The number of partitions.
     */
    public int|null $partitionsNum = null;

    /**
     * Expression used for subpartitioning.
     */
    public string|null $subpartitionBy = null;

    /**
     * The number of subpartitions.
     */
    public int|null $subpartitionsNum = null;

    /**
     * The partition of the new table.
     *
     * @var PartitionDefinition[]|null
     */
    public array|null $partitions = null;

    /**
     * If `CREATE TRIGGER` the name of the table.
     *
     * Used by `CREATE TRIGGER`.
     */
    public Expression|null $table = null;

    /**
     * The return data type of this routine.
     *
     * Used by `CREATE FUNCTION`.
     */
    public DataType|null $return = null;

    /**
     * The parameters of this routine.
     *
     * Used by `CREATE FUNCTION` and `CREATE PROCEDURE`.
     *
     * @var ParameterDefinition[]|null
     */
    public array|null $parameters = null;

    /**
     * The body of this function or procedure.
     * For views, it is the select statement that creates the view.
     * Used by `CREATE FUNCTION`, `CREATE PROCEDURE` and `CREATE VIEW`.
     *
     * @var Token[]
     */
    public array $body = [];

    public function build(): string
    {
        $fields = '';
        if ($this->fields !== null && $this->fields !== []) {
            if (is_array($this->fields)) {
                $fields = CreateDefinitions::buildAll($this->fields) . ' ';
            } else {
                $fields = $this->fields->build();
            }
        }

        if ($this->options->has('DATABASE') || $this->options->has('SCHEMA')) {
            return 'CREATE '
                . $this->options->build() . ' '
                . $this->name->build() . ' '
                . $this->entityOptions->build();
        }

        if ($this->options->has('TABLE')) {
            if ($this->select !== null) {
                return 'CREATE '
                    . $this->options->build() . ' '
                    . $this->name->build() . ' '
                    . $this->select->build();
            }

            if ($this->like !== null) {
                return 'CREATE '
                    . $this->options->build() . ' '
                    . $this->name->build() . ' LIKE '
                    . $this->like->build();
            }

            if ($this->with !== null) {
                return 'CREATE '
                . $this->options->build() . ' '
                . $this->name->build() . ' '
                . $this->with->build();
            }

            $partition = '';

            if (! empty($this->partitionBy)) {
                $partition .= "\nPARTITION BY " . $this->partitionBy;
            }

            if (! empty($this->partitionsNum)) {
                $partition .= "\nPARTITIONS " . $this->partitionsNum;
            }

            if (! empty($this->subpartitionBy)) {
                $partition .= "\nSUBPARTITION BY " . $this->subpartitionBy;
            }

            if (! empty($this->subpartitionsNum)) {
                $partition .= "\nSUBPARTITIONS " . $this->subpartitionsNum;
            }

            if (! empty($this->partitions)) {
                $partition .= "\n" . PartitionDefinitions::buildAll($this->partitions);
            }

            return 'CREATE '
                . $this->options->build() . ' '
                . $this->name->build() . ' '
                . $fields
                . ($this->entityOptions?->build() ?? '')
                . $partition;
        }

        if ($this->options->has('VIEW')) {
            $builtStatement = '';
            if ($this->select !== null) {
                $builtStatement = $this->select->build();
            } elseif ($this->with !== null) {
                $builtStatement = $this->with->build();
            }

            return 'CREATE '
                . $this->options->build() . ' '
                . $this->name->build() . ' '
                . $fields . ' AS ' . $builtStatement
                . TokensList::buildFromArray($this->body) . ' '
                . ($this->entityOptions?->build() ?? '');
        }

        if ($this->options->has('TRIGGER')) {
            return 'CREATE '
                . $this->options->build() . ' '
                . $this->name->build() . ' '
                . $this->entityOptions->build() . ' '
                . 'ON ' . $this->table->build() . ' '
                . 'FOR EACH ROW ' . TokensList::buildFromArray($this->body);
        }

        if ($this->options->has('PROCEDURE') || $this->options->has('FUNCTION')) {
            $tmp = '';
            if ($this->options->has('FUNCTION')) {
                $tmp = 'RETURNS ' . $this->return->build();
            }

            return 'CREATE '
                . $this->options->build() . ' '
                . $this->name->build() . ' '
                . ParameterDefinitions::buildAll($this->parameters) . ' '
                . $tmp . ' ' . $this->entityOptions->build() . ' '
                . TokensList::buildFromArray($this->body);
        }

        return 'CREATE '
            . $this->options->build() . ' '
            . $this->name->build() . ' '
            . TokensList::buildFromArray($this->body);
    }

    /**
     * @param Parser     $parser the instance that requests parsing
     * @param TokensList $list   the list of tokens to be parsed
     */
    public function parse(Parser $parser, TokensList $list): void
    {
        ++$list->idx; // Skipping `CREATE`.

        // Parsing options.
        $this->options = OptionsArrays::parse($parser, $list, static::$statementOptions);
        ++$list->idx; // Skipping last option.

        $isDatabase = $this->options->has('DATABASE') || $this->options->has('SCHEMA');
        $fieldName = $isDatabase ? 'database' : 'table';

        // Parsing the field name.
        $this->name = Expressions::parse(
            $parser,
            $list,
            [
                'parseField' => $fieldName,
                'breakOnAlias' => true,
            ],
        );

        if ($this->name === null) {
            $parser->error('The name of the entity was expected.', $list->tokens[$list->idx]);
        } else {
            ++$list->idx; // Skipping field.
        }

        /**
         * Token parsed at this moment.
         */
        $token = $list->tokens[$list->idx];
        $nextidx = $list->idx + 1;
        while ($nextidx < $list->count && $list->tokens[$nextidx]->type === TokenType::Whitespace) {
            ++$nextidx;
        }

        if ($isDatabase) {
            $this->entityOptions = OptionsArrays::parse($parser, $list, self::DATABASE_OPTIONS);
        } elseif ($this->options->has('TABLE')) {
            if (($token->type === TokenType::Keyword) && ($token->keyword === 'SELECT')) {
                /* CREATE TABLE ... SELECT */
                $this->select = new SelectStatement($parser, $list);
            } elseif ($token->type === TokenType::Keyword && ($token->keyword === 'WITH')) {
                /* CREATE TABLE WITH */
                $this->with = new WithStatement($parser, $list);
            } elseif (
                ($token->type === TokenType::Keyword) && ($token->keyword === 'AS')
                && ($list->tokens[$nextidx]->type === TokenType::Keyword)
            ) {
                if ($list->tokens[$nextidx]->value === 'SELECT') {
                    /* CREATE TABLE ... AS SELECT */
                    $list->idx = $nextidx;
                    $this->select = new SelectStatement($parser, $list);
                } elseif ($list->tokens[$nextidx]->value === 'WITH') {
                    /* CREATE TABLE WITH */
                    $list->idx = $nextidx;
                    $this->with = new WithStatement($parser, $list);
                }
            } elseif ($token->type === TokenType::Keyword && $token->keyword === 'LIKE') {
                /* CREATE TABLE `new_tbl` LIKE 'orig_tbl' */
                $list->idx = $nextidx;
                $this->like = Expressions::parse(
                    $parser,
                    $list,
                    [
                        'parseField' => 'table',
                        'breakOnAlias' => true,
                    ],
                );
                // The 'LIKE' keyword was found, but no table_name was found next to it
                if ($this->like === null) {
                    $parser->error('A table name was expected.', $list->tokens[$list->idx]);
                }
            } else {
                $this->fields = CreateDefinitions::parse($parser, $list);
                if ($this->fields === []) {
                    $parser->error('At least one column definition was expected.', $list->tokens[$list->idx]);
                }

                ++$list->idx;

                $this->entityOptions = OptionsArrays::parse($parser, $list, self::TABLE_OPTIONS);

                /**
                 * The field that is being filled (`partitionBy` or
                 * `subpartitionBy`).
                 */
                $field = null;

                /**
                 * The number of brackets. `false` means no bracket was found
                 * previously. At least one bracket is required to validate the
                 * expression.
                 */
                $brackets = false;

                /*
                 * Handles partitions.
                 */
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

                    if (($token->type === TokenType::Keyword) && ($token->keyword === 'PARTITION BY')) {
                        $field = 'partitionBy';
                        $brackets = false;
                    } elseif (($token->type === TokenType::Keyword) && ($token->keyword === 'SUBPARTITION BY')) {
                        $field = 'subpartitionBy';
                        $brackets = false;
                    } elseif (($token->type === TokenType::Keyword) && ($token->keyword === 'PARTITIONS')) {
                        $token = $list->getNextOfType(TokenType::Number);
                        --$list->idx; // `getNextOfType` also advances one position.
                        $this->partitionsNum = $token->value;
                    } elseif (($token->type === TokenType::Keyword) && ($token->keyword === 'SUBPARTITIONS')) {
                        $token = $list->getNextOfType(TokenType::Number);
                        --$list->idx; // `getNextOfType` also advances one position.
                        $this->subpartitionsNum = $token->value;
                    } elseif (! empty($field)) {
                        /*
                         * Handling the content of `PARTITION BY` and `SUBPARTITION BY`.
                         */

                        // Counting brackets.
                        if ($token->type === TokenType::Operator) {
                            if ($token->value === '(') {
                                // This is used instead of `++$brackets` because,
                                // initially, `$brackets` is `false` cannot be
                                // incremented.
                                $brackets += 1;
                            } elseif ($token->value === ')') {
                                --$brackets;
                            }
                        }

                        // Building the expression used for partitioning.
                        $this->$field .= $token->type === TokenType::Whitespace ? ' ' : $token->token;

                        // Last bracket was read, the expression ended.
                        // Comparing with `0` and not `false`, because `false` means
                        // that no bracket was found and at least one must is
                        // required.
                        if ($brackets === 0) {
                            $this->$field = trim($this->$field);
                            $field = null;
                        }
                    } elseif (($token->type === TokenType::Operator) && ($token->value === '(')) {
                        if (! empty($this->partitionBy)) {
                            $this->partitions = ArrayObjs::parse(
                                $parser,
                                $list,
                                ['type' => PartitionDefinitions::class],
                            );
                        }

                        break;
                    }
                }
            }
        } elseif ($this->options->has('PROCEDURE') || $this->options->has('FUNCTION')) {
            $this->parameters = ParameterDefinitions::parse($parser, $list);
            if ($this->options->has('FUNCTION')) {
                $prevToken = $token;
                $token = $list->getNextOfType(TokenType::Keyword);
                if ($token === null || $token->keyword !== 'RETURNS') {
                    $parser->error('A "RETURNS" keyword was expected.', $token ?? $prevToken);
                } else {
                    ++$list->idx;
                    $this->return = DataTypes::parse($parser, $list);
                }
            }

            ++$list->idx;

            $this->entityOptions = OptionsArrays::parse($parser, $list, self::FUNCTION_OPTIONS);
            ++$list->idx;

            for (; $list->idx < $list->count; ++$list->idx) {
                $token = $list->tokens[$list->idx];
                if ($token->type === TokenType::Delimiter) {
                    break;
                }

                $this->body[] = $token;
            }
        } elseif ($this->options->has('VIEW')) {
            /** @var Token $token */
            $token = $list->getNext(); // Skipping whitespaces and comments.

            // Parsing columns list.
            if (($token->type === TokenType::Operator) && ($token->value === '(')) {
                --$list->idx; // getNext() also goes forward one field.
                $this->fields = ArrayObjs::parse($parser, $list);
                ++$list->idx; // Skipping last token from the array.
                $list->getNext();
            }

            // Parsing the SELECT expression if the view started with it.
            if (
                $token->type === TokenType::Keyword
                && $token->keyword === 'AS'
                && $list->tokens[$nextidx]->type === TokenType::Keyword
            ) {
                if ($list->tokens[$nextidx]->value === 'SELECT') {
                    $list->idx = $nextidx;
                    $this->select = new SelectStatement($parser, $list);
                    ++$list->idx; // Skipping last token from the select.
                } elseif ($list->tokens[$nextidx]->value === 'WITH') {
                    ++$list->idx;
                    $this->with = new WithStatement($parser, $list);
                }
            }

            // Parsing all other tokens
            for (; $list->idx < $list->count; ++$list->idx) {
                $token = $list->tokens[$list->idx];
                if ($token->type === TokenType::Delimiter) {
                    break;
                }

                $this->body[] = $token;
            }
        } elseif ($this->options->has('TRIGGER')) {
            // Parsing the time and the event.
            $this->entityOptions = OptionsArrays::parse($parser, $list, self::TRIGGER_OPTIONS);
            ++$list->idx;

            $list->getNextOfTypeAndValue(TokenType::Keyword, 'ON');
            ++$list->idx; // Skipping `ON`.

            // Parsing the name of the table.
            $this->table = Expressions::parse(
                $parser,
                $list,
                [
                    'parseField' => 'table',
                    'breakOnAlias' => true,
                ],
            );
            ++$list->idx;

            $list->getNextOfTypeAndValue(TokenType::Keyword, 'FOR EACH ROW');
            ++$list->idx; // Skipping `FOR EACH ROW`.

            for (; $list->idx < $list->count; ++$list->idx) {
                $token = $list->tokens[$list->idx];
                if ($token->type === TokenType::Delimiter) {
                    break;
                }

                $this->body[] = $token;
            }
        } else {
            for (; $list->idx < $list->count; ++$list->idx) {
                $token = $list->tokens[$list->idx];
                if ($token->type === TokenType::Delimiter) {
                    break;
                }

                $this->body[] = $token;
            }
        }
    }
}
