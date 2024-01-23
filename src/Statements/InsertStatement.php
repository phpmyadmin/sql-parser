<?php

declare(strict_types=1);

namespace PhpMyAdmin\SqlParser\Statements;

use PhpMyAdmin\SqlParser\Components\ArrayObj;
use PhpMyAdmin\SqlParser\Components\IntoKeyword;
use PhpMyAdmin\SqlParser\Components\SetOperation;
use PhpMyAdmin\SqlParser\Parser;
use PhpMyAdmin\SqlParser\Parsers\Array2d;
use PhpMyAdmin\SqlParser\Parsers\ArrayObjs;
use PhpMyAdmin\SqlParser\Parsers\IntoKeywords;
use PhpMyAdmin\SqlParser\Parsers\OptionsArrays;
use PhpMyAdmin\SqlParser\Parsers\SetOperations;
use PhpMyAdmin\SqlParser\Statement;
use PhpMyAdmin\SqlParser\TokensList;
use PhpMyAdmin\SqlParser\TokenType;

use function strlen;
use function trim;

/**
 * `INSERT` statement.
 *
 * INSERT [LOW_PRIORITY | DELAYED | HIGH_PRIORITY] [IGNORE]
 *     [INTO] tbl_name
 *     [PARTITION (partition_name,...)]
 *     [(col_name,...)]
 *     {VALUES | VALUE} ({expr | DEFAULT},...),(...),...
 *     [ ON DUPLICATE KEY UPDATE
 *       col_name=expr
 *         [, col_name=expr] ... ]
 *
 * or
 *
 * INSERT [LOW_PRIORITY | DELAYED | HIGH_PRIORITY] [IGNORE]
 *     [INTO] tbl_name
 *     [PARTITION (partition_name,...)]
 *     SET col_name={expr | DEFAULT}, ...
 *     [ ON DUPLICATE KEY UPDATE
 *       col_name=expr
 *         [, col_name=expr] ... ]
 *
 * or
 *
 * INSERT [LOW_PRIORITY | HIGH_PRIORITY] [IGNORE]
 *     [INTO] tbl_name
 *     [PARTITION (partition_name,...)]
 *     [(col_name,...)]
 *     SELECT ...
 *     [ ON DUPLICATE KEY UPDATE
 *       col_name=expr
 *         [, col_name=expr] ... ]
 */
class InsertStatement extends Statement
{
    /**
     * Options for `INSERT` statements.
     *
     * @var array<string, int|array<int, int|string>>
     * @psalm-var array<string, (positive-int|array{positive-int, ('var'|'var='|'expr'|'expr=')})>
     */
    public static array $statementOptions = [
        'LOW_PRIORITY' => 1,
        'DELAYED' => 2,
        'HIGH_PRIORITY' => 3,
        'IGNORE' => 4,
    ];

    /**
     * Tables used as target for this statement.
     */
    public IntoKeyword|null $into = null;

    /**
     * Values to be inserted.
     *
     * @var ArrayObj[]|null
     */
    public array|null $values = null;

    /**
     * If SET clause is present
     * holds the SetOperation.
     *
     * @var SetOperation[]|null
     */
    public array|null $set = null;

    /**
     * If SELECT clause is present
     * holds the SelectStatement.
     */
    public SelectStatement|null $select = null;

    /**
     * If WITH CTE is present
     * holds the WithStatement.
     */
    public WithStatement|null $with = null;

    /**
     * If ON DUPLICATE KEY UPDATE clause is present
     * holds the SetOperation.
     *
     * @var SetOperation[]|null
     */
    public array|null $onDuplicateSet = null;

    public function build(): string
    {
        $ret = 'INSERT ' . $this->options;
        $ret = trim($ret) . ' INTO ' . $this->into;

        if ($this->values !== null && $this->values !== []) {
            $ret .= ' VALUES ' . ArrayObjs::buildAll($this->values);
        } elseif ($this->set !== null && $this->set !== []) {
            $ret .= ' SET ' . SetOperations::buildAll($this->set);
        } elseif ($this->select !== null && strlen((string) $this->select) > 0) {
            $ret .= ' ' . $this->select->build();
        }

        if ($this->onDuplicateSet !== null && $this->onDuplicateSet !== []) {
            $ret .= ' ON DUPLICATE KEY UPDATE ' . SetOperations::buildAll($this->onDuplicateSet);
        }

        return $ret;
    }

    /**
     * @param Parser     $parser the instance that requests parsing
     * @param TokensList $list   the list of tokens to be parsed
     */
    public function parse(Parser $parser, TokensList $list): void
    {
        ++$list->idx; // Skipping `INSERT`.

        // parse any options if provided
        $this->options = OptionsArrays::parse($parser, $list, static::$statementOptions);
        ++$list->idx;

        /**
         * The state of the parser.
         *
         * Below are the states of the parser.
         *
         *      0 ---------------------------------[ INTO ]----------------------------------> 1
         *
         *      1 -------------------------[ VALUES/VALUE/SET/SELECT ]-----------------------> 2
         *
         *      2 -------------------------[ ON DUPLICATE KEY UPDATE ]-----------------------> 3
         */
        $state = 0;

        /**
         * For keeping track of semi-states on encountering
         * ON DUPLICATE KEY UPDATE ...
         */
        $miniState = 0;

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
                if ($token->type === TokenType::Keyword && $token->keyword !== 'INTO') {
                    $parser->error('Unexpected keyword.', $token);
                    break;
                }

                ++$list->idx;
                $this->into = IntoKeywords::parse(
                    $parser,
                    $list,
                    ['fromInsert' => true],
                );

                $state = 1;
            } elseif ($state === 1) {
                if ($token->type !== TokenType::Keyword) {
                    $parser->error('Unexpected token.', $token);
                    break;
                }

                if ($token->keyword === 'VALUE' || $token->keyword === 'VALUES') {
                    ++$list->idx; // skip VALUES

                    $this->values = Array2d::parse($parser, $list);
                } elseif ($token->keyword === 'SET') {
                    ++$list->idx; // skip SET

                    $this->set = SetOperations::parse($parser, $list);
                } elseif ($token->keyword === 'SELECT') {
                    $this->select = new SelectStatement($parser, $list);
                } elseif ($token->keyword === 'WITH') {
                    $this->with = new WithStatement($parser, $list);
                } else {
                    $parser->error('Unexpected keyword.', $token);
                    break;
                }

                $state = 2;
                $miniState = 1;
            } elseif ($state === 2) {
                $lastCount = $miniState;

                if ($miniState === 1 && $token->keyword === 'ON') {
                    ++$miniState;
                } elseif ($miniState === 2 && $token->keyword === 'DUPLICATE') {
                    ++$miniState;
                } elseif ($miniState === 3 && $token->keyword === 'KEY') {
                    ++$miniState;
                } elseif ($miniState === 4 && $token->keyword === 'UPDATE') {
                    ++$miniState;
                }

                if ($lastCount === $miniState) {
                    $parser->error('Unexpected token.', $token);
                    break;
                }

                if ($miniState === 5) {
                    ++$list->idx;
                    $this->onDuplicateSet = SetOperations::parse($parser, $list);
                    $state = 3;
                }
            }
        }

        --$list->idx;
    }
}
