<?php

declare(strict_types=1);

namespace PhpMyAdmin\SqlParser;

use AllowDynamicProperties;
use PhpMyAdmin\SqlParser\Components\OptionsArray;
use PhpMyAdmin\SqlParser\Exceptions\ParserException;
use PhpMyAdmin\SqlParser\Parsers\OptionsArrays;
use PhpMyAdmin\SqlParser\Statements\SelectStatement;
use PhpMyAdmin\SqlParser\Statements\SetStatement;
use PhpMyAdmin\SqlParser\Utils\Query;
use Stringable;

use function array_flip;
use function array_key_exists;
use function array_keys;
use function is_array;
use function is_string;
use function str_contains;
use function strtoupper;
use function trim;

/**
 * The result of the parser is an array of statements are extensions of the class defined here.
 *
 * A statement represents the result of parsing the lexemes.
 *
 * Abstract statement definition.
 */
#[AllowDynamicProperties]
abstract class Statement implements Stringable
{
    /**
     * Options for this statement.
     *
     * The option would be the key and the value can be an integer or an array.
     *
     * The integer represents only the index used.
     *
     * The array may have two keys: `0` is used to represent the index used and
     * `1` is the type of the option (which may be 'var' or 'var='). Both
     * options mean they expect a value after the option (e.g. `A = B` or `A B`,
     * in which case `A` is the key and `B` is the value). The only difference
     * is in the building process. `var` options are built as `A B` and  `var=`
     * options are built as `A = B`
     *
     * Two options that can be used together must have different values for
     * indexes, else, when they will be used together, an error will occur.
     *
     * @var array<string, int|array<int, int|string>>
     * @psalm-var array<string, (positive-int|array{positive-int, ('var'|'var='|'expr'|'expr=')})>
     */
    public static array $statementOptions = [];

    protected const ADD_CLAUSE = 1;
    protected const ADD_KEYWORD = 2;

    /**
     * The clauses of this statement, in order.
     *
     * @var array<string, array{non-empty-string, int-mask-of<self::ADD_*>}>
     */
    public static array $clauses = [];

    /**
     * The options of this query.
     *
     * @see Statement::$statementOptions
     */
    public OptionsArray|null $options = null;

    /**
     * The index of the first token used in this statement.
     */
    public int|null $first = null;

    /**
     * The index of the last token used in this statement.
     */
    public int|null $last = null;

    /**
     * @param Parser|null     $parser the instance that requests parsing
     * @param TokensList|null $list   the list of tokens to be parsed
     *
     * @throws ParserException
     */
    public function __construct(Parser|null $parser = null, TokensList|null $list = null)
    {
        if (($parser === null) || ($list === null)) {
            return;
        }

        $this->parse($parser, $list);
    }

    /**
     * Builds the string representation of this statement.
     */
    public function build(): string
    {
        /**
         * Query to be returned.
         */
        $query = '';

        /**
         * Clauses which were built already.
         *
         * It is required to keep track of built clauses because some fields,
         * for example `join` is used by multiple clauses (`JOIN`, `LEFT JOIN`,
         * `LEFT OUTER JOIN`, etc.). The same happens for `VALUE` and `VALUES`.
         *
         * A clause is considered built just after fields' value
         * (`$this->field`) was used in building.
         */
        $built = [];

        foreach ($this->getClauses() as [$name, $type]) {
            /**
             * The name of the field that is used as source for the builder.
             * Same field is used to store the result of parsing.
             */
            $field = Parser::KEYWORD_PARSERS[$name]['field'];

            // The field is empty, there is nothing to be built.
            if (empty($this->$field)) {
                continue;
            }

            // Checking if this field was already built.
            if ($type & self::ADD_CLAUSE) {
                if (! empty($built[$field])) {
                    continue;
                }

                $built[$field] = true;
            }

            // Checking if the name of the clause should be added.
            if ($type & self::ADD_KEYWORD) {
                $query = trim($query) . ' ' . $name;
            }

            // Checking if the result of the builder should be added.
            if (! ($type & self::ADD_CLAUSE)) {
                continue;
            }

            if (is_array($this->$field)) {
                $class = Parser::KEYWORD_PARSERS[$name]['class'];
                $query = trim($query) . ' ' . $class::buildAll($this->$field);
            } else {
                $query = trim($query) . ' ' . $this->$field->build();
            }
        }

        return $query;
    }

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
            if ($this instanceof SelectStatement && $token->value === 'ON') {
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
                if ($this instanceof SelectStatement && $token->value === 'WITH ROLLUP') {
                    // Handle group options in Select statement
                    $this->groupOptions = OptionsArrays::parse(
                        $parser,
                        $list,
                        SelectStatement::STATEMENT_GROUP_OPTIONS,
                    );
                } elseif (
                    $this instanceof SelectStatement
                    && ($token->value === 'FOR UPDATE'
                        || $token->value === 'LOCK IN SHARE MODE')
                ) {
                    // Handle special end options in Select statement
                    $this->endOptions = OptionsArrays::parse($parser, $list, SelectStatement::STATEMENT_END_OPTIONS);
                } elseif (
                    $this instanceof SetStatement
                    && ($token->value === 'COLLATE'
                        || $token->value === 'DEFAULT')
                ) {
                    // Handle special end options in SET statement
                    $this->endOptions = OptionsArrays::parse($parser, $list, SetStatement::STATEMENT_END_OPTIONS);
                } else {
                    // There is no parser for this keyword and isn't the beginning
                    // of a statement (so no options) either.
                    $parser->error('Unrecognized keyword.', $token);
                    continue;
                }
            }

