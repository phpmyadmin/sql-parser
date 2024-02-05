<?php

declare(strict_types=1);

namespace PhpMyAdmin\SqlParser\Utils;

use PhpMyAdmin\SqlParser\Lexer;
use PhpMyAdmin\SqlParser\Parser;
use PhpMyAdmin\SqlParser\Statement;
use PhpMyAdmin\SqlParser\Statements\AlterStatement;
use PhpMyAdmin\SqlParser\Statements\AnalyzeStatement;
use PhpMyAdmin\SqlParser\Statements\CallStatement;
use PhpMyAdmin\SqlParser\Statements\CheckStatement;
use PhpMyAdmin\SqlParser\Statements\ChecksumStatement;
use PhpMyAdmin\SqlParser\Statements\CreateStatement;
use PhpMyAdmin\SqlParser\Statements\DeleteStatement;
use PhpMyAdmin\SqlParser\Statements\DropStatement;
use PhpMyAdmin\SqlParser\Statements\ExplainStatement;
use PhpMyAdmin\SqlParser\Statements\InsertStatement;
use PhpMyAdmin\SqlParser\Statements\LoadStatement;
use PhpMyAdmin\SqlParser\Statements\OptimizeStatement;
use PhpMyAdmin\SqlParser\Statements\RenameStatement;
use PhpMyAdmin\SqlParser\Statements\RepairStatement;
use PhpMyAdmin\SqlParser\Statements\ReplaceStatement;
use PhpMyAdmin\SqlParser\Statements\SelectStatement;
use PhpMyAdmin\SqlParser\Statements\SetStatement;
use PhpMyAdmin\SqlParser\Statements\ShowStatement;
use PhpMyAdmin\SqlParser\Statements\TruncateStatement;
use PhpMyAdmin\SqlParser\Statements\UpdateStatement;
use PhpMyAdmin\SqlParser\TokensList;
use PhpMyAdmin\SqlParser\TokenType;

use function array_flip;
use function array_keys;
use function count;
use function in_array;
use function is_string;
use function trim;

/**
 * Statement utilities.
 */
class Query
{
    /**
     * Functions that set the flag `is_func`.
     *
     * @var string[]
     */
    public static array $functions = [
        'SUM',
        'AVG',
        'STD',
        'STDDEV',
        'MIN',
        'MAX',
        'BIT_OR',
        'BIT_AND',
    ];

    /**
     * Gets an array with flags select statement has.
     *
     * @param SelectStatement $statement the statement to be processed
     * @param StatementFlags  $flags     flags set so far
     */
    private static function getFlagsSelect(SelectStatement $statement, StatementFlags $flags): void
    {
        $flags->queryType = StatementType::Select;
        /** @psalm-suppress DeprecatedProperty */
        $flags->isSelect = true;

        if ($statement->from !== []) {
            $flags->selectFrom = true;
        }

        if ($statement->options->has('DISTINCT')) {
            $flags->distinct = true;
        }

        if (! empty($statement->group) || ! empty($statement->having)) {
            $flags->isGroup = true;
        }

        if (! empty($statement->into) && ($statement->into->type === 'OUTFILE')) {
            $flags->isExport = true;
        }

        $expressions = $statement->expr;
        if (! empty($statement->join)) {
            foreach ($statement->join as $join) {
                $expressions[] = $join->expr;
            }
        }

        foreach ($expressions as $expr) {
            if (! empty($expr->function)) {
                if ($expr->function === 'COUNT') {
                    $flags->isCount = true;
                } elseif (in_array($expr->function, static::$functions)) {
                    $flags->isFunc = true;
                }
            }

            if (empty($expr->subquery)) {
                continue;
            }

            $flags->isSubQuery = true;
        }

        if (! empty($statement->procedure) && ($statement->procedure->name === 'ANALYSE')) {
            $flags->isAnalyse = true;
        }

        if (! empty($statement->group)) {
            $flags->group = true;
        }

        if (! empty($statement->having)) {
            $flags->having = true;
        }

        if ($statement->union !== []) {
            $flags->union = true;
        }

        if (empty($statement->join)) {
            return;
        }

        $flags->join = true;
    }

