<?php

declare(strict_types=1);

namespace PhpMyAdmin\SqlParser;

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
class Parser extends Core
{
    /**
     * Array of classes that are used in parsing the SQL statements.
     *
     * @var array<string, string>
     * @psalm-var array<string, class-string<Statement>|''>
     */
    public static $statementParsers = [
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
     *
     * @var array<string, array<string, string|array<string, string>>>
     * @psalm-var array<string, array{
     *   class?: class-string<Component>,
     *   field?: non-empty-string,
     *   options?: array<string, string>
     * }>
     */
    public static $keywordParsers = [
        // This is not a proper keyword and was added here to help the
        // formatter.
        'PARTITION BY' => [],
        'SUBPARTITION BY' => [],

        // This is not a proper keyword and was added here to help the
        // builder.
        '_OPTIONS' => [
            'class' => Components\OptionsArray::class,
            'field' => 'options',
        ],
        '_END_OPTIONS' => [
            'class' => Components\OptionsArray::class,
            'field' => 'endOptions',
        ],

        'INTERSECT' => [
            'class' => Components\UnionKeyword::class,
            'field' => 'union',
        ],
        'EXCEPT' => [
            'class' => Components\UnionKeyword::class,
            'field' => 'union',
        ],
        'UNION' => [
            'class' => Components\UnionKeyword::class,
            'field' => 'union',
        ],
        'UNION ALL' => [
            'class' => Components\UnionKeyword::class,
            'field' => 'union',
        ],
        'UNION DISTINCT' => [
            'class' => Components\UnionKeyword::class,
            'field' => 'union',
        ],

        // Actual clause parsers.
        'ALTER' => [
            'class' => Components\Expression::class,
            'field' => 'table',
            'options' => ['parseField' => 'table'],
        ],
        'ANALYZE' => [
            'class' => Components\ExpressionArray::class,
            'field' => 'tables',
            'options' => ['parseField' => 'table'],
        ],
        'BACKUP' => [
            'class' => Components\ExpressionArray::class,
            'field' => 'tables',
            'options' => ['parseField' => 'table'],
        ],
        'CALL' => [
            'class' => Components\FunctionCall::class,
            'field' => 'call',
        ],
        'CHECK' => [
            'class' => Components\ExpressionArray::class,
            'field' => 'tables',
            'options' => ['parseField' => 'table'],
        ],
        'CHECKSUM' => [
            'class' => Components\ExpressionArray::class,
            'field' => 'tables',
            'options' => ['parseField' => 'table'],
        ],
        'CROSS JOIN' => [
            'class' => Components\JoinKeyword::class,
            'field' => 'join',
        ],
        'DROP' => [
            'class' => Components\ExpressionArray::class,
            'field' => 'fields',
            'options' => ['parseField' => 'table'],
        ],
        'FORCE' => [
            'class' => Components\IndexHint::class,
            'field' => 'index_hints',
        ],
        'FROM' => [
            'class' => Components\ExpressionArray::class,
            'field' => 'from',
            'options' => ['field' => 'table'],
        ],
        'GROUP BY' => [
            'class' => Components\GroupKeyword::class,
            'field' => 'group',
        ],
        'HAVING' => [
            'class' => Components\Condition::class,
            'field' => 'having',
        ],
        'IGNORE' => [
            'class' => Components\IndexHint::class,
            'field' => 'index_hints',
        ],
        'INTO' => [
            'class' => Components\IntoKeyword::class,
            'field' => 'into',
        ],
        'JOIN' => [
            'class' => Components\JoinKeyword::class,
            'field' => 'join',
        ],
        'LEFT JOIN' => [
            'class' => Components\JoinKeyword::class,
            'field' => 'join',
        ],
        'LEFT OUTER JOIN' => [
            'class' => Components\JoinKeyword::class,
            'field' => 'join',
        ],
        'ON' => [
            'class' => Components\Expression::class,
            'field' => 'table',
            'options' => ['parseField' => 'table'],
        ],
        'RIGHT JOIN' => [
            'class' => Components\JoinKeyword::class,
            'field' => 'join',
        ],
        'RIGHT OUTER JOIN' => [
            'class' => Components\JoinKeyword::class,
            'field' => 'join',
        ],
        'INNER JOIN' => [
            'class' => Components\JoinKeyword::class,
            'field' => 'join',
        ],
        'FULL JOIN' => [
            'class' => Components\JoinKeyword::class,
            'field' => 'join',
        ],
        'FULL OUTER JOIN' => [
            'class' => Components\JoinKeyword::class,
            'field' => 'join',
        ],
        'NATURAL JOIN' => [
            'class' => Components\JoinKeyword::class,
            'field' => 'join',
        ],
        'NATURAL LEFT JOIN' => [
            'class' => Components\JoinKeyword::class,
            'field' => 'join',
        ],
        'NATURAL RIGHT JOIN' => [
            'class' => Components\JoinKeyword::class,
            'field' => 'join',
        ],
        'NATURAL LEFT OUTER JOIN' => [
            'class' => Components\JoinKeyword::class,
            'field' => 'join',
        ],
        'NATURAL RIGHT OUTER JOIN' => [
            'class' => Components\JoinKeyword::class,
            'field' => 'join',
        ],
        'STRAIGHT_JOIN' => [
            'class' => Components\JoinKeyword::class,
            'field' => 'join',
        ],
        'LIMIT' => [
            'class' => Components\Limit::class,
            'field' => 'limit',
        ],
        'OPTIMIZE' => [
            'class' => Components\ExpressionArray::class,
            'field' => 'tables',
            'options' => ['parseField' => 'table'],
        ],
        'ORDER BY' => [
            'class' => Components\OrderKeyword::class,
            'field' => 'order',
        ],
        'PARTITION' => [
            'class' => Components\ArrayObj::class,
            'field' => 'partition',
        ],
        'PROCEDURE' => [
            'class' => Components\FunctionCall::class,
            'field' => 'procedure',
        ],
        'RENAME' => [
            'class' => Components\RenameOperation::class,
            'field' => 'renames',
        ],
        'REPAIR' => [
            'class' => Components\ExpressionArray::class,
            'field' => 'tables',
            'options' => ['parseField' => 'table'],
        ],
        'RESTORE' => [
            'class' => Components\ExpressionArray::class,
            'field' => 'tables',
            'options' => ['parseField' => 'table'],
        ],
        'SET' => [
            'class' => Components\SetOperation::class,
            'field' => 'set',
        ],
        'SELECT' => [
            'class' => Components\ExpressionArray::class,
            'field' => 'expr',
        ],
        'TRUNCATE' => [
            'class' => Components\Expression::class,
            'field' => 'table',
            'options' => ['parseField' => 'table'],
        ],
        'UPDATE' => [
            'class' => Components\ExpressionArray::class,
            'field' => 'tables',
            'options' => ['parseField' => 'table'],
        ],
        'USE' => [
            'class' => Components\IndexHint::class,
            'field' => 'index_hints',
        ],
        'VALUE' => [
            'class' => Components\Array2d::class,
            'field' => 'values',
        ],
        'VALUES' => [
            'class' => Components\Array2d::class,
            'field' => 'values',
        ],
        'WHERE' => [
            'class' => Components\Condition::class,
            'field' => 'where',
        ],
    ];

    /**
     * The list of tokens that are parsed.
     *
     * @var TokensList|null
     */
    public $list;

    /**
     * List of statements parsed.
     *
     * @var Statement[]
     */
    public $statements = [];

    /**
     * The number of opened brackets.
     *
     * @var int
     */
    public $brackets = 0;

    /**
     * @param string|UtfString|TokensList|null $list   the list of tokens to be parsed
     * @param bool                             $strict whether strict mode should be enabled or not
     */
    public function __construct($list = null, $strict = false)
    {
        parent::__construct();

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
     * @return void
     *
     * @throws ParserException
     */
    public function parse()
    {
        /**
         * Last transaction.
         *
         * @var TransactionStatement
         */
        $lastTransaction = null;

        /**
         * Last parsed statement.
         *
         * @var Statement
         */
        $lastStatement = null;

        /**
         * Union's type or false for no union.
         *
         * @var bool|string
         */
        $unionType = false;

        /**
         * The index of the last token from the last statement.
         *
         * @var int
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
            if (($token->type === Token::TYPE_NONE) && (strtoupper($token->token) === 'DELIMITER')) {
                // Skipping to the end of this statement.
                $list->getNextOfType(Token::TYPE_DELIMITER);
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
            if ($token->type !== Token::TYPE_KEYWORD) {
                if (
                    ($token->type !== Token::TYPE_COMMENT)
                    && ($token->type !== Token::TYPE_WHITESPACE)
                    && ($token->type !== Token::TYPE_OPERATOR) // `(` and `)`
                    && ($token->type !== Token::TYPE_DELIMITER)
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

            // Checking if it is a known statement that can be parsed.
            if (empty(static::$statementParsers[$token->keyword])) {
                if (! isset(static::$statementParsers[$token->keyword])) {
                    // A statement is considered recognized if the parser
                    // is aware that it is a statement, but it does not have
                    // a parser for it yet.
                    $this->error('Unrecognized statement type.', $token);
                }

                // Skipping to the end of this statement.
                $list->getNextOfType(Token::TYPE_DELIMITER);
                $prevLastIdx = $list->idx;
                continue;
            }

            /**
             * The name of the class that is used for parsing.
             *
             * @var string
             */
            $class = static::$statementParsers[$token->keyword];

            /**
             * Processed statement.
             *
             * @var Statement
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
     * @return void
     *
     * @throws ParserException throws the exception, if strict mode is enabled.
     */
    public function error($msg, ?Token $token = null, $code = 0)
    {
        $error = new ParserException(
            Translator::gettext($msg),
            $token,
            $code
        );
        parent::error($error);
    }
}