            $this->before($parser, $list, $token);

            // Parsing this keyword.
            if ($class !== null) {
                // We can't parse keyword at the end of statement
                if ($list->idx >= $list->count) {
                    $parser->error('Keyword at end of statement.', $token);
                    continue;
                }

                ++$list->idx; // Skipping keyword or last option.
                $this->$field = $class::parse($parser, $list, $options);
            }

            $this->after($parser, $list, $token);
        }

        // This may be corrected by the parser.
        $this->last = --$list->idx; // Go back to last used token.
    }

    /**
     * Function called before the token is processed.
     *
     * @param Parser     $parser the instance that requests parsing
     * @param TokensList $list   the list of tokens to be parsed
     * @param Token      $token  the token that is being parsed
     */
    public function before(Parser $parser, TokensList $list, Token $token): void
    {
    }

    /**
     * Function called after the token was processed.
     *
     * @param Parser     $parser the instance that requests parsing
     * @param TokensList $list   the list of tokens to be parsed
     * @param Token      $token  the token that is being parsed
     */
    public function after(Parser $parser, TokensList $list, Token $token): void
    {
    }

    /**
     * Gets the clauses of this statement.
     *
     * @return array<string, array{non-empty-string, int-mask-of<Statement::ADD_*>}>
     */
    public function getClauses(): array
    {
        return static::$clauses;
    }

    /**
     * Builds the string representation of this statement.
     *
     * @see static::build
     */
    public function __toString(): string
    {
        return $this->build();
    }

    /**
     * Validates the order of the clauses in parsed statement
     * Ideally this should be called after successfully
     * completing the parsing of each statement.
     *
     * @param Parser     $parser the instance that requests parsing
     * @param TokensList $list   the list of tokens to be parsed
     *
     * @throws ParserException
     */
    public function validateClauseOrder(Parser $parser, TokensList $list): bool
    {
        $clauses = array_flip(array_keys($this->getClauses()));

        if ($clauses === []) {
            return true;
        }

        $minIdx = -1;

        /**
         * For tracking JOIN clauses in a query
         *   = 0 - JOIN not found till now
         *   > 0 - Index of first JOIN clause in the statement.
         */
        $minJoin = 0;

        /**
         * For tracking JOIN clauses in a query
         *   = 0 - JOIN not found till now
         *   > 0 - Index of last JOIN clause
         *         (which appears together with other JOINs)
         *         in the statement.
         */
        $maxJoin = 0;

        $error = 0;
        $lastIdx = 0;
        foreach (array_keys($clauses) as $clauseType) {
            $clauseStartIdx = Query::getClauseStartOffset($this, $list, $clauseType);

            if (
                $clauseStartIdx !== -1
                && $this instanceof SelectStatement
                && ($clauseType === 'FORCE'
                    || $clauseType === 'IGNORE'
                    || $clauseType === 'USE')
            ) {
                // TODO: ordering of clauses in a SELECT statement with
                // Index hints is not supported
                return true;
            }

            // Handle ordering of Multiple Joins in a query
            if ($clauseStartIdx !== -1) {
                $containsJoinClause = str_contains(strtoupper($clauseType), 'JOIN');
                if ($minJoin === 0 && $containsJoinClause) {
                    // First JOIN clause is detected
                    $minJoin = $maxJoin = $clauseStartIdx;
                } elseif ($minJoin !== 0 && ! $containsJoinClause) {
                    // After a previous JOIN clause, a non-JOIN clause has been detected
                    $maxJoin = $lastIdx;
                } elseif ($maxJoin < $clauseStartIdx && $containsJoinClause) {
                    $error = 1;
                }
            }

            if ($clauseStartIdx !== -1 && $clauseStartIdx < $minIdx) {
                if ($minJoin === 0 || $error === 1) {
                    $token = $list->tokens[$clauseStartIdx];
                    $parser->error('Unexpected ordering of clauses.', $token);

                    return false;
                }

                $minIdx = $clauseStartIdx;
            } elseif ($clauseStartIdx !== -1) {
                $minIdx = $clauseStartIdx;
            }

            $lastIdx = $clauseStartIdx !== -1 ? $clauseStartIdx : $lastIdx;
        }

        return true;
    }
}