    /**
     * Gets an array with flags this statement has.
     *
     * @param Statement|null $statement the statement to be processed
     */
    public static function getFlags(Statement|null $statement): StatementFlags
    {
        $flags = new StatementFlags();

        if ($statement instanceof AlterStatement) {
            $flags->queryType = StatementType::Alter;
            $flags->reload = true;
        } elseif ($statement instanceof CreateStatement) {
            $flags->queryType = StatementType::Create;
            $flags->reload = true;
        } elseif ($statement instanceof AnalyzeStatement) {
            $flags->queryType = StatementType::Analyze;
            $flags->isMaint = true;
        } elseif ($statement instanceof CheckStatement) {
            $flags->queryType = StatementType::Check;
            $flags->isMaint = true;
        } elseif ($statement instanceof ChecksumStatement) {
            $flags->queryType = StatementType::Checksum;
            $flags->isMaint = true;
        } elseif ($statement instanceof OptimizeStatement) {
            $flags->queryType = StatementType::Optimize;
            $flags->isMaint = true;
        } elseif ($statement instanceof RepairStatement) {
            $flags->queryType = StatementType::Repair;
            $flags->isMaint = true;
        } elseif ($statement instanceof CallStatement) {
            $flags->queryType = StatementType::Call;
            $flags->isProcedure = true;
        } elseif ($statement instanceof DeleteStatement) {
            $flags->queryType = StatementType::Delete;
            /** @psalm-suppress DeprecatedProperty */
            $flags->isDelete = true;
            $flags->isAffected = true;
        } elseif ($statement instanceof DropStatement) {
            $flags->queryType = StatementType::Drop;
            $flags->reload = true;

            if ($statement->options->has('DATABASE') || $statement->options->has('SCHEMA')) {
                $flags->dropDatabase = true;
            }
        } elseif ($statement instanceof ExplainStatement) {
            $flags->queryType = StatementType::Explain;
            /** @psalm-suppress DeprecatedProperty */
            $flags->isExplain = true;
        } elseif ($statement instanceof InsertStatement) {
            $flags->queryType = StatementType::Insert;
            $flags->isAffected = true;
            $flags->isInsert = true;
        } elseif ($statement instanceof LoadStatement) {
            $flags->queryType = StatementType::Load;
            $flags->isAffected = true;
            $flags->isInsert = true;
        } elseif ($statement instanceof ReplaceStatement) {
            $flags->queryType = StatementType::Replace;
            $flags->isAffected = true;
            /** @psalm-suppress DeprecatedProperty */
            $flags->isReplace = true;
            $flags->isInsert = true;
        } elseif ($statement instanceof SelectStatement) {
            self::getFlagsSelect($statement, $flags);
        } elseif ($statement instanceof ShowStatement) {
            $flags->queryType = StatementType::Show;
            /** @psalm-suppress DeprecatedProperty */
            $flags->isShow = true;
        } elseif ($statement instanceof UpdateStatement) {
            $flags->queryType = StatementType::Update;
            $flags->isAffected = true;
        } elseif ($statement instanceof SetStatement) {
            $flags->queryType = StatementType::Set;
        }

        if (
            ($statement instanceof SelectStatement)
            || ($statement instanceof UpdateStatement)
            || ($statement instanceof DeleteStatement)
        ) {
            if (! empty($statement->limit)) {
                $flags->limit = true;
            }

            if (! empty($statement->order)) {
                $flags->order = true;
            }
        }

        return $flags;
    }

