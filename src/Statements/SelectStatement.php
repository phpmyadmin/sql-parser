<?php

declare(strict_types=1);

namespace PhpMyAdmin\SqlParser\Statements;

use PhpMyAdmin\SqlParser\Components\ArrayObj;
use PhpMyAdmin\SqlParser\Components\CaseExpression;
use PhpMyAdmin\SqlParser\Components\Condition;
use PhpMyAdmin\SqlParser\Components\Expression;
use PhpMyAdmin\SqlParser\Components\FunctionCall;
use PhpMyAdmin\SqlParser\Components\GroupKeyword;
use PhpMyAdmin\SqlParser\Components\IndexHint;
use PhpMyAdmin\SqlParser\Components\IntoKeyword;
use PhpMyAdmin\SqlParser\Components\JoinKeyword;
use PhpMyAdmin\SqlParser\Components\Limit;
use PhpMyAdmin\SqlParser\Components\OptionsArray;
use PhpMyAdmin\SqlParser\Components\OrderKeyword;
use PhpMyAdmin\SqlParser\Exceptions\ParserException;
use PhpMyAdmin\SqlParser\Parser;
use PhpMyAdmin\SqlParser\Parsers\OptionsArrays;
use PhpMyAdmin\SqlParser\Statement;
use PhpMyAdmin\SqlParser\TokensList;
use PhpMyAdmin\SqlParser\TokenType;

use function array_key_exists;
use function is_string;

/**
 * `SELECT` statement.
 *
 * SELECT
 *     [ALL | DISTINCT | DISTINCTROW ]
 *       [HIGH_PRIORITY]
 *       [MAX_STATEMENT_TIME = N]
 *       [STRAIGHT_JOIN]
 *       [SQL_SMALL_RESULT] [SQL_BIG_RESULT] [SQL_BUFFER_RESULT]
 *       [SQL_CACHE | SQL_NO_CACHE] [SQL_CALC_FOUND_ROWS]
 *     select_expr [, select_expr ...]
 *     [FROM table_references
 *       [PARTITION partition_list]
 *     [WHERE where_condition]
 *     [GROUP BY {col_name | expr | position}
 *       [ASC | DESC], ... [WITH ROLLUP]]
 *     [HAVING where_condition]
 *     [ORDER BY {col_name | expr | position}
 *       [ASC | DESC], ...]
 *     [LIMIT {[offset,] row_count | row_count OFFSET offset}]
 *     [PROCEDURE procedure_name(argument_list)]
 *     [INTO OUTFILE 'file_name'
 *         [CHARACTER SET charset_name]
 *         export_options
 *       | INTO DUMPFILE 'file_name'
 *       | INTO var_name [, var_name]]
 *     [FOR UPDATE | LOCK IN SHARE MODE]]
 */
class SelectStatement extends Statement
{
    /**
     * Options for `SELECT` statements and their slot ID.
     *
     * @var array<string, int|array<int, int|string>>
     * @psalm-var array<string, (positive-int|array{positive-int, ('var'|'var='|'expr'|'expr=')})>
     */
    public static array $statementOptions = [
        'ALL' => 1,
        'DISTINCT' => 1,
        'DISTINCTROW' => 1,
        'HIGH_PRIORITY' => 2,
        'MAX_STATEMENT_TIME' => [
            3,
            'var=',
        ],
        'STRAIGHT_JOIN' => 4,
        'SQL_SMALL_RESULT' => 5,
        'SQL_BIG_RESULT' => 6,
        'SQL_BUFFER_RESULT' => 7,
        'SQL_CACHE' => 8,
        'SQL_NO_CACHE' => 8,
        'SQL_CALC_FOUND_ROWS' => 9,
    ];

    protected const STATEMENT_GROUP_OPTIONS = ['WITH ROLLUP' => 1];

    protected const STATEMENT_END_OPTIONS = [
        'FOR UPDATE' => 1,
        'LOCK IN SHARE MODE' => 1,
    ];

