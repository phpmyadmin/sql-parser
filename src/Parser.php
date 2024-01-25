<?php

declare(strict_types=1);

namespace PhpMyAdmin\SqlParser;

use Exception;
use PhpMyAdmin\SqlParser\Exceptions\ParserException;
use PhpMyAdmin\SqlParser\Statements\SelectStatement;
use PhpMyAdmin\SqlParser\Statements\TransactionStatement;

use function is_string;
use function strtoupper;

/**
 * Defines the parser of the library.
 *
 * This is one of the most important components, along with the lexer.
 *
 * Takes multiple tokens (contained in a Lexer instance) as input and builds a parse tree.
 */
class Parser
{
    /**
     * Whether errors should throw exceptions or just be stored.
     */
    private bool $strict = false;

    /**
     * List of errors that occurred during lexing.
     *
     * Usually, the lexing does not stop once an error occurred because that
     * error might be false positive or a partial result (even a bad one)
     * might be needed.
     *
     * @var Exception[]
     */
    public array $errors = [];

    /**
     * Array of classes that are used in parsing the SQL statements.
     *
     * @psalm-var array<string, class-string<Statement>|''>
     */
    public const STATEMENT_PARSERS = [
        // MySQL Utility Statements
        'DESCRIBE' => Statements\ExplainStatement::class,
        'DESC' => Statements\ExplainStatement::class,
        'EXPLAIN' => Statements\ExplainStatement::class,
        'FLUSH' => '',
        'GRANT' => '',
        'HELP' => '',
        'SET PASSWORD' => '',
        'STATUS' => '',
        'USE' => '',

        // Table Maintenance Statements
        // https://dev.mysql.com/doc/refman/5.7/en/table-maintenance-sql.html
        'ANALYZE' => Statements\AnalyzeStatement::class,
        'BACKUP' => Statements\BackupStatement::class,
        'CHECK' => Statements\CheckStatement::class,
        'CHECKSUM' => Statements\ChecksumStatement::class,
        'OPTIMIZE' => Statements\OptimizeStatement::class,
        'REPAIR' => Statements\RepairStatement::class,
        'RESTORE' => Statements\RestoreStatement::class,

        // Database Administration Statements
        // https://dev.mysql.com/doc/refman/5.7/en/sql-syntax-server-administration.html
        'SET' => Statements\SetStatement::class,
        'SHOW' => Statements\ShowStatement::class,

        // Data Definition Statements.
        // https://dev.mysql.com/doc/refman/5.7/en/sql-syntax-data-definition.html
        'ALTER' => Statements\AlterStatement::class,
        'CREATE' => Statements\CreateStatement::class,
        'DROP' => Statements\DropStatement::class,
        'RENAME' => Statements\RenameStatement::class,
        'TRUNCATE' => Statements\TruncateStatement::class,

        // Data Manipulation Statements.
        // https://dev.mysql.com/doc/refman/5.7/en/sql-syntax-data-manipulation.html
        'CALL' => Statements\CallStatement::class,
        'DELETE' => Statements\DeleteStatement::class,
        'DO' => '',
        'HANDLER' => '',
        'INSERT' => Statements\InsertStatement::class,
        'LOAD DATA' => Statements\LoadStatement::class,
        'REPLACE' => Statements\ReplaceStatement::class,
        'SELECT' => Statements\SelectStatement::class,
        'UPDATE' => Statements\UpdateStatement::class,
        'WITH' => Statements\WithStatement::class,

        // Prepared Statements.
        // https://dev.mysql.com/doc/refman/5.7/en/sql-syntax-prepared-statements.html
        'DEALLOCATE' => '',
        'EXECUTE' => '',
        'PREPARE' => '',

        // Transactional and Locking Statements
        // https://dev.mysql.com/doc/refman/5.7/en/commit.html
        'BEGIN' => Statements\TransactionStatement::class,
        'COMMIT' => Statements\TransactionStatement::class,
        'ROLLBACK' => Statements\TransactionStatement::class,
        'START TRANSACTION' => Statements\TransactionStatement::class,

        'PURGE' => Statements\PurgeStatement::class,

        // Lock statements
        // https://dev.mysql.com/doc/refman/5.7/en/lock-tables.html
        'LOCK' => Statements\LockStatement::class,
        'UNLOCK' => Statements\LockStatement::class,
    ];