    /**
     * Parses a query and gets all information about it.
     *
     * @param string $query the query to be parsed
     */
    public static function getAll(string $query): StatementInfo
    {
        $parser = new Parser($query);

        if ($parser->statements === []) {
            return new StatementInfo($parser, null, static::getFlags(null), [], []);
        }

        $statement = $parser->statements[0];
        $flags = static::getFlags($statement);
        $selectTables = [];
        $selectExpressions = [];

        if ($statement instanceof SelectStatement) {
            // Finding tables' aliases and their associated real names.
            $tableAliases = [];
            foreach ($statement->from as $expr) {
                if (! isset($expr->table, $expr->alias) || ($expr->table === '') || ($expr->alias === '')) {
                    continue;
                }

                $tableAliases[$expr->alias] = [
                    $expr->table,
                    $expr->database ?? null,
                ];
            }

            // Trying to find selected tables only from the select expression.
            // Sometimes, this is not possible because the tables aren't defined
            // explicitly (e.g. SELECT * FROM film, SELECT film_id FROM film).
            foreach ($statement->expr as $expr) {
                if (isset($expr->table) && ($expr->table !== '')) {
                    if (isset($tableAliases[$expr->table])) {
                        $arr = $tableAliases[$expr->table];
                    } else {
                        $arr = [
                            $expr->table,
                            isset($expr->database) && ($expr->database !== '') ?
                                $expr->database : null,
                        ];
                    }

                    if (! in_array($arr, $selectTables)) {
                        $selectTables[] = $arr;
                    }
                } else {
                    $selectExpressions[] = $expr->expr;
                }
            }

            // If no tables names were found in the SELECT clause or if there
            // are expressions like * or COUNT(*), etc. tables names should be
            // extracted from the FROM clause.
            if ($selectTables === []) {
                foreach ($statement->from as $expr) {
                    if (! isset($expr->table) || ($expr->table === '')) {
                        continue;
                    }

                    $arr = [
                        $expr->table,
                        isset($expr->database) && ($expr->database !== '') ?
                            $expr->database : null,
                    ];
                    if (in_array($arr, $selectTables)) {
                        continue;
                    }

                    $selectTables[] = $arr;
                }
            }
        }

        return new StatementInfo($parser, $statement, $flags, $selectTables, $selectExpressions);
    }

    /**
     * Gets a list of all tables used in this statement.
     *
     * @param Statement $statement statement to be scanned
     *
     * @return array<int, string>
     */
    public static function getTables(Statement $statement): array
    {
        $expressions = [];

        if (($statement instanceof InsertStatement) || ($statement instanceof ReplaceStatement)) {
            $expressions = [$statement->into->dest];
        } elseif ($statement instanceof UpdateStatement) {
            $expressions = $statement->tables;
        } elseif (($statement instanceof SelectStatement) || ($statement instanceof DeleteStatement)) {
            $expressions = $statement->from;
        } elseif (($statement instanceof AlterStatement) || ($statement instanceof TruncateStatement)) {
            $expressions = [$statement->table];
        } elseif ($statement instanceof DropStatement) {
            if (! $statement->options->has('TABLE')) {
                // No tables are dropped.
                return [];
            }

            $expressions = $statement->fields;
        } elseif ($statement instanceof RenameStatement) {
            foreach ($statement->renames as $rename) {
                $expressions[] = $rename->old;
            }
        }

        $ret = [];
        foreach ($expressions as $expr) {
            if (empty($expr->table)) {
                continue;
            }

            $expr->expr = null; // Force rebuild.
            $expr->alias = null; // Aliases are not required.
            $ret[] = $expr->build();
        }

        return $ret;
    }