    /**
     * The clauses of this statement, in order.
     *
     * @see Statement::$clauses
     *
     * @var array<string, array{non-empty-string, int-mask-of<self::ADD_*>}>
     */
    public static array $clauses = [
        'SELECT' => [
            'SELECT',
            Statement::ADD_KEYWORD,
        ],
        // Used for options.
        '_OPTIONS' => [
            '_OPTIONS',
            Statement::ADD_CLAUSE,
        ],
        // Used for selected expressions.
        '_SELECT' => [
            'SELECT',
            Statement::ADD_CLAUSE,
        ],
        'INTO' => [
            'INTO',
            Statement::ADD_CLAUSE | Statement::ADD_KEYWORD,
        ],
        'FROM' => [
            'FROM',
            Statement::ADD_CLAUSE | Statement::ADD_KEYWORD,
        ],
        'FORCE' => [
            'FORCE',
            Statement::ADD_CLAUSE,
        ],
        'USE' => [
            'USE',
            Statement::ADD_CLAUSE,
        ],
        'IGNORE' => [
            'IGNORE',
            Statement::ADD_CLAUSE | Statement::ADD_KEYWORD,
        ],
        'PARTITION' => [
            'PARTITION',
            Statement::ADD_CLAUSE | Statement::ADD_KEYWORD,
        ],

        'JOIN' => [
            'JOIN',
            Statement::ADD_CLAUSE,
        ],
        'FULL JOIN' => [
            'FULL JOIN',
            Statement::ADD_CLAUSE,
        ],
        'INNER JOIN' => [
            'INNER JOIN',
            Statement::ADD_CLAUSE,
        ],
        'LEFT JOIN' => [
            'LEFT JOIN',
            Statement::ADD_CLAUSE,
        ],
        'LEFT OUTER JOIN' => [
            'LEFT OUTER JOIN',
            Statement::ADD_CLAUSE,
        ],
        'RIGHT JOIN' => [
            'RIGHT JOIN',
            Statement::ADD_CLAUSE,
        ],
        'RIGHT OUTER JOIN' => [
            'RIGHT OUTER JOIN',
            Statement::ADD_CLAUSE,
        ],
        'NATURAL JOIN' => [
            'NATURAL JOIN',
            Statement::ADD_CLAUSE,
        ],
        'NATURAL LEFT JOIN' => [
            'NATURAL LEFT JOIN',
            Statement::ADD_CLAUSE,
        ],
        'NATURAL RIGHT JOIN' => [
            'NATURAL RIGHT JOIN',
            Statement::ADD_CLAUSE,
        ],
        'NATURAL LEFT OUTER JOIN' => [
            'NATURAL LEFT OUTER JOIN',
            Statement::ADD_CLAUSE,
        ],
        'NATURAL RIGHT OUTER JOIN' => [
            'NATURAL RIGHT JOIN',
            Statement::ADD_CLAUSE,
        ],
        'WHERE' => [
            'WHERE',
            Statement::ADD_CLAUSE | Statement::ADD_KEYWORD,
        ],
        'GROUP BY' => [
            'GROUP BY',
            Statement::ADD_CLAUSE | Statement::ADD_KEYWORD,
        ],
        '_GROUP_OPTIONS' => [
            '_GROUP_OPTIONS',
            Statement::ADD_CLAUSE,
        ],
        'HAVING' => [
            'HAVING',
            Statement::ADD_CLAUSE | Statement::ADD_KEYWORD,
        ],
        'ORDER BY' => [
            'ORDER BY',
            Statement::ADD_CLAUSE | Statement::ADD_KEYWORD,
        ],
        'LIMIT' => [
            'LIMIT',
            Statement::ADD_CLAUSE | Statement::ADD_KEYWORD,
        ],
        'PROCEDURE' => [
            'PROCEDURE',
            Statement::ADD_CLAUSE | Statement::ADD_KEYWORD,
        ],
        'UNION' => [
            'UNION',
            Statement::ADD_CLAUSE,
        ],
        'EXCEPT' => [
            'EXCEPT',
            Statement::ADD_CLAUSE,
        ],
        'INTERSECT' => [
            'INTERSECT',
            Statement::ADD_CLAUSE,
        ],
        '_END_OPTIONS' => [
            '_END_OPTIONS',
            Statement::ADD_CLAUSE,
        ],
        // These are available only when `UNION` is present.
        // 'ORDER BY'                      => ['ORDER BY', Statement::ADD_CLAUSE|Statement::ADD_KEYWORD],
        // 'LIMIT'                         => ['LIMIT', Statement::ADD_CLAUSE|Statement::ADD_KEYWORD],
    ];