    /**
     * Array of classes that are used in parsing SQL components.
     */
    public const KEYWORD_PARSERS = [
        // This is not a proper keyword and was added here to help the
        // builder.
        '_OPTIONS' => [
            'class' => Parsers\OptionsArrays::class,
            'field' => 'options',
        ],
        '_END_OPTIONS' => [
            'class' => Parsers\OptionsArrays::class,
            'field' => 'endOptions',
        ],
        '_GROUP_OPTIONS' => [
            'class' => Parsers\OptionsArrays::class,
            'field' => 'groupOptions',
        ],

        'INTERSECT' => [
            'class' => Parsers\UnionKeywords::class,
            'field' => 'union',
        ],
        'EXCEPT' => [
            'class' => Parsers\UnionKeywords::class,
            'field' => 'union',
        ],
        'UNION' => [
            'class' => Parsers\UnionKeywords::class,
            'field' => 'union',
        ],
        'UNION ALL' => [
            'class' => Parsers\UnionKeywords::class,
            'field' => 'union',
        ],
        'UNION DISTINCT' => [
            'class' => Parsers\UnionKeywords::class,
            'field' => 'union',
        ],

        // Actual clause parsers.
        'ALTER' => [
            'class' => Parsers\Expressions::class,
            'field' => 'table',
            'options' => ['parseField' => 'table'],
        ],
        'ANALYZE' => [
            'class' => Parsers\ExpressionArray::class,
            'field' => 'tables',
            'options' => ['parseField' => 'table'],
        ],
        'BACKUP' => [
            'class' => Parsers\ExpressionArray::class,
            'field' => 'tables',
            'options' => ['parseField' => 'table'],
        ],
        'CALL' => [
            'class' => Parsers\FunctionCalls::class,
            'field' => 'call',
        ],
        'CHECK' => [
            'class' => Parsers\ExpressionArray::class,
            'field' => 'tables',
            'options' => ['parseField' => 'table'],
        ],
        'CHECKSUM' => [
            'class' => Parsers\ExpressionArray::class,
            'field' => 'tables',
            'options' => ['parseField' => 'table'],
        ],
        'CROSS JOIN' => [
            'class' => Parsers\JoinKeywords::class,
            'field' => 'join',
        ],
        'DROP' => [
            'class' => Parsers\ExpressionArray::class,
            'field' => 'fields',
            'options' => ['parseField' => 'table'],
        ],
        'FORCE' => [
            'class' => Parsers\IndexHints::class,
            'field' => 'indexHints',
        ],
        'FROM' => [
            'class' => Parsers\ExpressionArray::class,
            'field' => 'from',
            'options' => ['field' => 'table'],
        ],
        'GROUP BY' => [
            'class' => Parsers\GroupKeywords::class,
            'field' => 'group',
        ],
        'HAVING' => [
            'class' => Parsers\Conditions::class,
            'field' => 'having',
        ],
        'IGNORE' => [
            'class' => Parsers\IndexHints::class,
            'field' => 'indexHints',
        ],
        'INTO' => [
            'class' => Parsers\IntoKeywords::class,
            'field' => 'into',
        ],
        'JOIN' => [
            'class' => Parsers\JoinKeywords::class,
            'field' => 'join',
        ],
        'LEFT JOIN' => [
            'class' => Parsers\JoinKeywords::class,
            'field' => 'join',
        ],
        'LEFT OUTER JOIN' => [
            'class' => Parsers\JoinKeywords::class,
            'field' => 'join',
        ],
        'ON' => [
            'class' => Parsers\Expressions::class,
            'field' => 'table',
            'options' => ['parseField' => 'table'],
        ],
        'RIGHT JOIN' => [
            'class' => Parsers\JoinKeywords::class,
            'field' => 'join',
        ],
        'RIGHT OUTER JOIN' => [
            'class' => Parsers\JoinKeywords::class,
            'field' => 'join',
        ],
        'INNER JOIN' => [
            'class' => Parsers\JoinKeywords::class,
            'field' => 'join',
        ],
        'FULL JOIN' => [
            'class' => Parsers\JoinKeywords::class,
            'field' => 'join',
        ],
        'FULL OUTER JOIN' => [
            'class' => Parsers\JoinKeywords::class,
            'field' => 'join',
        ],
        'NATURAL JOIN' => [
            'class' => Parsers\JoinKeywords::class,
            'field' => 'join',
        ],
        'NATURAL LEFT JOIN' => [
            'class' => Parsers\JoinKeywords::class,
            'field' => 'join',
        ],
        'NATURAL RIGHT JOIN' => [
            'class' => Parsers\JoinKeywords::class,
            'field' => 'join',
        ],
        'NATURAL LEFT OUTER JOIN' => [
            'class' => Parsers\JoinKeywords::class,
            'field' => 'join',
        ],
        'NATURAL RIGHT OUTER JOIN' => [
            'class' => Parsers\JoinKeywords::class,
            'field' => 'join',
        ],
        'STRAIGHT_JOIN' => [
            'class' => Parsers\JoinKeywords::class,
            'field' => 'join',
        ],
        'LIMIT' => [
            'class' => Parsers\Limits::class,
            'field' => 'limit',
        ],
        'OPTIMIZE' => [
            'class' => Parsers\ExpressionArray::class,
            'field' => 'tables',
            'options' => ['parseField' => 'table'],
        ],
        'ORDER BY' => [
            'class' => Parsers\OrderKeywords::class,
            'field' => 'order',
        ],
        'PARTITION' => [
            'class' => Parsers\ArrayObjs::class,
            'field' => 'partition',
        ],
        'PROCEDURE' => [
            'class' => Parsers\FunctionCalls::class,
            'field' => 'procedure',
        ],
        'RENAME' => [
            'class' => Parsers\RenameOperations::class,
            'field' => 'renames',
        ],
        'REPAIR' => [
            'class' => Parsers\ExpressionArray::class,
            'field' => 'tables',
            'options' => ['parseField' => 'table'],
        ],
        'RESTORE' => [
            'class' => Parsers\ExpressionArray::class,
            'field' => 'tables',
            'options' => ['parseField' => 'table'],
        ],
        'SET' => [
            'class' => Parsers\SetOperations::class,
            'field' => 'set',
        ],
        'SELECT' => [
            'class' => Parsers\ExpressionArray::class,
            'field' => 'expr',
        ],
        'TRUNCATE' => [
            'class' => Parsers\Expressions::class,
            'field' => 'table',
            'options' => ['parseField' => 'table'],
        ],
        'UPDATE' => [
            'class' => Parsers\ExpressionArray::class,
            'field' => 'tables',
            'options' => ['parseField' => 'table'],
        ],
        'USE' => [
            'class' => Parsers\IndexHints::class,
            'field' => 'indexHints',
        ],
        'VALUE' => [
            'class' => Parsers\Array2d::class,
            'field' => 'values',
        ],
        'VALUES' => [
            'class' => Parsers\Array2d::class,
            'field' => 'values',
        ],
        'WHERE' => [
            'class' => Parsers\Conditions::class,
            'field' => 'where',
        ],
    ];

