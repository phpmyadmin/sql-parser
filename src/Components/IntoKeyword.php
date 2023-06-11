<?php

declare(strict_types=1);

namespace PhpMyAdmin\SqlParser\Components;

use PhpMyAdmin\SqlParser\Component;
use PhpMyAdmin\SqlParser\Parser;
use PhpMyAdmin\SqlParser\Token;
use PhpMyAdmin\SqlParser\TokensList;

use function implode;
use function trim;

/**
 * `INTO` keyword parser.
 */
final class IntoKeyword implements Component
{
    /**
     * FIELDS/COLUMNS Options for `SELECT...INTO` statements.
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
     * LINES Options for `SELECT...INTO` statements.
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
     * Type of target (OUTFILE or SYMBOL).
     *
     * @var string|null
     */
    public $type;

    /**
     * The destination, which can be a table or a file.
     *
     * @var string|Expression|null
     */
    public $dest;

    /**
     * The name of the columns.
     *
     * @var string[]|null
     */
    public $columns;

    /**
     * The values to be selected into (SELECT .. INTO @var1).
     *
     * @var Expression[]|null
     */
    public $values;

    /**
     * Options for FIELDS/COLUMNS keyword.
     *
     * @see IntoKeyword::$statementFieldsOptions
     *
     * @var OptionsArray|null
     */
    public $fieldsOptions;

    /**
     * Whether to use `FIELDS` or `COLUMNS` while building.
     *
     * @var bool|null
     */
    public $fieldsKeyword;

    /**
     * Options for OPTIONS keyword.
     *
     * @see IntoKeyword::$statementLinesOptions
     *
     * @var OptionsArray|null
     */
    public $linesOptions;

    /**
     * @param string|null            $type          type of destination (may be OUTFILE)
     * @param string|Expression|null $dest          actual destination
     * @param string[]|null          $columns       column list of destination
     * @param Expression[]|null      $values        selected fields
     * @param OptionsArray|null      $fieldsOptions options for FIELDS/COLUMNS keyword
     * @param bool|null              $fieldsKeyword options for OPTIONS keyword
     */
    public function __construct(
        $type = null,
        $dest = null,
        $columns = null,
        $values = null,
        $fieldsOptions = null,
        $fieldsKeyword = null
    ) {
        $this->type = $type;
        $this->dest = $dest;
        $this->columns = $columns;
        $this->values = $values;
        $this->fieldsOptions = $fieldsOptions;
        $this->fieldsKeyword = $fieldsKeyword;
    }

    /**
     * @param Parser               $parser  the parser that serves as context
     * @param TokensList           $list    the list of tokens that are being parsed
     * @param array<string, mixed> $options parameters for parsing
     *
     * @return IntoKeyword
     */
    public static function parse(Parser $parser, TokensList $list, array $options = [])
    {
        $ret = new static();

        /**
         * The state of the parser.
         *
         * Below are the states of the parser.
         *
         *      0 -----------------------[ name ]----------------------> 1
         *      0 ---------------------[ OUTFILE ]---------------------> 2
         *
         *      1 ------------------------[ ( ]------------------------> (END)
         *
         *      2 ---------------------[ filename ]--------------------> 1
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

            if (($token->type === Token::TYPE_KEYWORD) && ($token->flags & Token::FLAG_KEYWORD_RESERVED)) {
                if (($state === 0) && ($token->keyword === 'OUTFILE')) {
                    $ret->type = 'OUTFILE';
                    $state = 2;
                    continue;
                }

                // No other keyword is expected except for $state = 4, which expects `LINES`
                if ($state !== 4) {
                    break;
                }
            }

            if ($state === 0) {
                if (
                    (isset($options['fromInsert'])
                    && $options['fromInsert'])
                    || (isset($options['fromReplace'])
                    && $options['fromReplace'])
                ) {
                    $ret->dest = Expression::parse(
                        $parser,
                        $list,
                        [
                            'parseField' => 'table',
                            'breakOnAlias' => true,
                        ]
                    );
                } else {
                    $ret->values = ExpressionArray::parse($parser, $list);
                }

                $state = 1;
            } elseif ($state === 1) {
                if (($token->type === Token::TYPE_OPERATOR) && ($token->value === '(')) {
                    $ret->columns = ArrayObj::parse($parser, $list)->values;
                    ++$list->idx;
                }

                break;
            } elseif ($state === 2) {
                $ret->dest = $token->value;

                $state = 3;
            } elseif ($state === 3) {
                $ret->parseFileOptions($parser, $list, $token->keyword);
                $state = 4;
            } elseif ($state === 4) {
                if ($token->type === Token::TYPE_KEYWORD && $token->keyword !== 'LINES') {
                    break;
                }

                $ret->parseFileOptions($parser, $list, $token->keyword);
                $state = 5;
            }
        }

        --$list->idx;

        return $ret;
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

            $this->fieldsKeyword = ($keyword === 'FIELDS');
        } else {
            // parse line options
            $this->linesOptions = OptionsArray::parse($parser, $list, static::$statementLinesOptions);
        }
    }

    public function build(): string
    {
        if ($this->dest instanceof Expression) {
            $columns = ! empty($this->columns) ? '(`' . implode('`, `', $this->columns) . '`)' : '';

            return $this->dest . $columns;
        }

        if (isset($this->values)) {
            return Expression::buildAll($this->values);
        }

        $ret = 'OUTFILE "' . $this->dest . '"';

        $fieldsOptionsString = $this->fieldsOptions?->build() ?? '';
        if (trim($fieldsOptionsString) !== '') {
            $ret .= $this->fieldsKeyword ? ' FIELDS' : ' COLUMNS';
            $ret .= ' ' . $fieldsOptionsString;
        }

        $linesOptionsString = $this->linesOptions?->build() ?? '';
        if (trim($linesOptionsString) !== '') {
            $ret .= ' LINES ' . $linesOptionsString;
        }

        return $ret;
    }

    public function __toString(): string
    {
        return $this->build();
    }
}