    /**
     * Gets a specific clause.
     *
     * @param Statement  $statement the parsed query that has to be modified
     * @param TokensList $list      the list of tokens
     * @param string     $clause    the clause to be returned
     * @param int|string $type      The type of the search.
     *                              If int,
     *                              -1 for everything that was before
     *                              0 only for the clause
     *                              1 for everything after
     *                              If string, the name of the first clause that
     *                              should not be included.
     * @param bool       $skipFirst whether to skip the first keyword in clause
     */
    public static function getClause(
        Statement $statement,
        TokensList $list,
        string $clause,
        int|string $type = 0,
        bool $skipFirst = true,
    ): string {
        /**
         * The index of the current clause.
         */
        $currIdx = 0;

        /**
         * The count of brackets.
         * We keep track of them so we won't insert the clause in a subquery.
         */
        $brackets = 0;

        /**
         * The string to be returned.
         */
        $ret = '';

        /**
         * The clauses of this type of statement and their index.
         */
        $clauses = array_flip(array_keys($statement->getClauses()));

        /**
         * Lexer used for lexing the clause.
         */
        $lexer = new Lexer($clause);

        /**
         * The type of this clause.
         */
        $clauseType = $lexer->list->getNextOfType(TokenType::Keyword)->keyword;

        /**
         * The index of this clause.
         */
        $clauseIdx = $clauses[$clauseType] ?? -1;

        $firstClauseIdx = $clauseIdx;
        $lastClauseIdx = $clauseIdx;

        // Determining the behavior of this function.
        if ($type === -1) {
            $firstClauseIdx = -1; // Something small enough.
            $lastClauseIdx = $clauseIdx - 1;
        } elseif ($type === 1) {
            $firstClauseIdx = $clauseIdx + 1;
            $lastClauseIdx = 10000; // Something big enough.
        } elseif (is_string($type) && isset($clauses[$type])) {
            if ($clauses[$type] > $clauseIdx) {
                $firstClauseIdx = $clauseIdx + 1;
                $lastClauseIdx = $clauses[$type] - 1;
            } else {
                $firstClauseIdx = $clauses[$type] + 1;
                $lastClauseIdx = $clauseIdx - 1;
            }
        }

        // This option is unavailable for multiple clauses.
        if ($type !== 0) {
            $skipFirst = false;
        }

        for ($i = $statement->first; $i <= $statement->last; ++$i) {
            $token = $list->tokens[$i];

            if ($token->type === TokenType::Comment) {
                continue;
            }

            if ($token->type === TokenType::Operator) {
                if ($token->value === '(') {
                    ++$brackets;
                } elseif ($token->value === ')') {
                    --$brackets;
                }
            }

            if ($brackets === 0) {
                // Checking if the section was changed.
                if (
                    ($token->type === TokenType::Keyword)
                    && isset($clauses[$token->keyword])
                    && ($clauses[$token->keyword] >= $currIdx)
                ) {
                    $currIdx = $clauses[$token->keyword];
                    if ($skipFirst && ($currIdx === $clauseIdx)) {
                        // This token is skipped (not added to the old
                        // clause) because it will be replaced.
                        continue;
                    }
                }
            }

            if (($firstClauseIdx > $currIdx) || ($currIdx > $lastClauseIdx)) {
                continue;
            }

            $ret .= $token->token;
        }

        return trim($ret);
    }

    /**
     * Builds a query by rebuilding the statement from the tokens list supplied
     * and replaces a clause.
     *
     * It is a very basic version of a query builder.
     *
     * @param Statement  $statement the parsed query that has to be modified
     * @param TokensList $list      the list of tokens
     * @param string     $old       The type of the clause that should be
     *                              replaced. This can be an entire clause.
     * @param string     $new       The new clause. If this parameter is omitted
     *                              it is considered to be equal with `$old`.
     * @param bool       $onlyType  whether only the type of the clause should
     *                              be replaced or the entire clause
     */
    public static function replaceClause(
        Statement $statement,
        TokensList $list,
        string $old,
        string|null $new = null,
        bool $onlyType = false,
    ): string {
        // TODO: Update the tokens list and the statement.

        if ($new === null) {
            $new = $old;
        }

        if ($onlyType) {
            return static::getClause($statement, $list, $old, -1, false) . ' ' .
                $new . ' ' . static::getClause($statement, $list, $old, 0) . ' ' .
                static::getClause($statement, $list, $old, 1, false);
        }

        return static::getClause($statement, $list, $old, -1, false) . ' ' .
            $new . ' ' . static::getClause($statement, $list, $old, 1, false);
    }