    /**
     * The list of tokens that are parsed.
     */
    public TokensList|null $list = null;

    /**
     * List of statements parsed.
     *
     * @var Statement[]
     */
    public array $statements = [];

    /**
     * The number of opened brackets.
     */
    public int $brackets = 0;

    /**
     * @param string|UtfString|TokensList|null $list   the list of tokens to be parsed
     * @param bool                             $strict whether strict mode should be enabled or not
     */
    public function __construct(string|UtfString|TokensList|null $list = null, bool $strict = false)
    {
        if (Context::$keywords === []) {
            Context::load();
        }

        if (is_string($list) || ($list instanceof UtfString)) {
            $lexer = new Lexer($list, $strict);
            $this->list = $lexer->list;
        } elseif ($list instanceof TokensList) {
            $this->list = $list;
        }

        $this->strict = $strict;

        if ($list === null) {
            return;
        }

        $this->parse();
    }

    /**
     * Builds the parse trees.
     *
     * @throws ParserException
     */
    public function parse(): void
    {
        /**
         * Last transaction.
         */
        $lastTransaction = null;

        /**
         * Last parsed statement.
         */
        $lastStatement = null;

        /**
         * Union's type or false for no union.
         */
        $unionType = false;

        /**
         * The index of the last token from the last statement.
         */
        $prevLastIdx = -1;

        /**
         * The list of tokens.
         */
        $list = &$this->list;

        for (; $list->idx < $list->count; ++$list->idx) {
            /**
             * Token parsed at this moment.
             */
            $token = $list->tokens[$list->idx];

            // `DELIMITER` is not an actual statement and it requires
            // special handling.
            if (($token->type === TokenType::None) && (strtoupper($token->token) === 'DELIMITER')) {
                // Skipping to the end of this statement.
                $list->getNextOfType(TokenType::Delimiter);
                $prevLastIdx = $list->idx;
                continue;
            }

            // Counting the brackets around statements.
            if ($token->value === '(') {
                ++$this->brackets;
                continue;
            }

            // Statements can start with keywords only.
            // Comments, whitespaces, etc. are ignored.
            if ($token->type !== TokenType::Keyword) {
                if (
                    ($token->type !== TokenType::Comment)
                    && ($token->type !== TokenType::Whitespace)
                    && ($token->type !== TokenType::Operator) // `(` and `)`
                    && ($token->type !== TokenType::Delimiter)
                ) {
                    $this->error('Unexpected beginning of statement.', $token);
                }

                continue;
            }

            if (
                ($token->keyword === 'UNION') ||
                    ($token->keyword === 'UNION ALL') ||
                    ($token->keyword === 'UNION DISTINCT') ||
                    ($token->keyword === 'EXCEPT') ||
                    ($token->keyword === 'INTERSECT')
            ) {
                $unionType = $token->keyword;
                continue;
            }

            $lastIdx = $list->idx;
            $statementName = null;

            if ($token->keyword === 'ANALYZE') {
                ++$list->idx; // Skip ANALYZE

                $first = $list->getNextOfType(TokenType::Keyword);
                $second = $list->getNextOfType(TokenType::Keyword);

                // ANALYZE keyword can be an indication of two cases:
                // 1 - ANALYZE TABLE statements, in both MariaDB and MySQL
                // 2 - Explain statement, in case of MariaDB https://mariadb.com/kb/en/explain-analyze/
                // We need to point case 2 to use the EXPLAIN Parser.
                $statementName = 'EXPLAIN';
                if (($first && $first->keyword === 'TABLE') || ($second && $second->keyword === 'TABLE')) {
                    $statementName = 'ANALYZE';
                }

                $list->idx = $lastIdx;
            } elseif (empty(self::STATEMENT_PARSERS[$token->keyword])) {
                // Checking if it is a known statement that can be parsed.
                if (! isset(self::STATEMENT_PARSERS[$token->keyword])) {
                    // A statement is considered recognized if the parser
                    // is aware that it is a statement, but it does not have
                    // a parser for it yet.
                    $this->error('Unrecognized statement type.', $token);
                }

                // Skipping to the end of this statement.
                $list->getNextOfType(TokenType::Delimiter);
                $prevLastIdx = $list->idx;
                continue;
            }

            /**
             * The name of the class that is used for parsing.
             */
            $class = self::STATEMENT_PARSERS[$statementName ?? $token->keyword];

            /**
             * Processed statement.
             */
            $statement = new $class($this, $this->list);

            // The first token that is a part of this token is the next token
            // unprocessed by the previous statement.
            // There might be brackets around statements and this shouldn't
            // affect the parser
            $statement->first = $prevLastIdx + 1;

            // Storing the index of the last token parsed and updating the old
            // index.
            $statement->last = $list->idx;
            $prevLastIdx = $list->idx;

            // Handles unions.
            if (
                ! empty($unionType)
                && ($lastStatement instanceof SelectStatement)
                && ($statement instanceof SelectStatement)
            ) {
                /*
                 * This SELECT statement.
                 *
                 * @var SelectStatement $statement
                 */

                /*
                 * Last SELECT statement.
                 *
                 * @var SelectStatement $lastStatement
                 */
                $lastStatement->union[] = [
                    $unionType,
                    $statement,
                ];

                // if there are no no delimiting brackets, the `ORDER` and
                // `LIMIT` keywords actually belong to the first statement.
                $lastStatement->order = $statement->order;
                $lastStatement->limit = $statement->limit;
                $statement->order = [];
                $statement->limit = null;

                // The statement actually ends where the last statement in
                // union ends.
                $lastStatement->last = $statement->last;

                $unionType = false;

                // Validate clause order
                $statement->validateClauseOrder($this, $list);
                continue;
            }

            // Handles transactions.
            if ($statement instanceof TransactionStatement) {
                /*
                 * @var TransactionStatement
                 */
                if ($statement->type === TransactionStatement::TYPE_BEGIN) {
                    $lastTransaction = $statement;
                    $this->statements[] = $statement;
                } elseif ($statement->type === TransactionStatement::TYPE_END) {
                    if ($lastTransaction === null) {
                        // Even though an error occurred, the query is being
                        // saved.
                        $this->statements[] = $statement;
                        $this->error('No transaction was previously started.', $token);
                    } else {
                        $lastTransaction->end = $statement;
                    }

                    $lastTransaction = null;
                }

                // Validate clause order
                $statement->validateClauseOrder($this, $list);
                continue;
            }

            // Validate clause order
            $statement->validateClauseOrder($this, $list);

            // Finally, storing the statement.
            if ($lastTransaction !== null) {
                $lastTransaction->statements[] = $statement;
            } else {
                $this->statements[] = $statement;
            }

            $lastStatement = $statement;
        }
    }

    /**
     * Creates a new error log.
     *
     * @param string $msg   the error message
     * @param Token  $token the token that produced the error
     * @param int    $code  the code of the error
     *
     * @throws ParserException throws the exception, if strict mode is enabled.
     */
    public function error(string $msg, Token|null $token = null, int $code = 0): void
    {
        $error = new ParserException(
            Translator::gettext($msg),
            $token,
            $code,
        );

        if ($this->strict) {
            throw $error;
        }

        $this->errors[] = $error;
    }
}