    /**
     * Expressions that are being selected by this statement.
     *
     * @var (CaseExpression|Expression)[]
     */
    public array $expr = [];

    /**
     * Tables used as sources for this statement.
     *
     * @var Expression[]
     */
    public array $from = [];

    /**
     * Index hints
     *
     * @var IndexHint[]|null
     */
    public array|null $indexHints = null;

    /**
     * Partitions used as source for this statement.
     */
    public ArrayObj|null $partition = null;

    /**
     * Conditions used for filtering each row of the result set.
     *
     * @var Condition[]|null
     */
    public array|null $where = null;

    /**
     * Conditions used for grouping the result set.
     *
     * @var GroupKeyword[]|null
     */
    public array|null $group = null;

    /**
     * List of options available for the GROUP BY component.
     */
    public OptionsArray|null $groupOptions = null;

    /**
     * Conditions used for filtering the result set.
     *
     * @var Condition[]|null
     */
    public array|null $having = null;

    /**
     * Specifies the order of the rows in the result set.
     *
     * @var OrderKeyword[]|null
     */
    public array|null $order = null;

    /**
     * Conditions used for limiting the size of the result set.
     */
    public Limit|null $limit = null;

    /**
     * Procedure that should process the data in the result set.
     */
    public FunctionCall|null $procedure = null;

    /**
     * Destination of this result set.
     */
    public IntoKeyword|null $into = null;

    /**
     * Joins.
     *
     * @var JoinKeyword[]|null
     */
    public array|null $join = null;

    /**
     * Unions.
     *
     * @var SelectStatement[]
     */
    public array $union = [];

    /**
     * The end options of this query.
     *
     * @see SelectStatement::STATEMENT_END_OPTIONS
     */
    public OptionsArray|null $endOptions = null;