    /**
     * Builds a query by rebuilding the statement from the tokens list supplied
     * and replaces multiple clauses.
     *
     * @param Statement                      $statement the parsed query that has to be modified
     * @param TokensList                     $list      the list of tokens
     * @param array<int, array<int, string>> $ops       Clauses to be replaced. Contains multiple
     *                              arrays having two values: [$old, $new].
     *                              Clauses must be sorted.
     */
    public static function replaceClauses(Statement $statement, TokensList $list, array $ops): string
    {
        $count = count($ops);

        // Nothing to do.
        if ($count === 0) {
            return '';
        }

        /**
         * Value to be returned.
         */
        $ret = '';

        // If there is only one clause, `replaceClause()` should be used.
        if ($count === 1) {
            return static::replaceClause($statement, $list, $ops[0][0], $ops[0][1]);
        }

        // Adding everything before first replacement.
        $ret .= static::getClause($statement, $list, $ops[0][0], -1) . ' ';

        // Doing replacements.
        foreach ($ops as $i => $clause) {
            $ret .= $clause[1] . ' ';

            // Adding everything between this and next replacement.
            if ($i + 1 === $count) {
                continue;
            }

            $ret .= static::getClause($statement, $list, $clause[0], $ops[$i + 1][0]) . ' ';
        }

        // Adding everything after the last replacement.
        return $ret . static::getClause($statement, $list, $ops[$count - 1][0], 1);
    }

    /**
     * Gets the first full statement in the query.
     *
     * @param string $query     the query to be analyzed
     * @param string $delimiter the delimiter to be used
     *
     * @return array<int, string|null> array containing the first full query,
     *                                 the remaining part of the query and the last delimiter
     * @psalm-return array{string|null, string, string|null}
     */
    public static function getFirstStatement(string $query, string|null $delimiter = null): array
    {
        $lexer = new Lexer($query, false, $delimiter);
        $list = $lexer->list;

        /**
         * Whether a full statement was found.
         */
        $fullStatement = false;

        /**
         * The first full statement.
         */
        $statement = '';

        for ($list->idx = 0; $list->idx < $list->count; ++$list->idx) {
            $token = $list->tokens[$list->idx];

            if ($token->type === TokenType::Comment) {
                continue;
            }

            $statement .= $token->token;

            if (($token->type === TokenType::Delimiter) && ! empty($token->token)) {
                $delimiter = $token->token;
                $fullStatement = true;
                break;
            }
        }

        // No statement was found so we return the entire query as being the
        // remaining part.
        if (! $fullStatement) {
            return [
                null,
                $query,
                $delimiter,
            ];
        }

        // At least one query was found so we have to build the rest of the
        // remaining query.
        $query = '';
        for (++$list->idx; $list->idx < $list->count; ++$list->idx) {
            $query .= $list->tokens[$list->idx]->token;
        }

        return [
            trim($statement),
            $query,
            $delimiter,
        ];
    }

    /**
     * Gets a starting offset of a specific clause.
     *
     * @param Statement  $statement the parsed query that has to be modified
     * @param TokensList $list      the list of tokens
     * @param string     $clause    the clause to be returned
     */
    public static function getClauseStartOffset(Statement $statement, TokensList $list, string $clause): int
    {
        /**
         * The count of brackets.
         * We keep track of them so we won't insert the clause in a subquery.
         */
        $brackets = 0;

        /**
         * The clauses of this type of statement and their index.
         */
        $clauses = array_flip(array_keys($statement->getClauses()));

        for ($i = $statement->first; $i <= $statement->last; ++$i) {
            $token = $list->tokens[$i];

            if ($token->type === TokenType::Comment) {
                continue;
            }

            if ($token->type === TokenType::Operator) {
                if ($token->value === '(') {
                    ++$brackets;
                } elseif ($token->value === ')') {
                    --$brackets;
                }
            }

            if ($brackets !== 0) {
                continue;
            }

            if (
                ($token->type === TokenType::Keyword)
                && isset($clauses[$token->keyword])
                && ($clause === $token->keyword)
            ) {
                return $i;
            }

            if ($token->keyword === 'UNION') {
                return -1;
            }
        }

        return -1;
    }
}