    /**
     * Parses the statements defined by the tokens list.
     *
     * @param Parser     $parser the instance that requests parsing
     * @param TokensList $list   the list of tokens to be parsed
     *
     * @throws ParserException
     */
    public function parse(Parser $parser, TokensList $list): void
    {
        /**
         * Array containing all list of clauses parsed.
         * This is used to check for duplicates.
         */
        $parsedClauses = [];

        // This may be corrected by the parser.
        $this->first = $list->idx;

        /**
         * Whether options were parsed or not.
         * For statements that do not have any options this is set to `true` by
         * default.
         */
        $parsedOptions = static::$statementOptions === [];

        for (; $list->idx < $list->count; ++$list->idx) {
            /**
             * Token parsed at this moment.
             */
            $token = $list->tokens[$list->idx];

            // End of statement.
            if ($token->type === TokenType::Delimiter) {
                break;
            }

            // Checking if this closing bracket is the pair for a bracket
            // outside the statement.
            if (($token->value === ')') && ($parser->brackets > 0)) {
                --$parser->brackets;
                continue;
            }

            // Only keywords are relevant here. Other parts of the query are
            // processed in the functions below.
            if ($token->type !== TokenType::Keyword) {
                if (($token->type !== TokenType::Comment) && ($token->type !== TokenType::Whitespace)) {
                    $parser->error('Unexpected token.', $token);
                }

                continue;
            }

            // Unions are parsed by the parser because they represent more than
            // one statement.
            if (
                ($token->keyword === 'UNION') ||
                ($token->keyword === 'UNION ALL') ||
                ($token->keyword === 'UNION DISTINCT') ||
                ($token->keyword === 'EXCEPT') ||
                ($token->keyword === 'INTERSECT')
            ) {
                break;
            }

            $lastIdx = $list->idx;

            // ON DUPLICATE KEY UPDATE ...
            // has to be parsed in parent statement (INSERT or REPLACE)
            // so look for it and break
            if ($token->value === 'ON') {
                ++$list->idx; // Skip ON

                // look for ON DUPLICATE KEY UPDATE
                $first = $list->getNextOfType(TokenType::Keyword);
                $second = $list->getNextOfType(TokenType::Keyword);
                $third = $list->getNextOfType(TokenType::Keyword);

                if (
                    $first && $second && $third
                    && $first->value === 'DUPLICATE'
                    && $second->value === 'KEY'
                    && $third->value === 'UPDATE'
                ) {
                    $list->idx = $lastIdx;
                    break;
                }
            }

            $list->idx = $lastIdx;

            /**
             * The name of the class that is used for parsing.
             */
            $class = null;

            /**
             * The name of the field where the result of the parsing is stored.
             */
            $field = null;

            /**
             * Parser's options.
             */
            $options = [];

            // Looking for duplicated clauses.
            if (
                is_string($token->value)
                && (
                    isset(Parser::KEYWORD_PARSERS[$token->value])
                    || (
                        isset(Parser::STATEMENT_PARSERS[$token->value])
                        && Parser::STATEMENT_PARSERS[$token->value] !== ''
                    )
                )
            ) {
                if (array_key_exists($token->value, $parsedClauses)) {
                    $parser->error('This type of clause was previously parsed.', $token);
                    break;
                }

                $parsedClauses[$token->value] = true;
            }

            // Checking if this is the beginning of a clause.
            // Fix Issue #221: As `truncate` is not a keyword,
            // but it might be the beginning of a statement of truncate,
            // so let the value use the keyword field for truncate type.
            $tokenValue = $token->keyword === 'TRUNCATE' ? $token->keyword : $token->value;
            if (is_string($tokenValue) && isset(Parser::KEYWORD_PARSERS[$tokenValue]) && $list->idx < $list->count) {
                $class = Parser::KEYWORD_PARSERS[$tokenValue]['class'];
                $field = Parser::KEYWORD_PARSERS[$tokenValue]['field'];
                if (isset(Parser::KEYWORD_PARSERS[$tokenValue]['options'])) {
                    $options = Parser::KEYWORD_PARSERS[$tokenValue]['options'];
                }
            }

            // Checking if this is the beginning of the statement.
            if (
                isset(Parser::STATEMENT_PARSERS[$token->keyword])
                && Parser::STATEMENT_PARSERS[$token->keyword] !== ''
            ) {
                if (static::$clauses !== [] && is_string($token->value) && ! isset(static::$clauses[$token->value])) {
                    // Some keywords (e.g. `SET`) may be the beginning of a
                    // statement and a clause.
                    // If such keyword was found, and it cannot be a clause of
                    // this statement it means it is a new statement, but no
                    // delimiter was found between them.
                    $parser->error(
                        'A new statement was found, but no delimiter between it and the previous one.',
                        $token,
                    );
                    break;
                }

                if (! $parsedOptions) {
                    if (! array_key_exists((string) $token->value, static::$statementOptions)) {
                        // Skipping keyword because if it is not a option.
                        ++$list->idx;
                    }

                    $this->options = OptionsArrays::parse($parser, $list, static::$statementOptions);
                    $parsedOptions = true;
                }
            } elseif ($class === null) {
                if ($token->value === 'WITH ROLLUP') {
                    // Handle group options in Select statement
                    $this->groupOptions = OptionsArrays::parse($parser, $list, self::STATEMENT_GROUP_OPTIONS);
                } elseif ($token->value === 'FOR UPDATE' || $token->value === 'LOCK IN SHARE MODE') {
                    // Handle special end options in Select statement
                    $this->endOptions = OptionsArrays::parse($parser, $list, self::STATEMENT_END_OPTIONS);
                } else {
                    // There is no parser for this keyword and isn't the beginning
                    // of a statement (so no options) either.
                    $parser->error('Unrecognized keyword.', $token);
                    continue;
                }
            }

            if ($class === null) {
                continue;
            }

            // Parsing this keyword.
            // We can't parse keyword at the end of statement
            if ($list->idx >= $list->count) {
                $parser->error('Keyword at end of statement.', $token);
                continue;
            }

            ++$list->idx; // Skipping keyword or last option.
            $this->$field = $class::parse($parser, $list, $options);
        }

        // This may be corrected by the parser.
        $this->last = --$list->idx; // Go back to last used token.
    }

    /**
     * Gets the clauses of this statement.
     *
     * @return array<string, array{non-empty-string, int-mask-of<Statement::ADD_*>}>
     */
    public function getClauses(): array
    {
        // This is a cheap fix for `SELECT` statements that contain `UNION`.
        // The `ORDER BY` and `LIMIT` clauses should be at the end of the
        // statement.
        if ($this->union !== []) {
            $clauses = static::$clauses;
            unset($clauses['ORDER BY'], $clauses['LIMIT']);
            $clauses['ORDER BY'] = [
                'ORDER BY',
                Statement::ADD_CLAUSE | Statement::ADD_KEYWORD,
            ];
            $clauses['LIMIT'] = [
                'LIMIT',
                Statement::ADD_CLAUSE | Statement::ADD_KEYWORD,
            ];

            return $clauses;
        }

        return static::$clauses;
    }

    /**
     * Gets a list of all aliases and their original names.
     *
     * @param string $database the name of the database
     *
     * @return array<string, array<string, array<string, array<string, array<string, string>|string|null>>|null>>
     */
    public function getAliases(string $database): array
    {
        if ($this->expr === [] || $this->from === []) {
            return [];
        }

        $retval = [];

        $tables = [];

        /**
         * Expressions that may contain aliases.
         * These are extracted from `FROM` and `JOIN` keywords.
         */
        $expressions = $this->from;

        // Adding expressions from JOIN.
        if (! empty($this->join)) {
            foreach ($this->join as $join) {
                $expressions[] = $join->expr;
            }
        }

        foreach ($expressions as $expr) {
            if (! isset($expr->table) || ($expr->table === '')) {
                continue;
            }

            $thisDb = isset($expr->database) && ($expr->database !== '') ?
                $expr->database : $database;

            if (! isset($retval[$thisDb])) {
                $retval[$thisDb] = [
                    'alias' => null,
                    'tables' => [],
                ];
            }

            if (! isset($retval[$thisDb]['tables'][$expr->table])) {
                $retval[$thisDb]['tables'][$expr->table] = [
                    'alias' => isset($expr->alias) && ($expr->alias !== '') ?
                        $expr->alias : null,
                    'columns' => [],
                ];
            }

            if (! isset($tables[$thisDb])) {
                $tables[$thisDb] = [];
            }

            $tables[$thisDb][$expr->alias] = $expr->table;
        }

        foreach ($this->expr as $expr) {
            if (! isset($expr->column, $expr->alias) || ($expr->column === '') || ($expr->alias === '')) {
                continue;
            }

            $thisDb = isset($expr->database) && ($expr->database !== '') ?
                $expr->database : $database;

            if (isset($expr->table) && ($expr->table !== '')) {
                $thisTable = $tables[$thisDb][$expr->table] ?? $expr->table;
                $retval[$thisDb]['tables'][$thisTable]['columns'][$expr->column] = $expr->alias;
            } else {
                foreach ($retval[$thisDb]['tables'] as &$table) {
                    $table['columns'][$expr->column] = $expr->alias;
                }
            }
        }

        return $retval;
    }
}
