<?php

declare(strict_types=1);

namespace PhpMyAdmin\SqlParser\Components;

use PhpMyAdmin\SqlParser\Component;
use PhpMyAdmin\SqlParser\Parser;
use PhpMyAdmin\SqlParser\Parsers\Expressions;
use PhpMyAdmin\SqlParser\Parsers\OptionsArrays;
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
     * LINES Options for `SELECT...INTO` statements.
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
     * Type of target (OUTFILE or SYMBOL).
     */
    public string|null $type = null;

    /**
     * The destination, which can be a table or a file.
     */
    public string|Expression|null $dest = null;

    /**
     * The name of the columns.
     *
     * @var string[]|null
     */
    public array|null $columns = null;

    /**
     * The values to be selected into (SELECT .. INTO @var1).
     *
     * @var Expression[]|null
     */
    public array|null $values = null;

    /**
     * Options for FIELDS/COLUMNS keyword.
     *
     * @see IntoKeyword::STATEMENT_FIELDS_OPTIONS
     */
    public OptionsArray|null $fieldsOptions = null;

    /**
     * Whether to use `FIELDS` or `COLUMNS` while building.
     */
    public bool|null $fieldsKeyword = null;

    /**
     * Options for OPTIONS keyword.
     *
     * @see IntoKeyword::STATEMENT_LINES_OPTIONS
     */
    public OptionsArray|null $linesOptions = null;

    /**
     * @param string|null            $type          type of destination (may be OUTFILE)
     * @param string|Expression|null $dest          actual destination
     * @param string[]|null          $columns       column list of destination
     * @param Expression[]|null      $values        selected fields
     * @param OptionsArray|null      $fieldsOptions options for FIELDS/COLUMNS keyword
     * @param bool|null              $fieldsKeyword options for OPTIONS keyword
     */
    public function __construct(
        string|null $type = null,
        string|Expression|null $dest = null,
        array|null $columns = null,
        array|null $values = null,
        OptionsArray|null $fieldsOptions = null,
        bool|null $fieldsKeyword = null,
    ) {
        $this->type = $type;
        $this->dest = $dest;
        $this->columns = $columns;
        $this->values = $values;
        $this->fieldsOptions = $fieldsOptions;
        $this->fieldsKeyword = $fieldsKeyword;
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

            $this->fieldsKeyword = ($keyword === 'FIELDS');
        } else {
            // parse line options
            $this->linesOptions = OptionsArrays::parse($parser, $list, self::STATEMENT_LINES_OPTIONS);
        }
    }

    public function build(): string
    {
        if ($this->dest instanceof Expression) {
            $columns = ! empty($this->columns) ? '(`' . implode('`, `', $this->columns) . '`)' : '';

            return $this->dest . $columns;
        }

        if (isset($this->values)) {
            return Expressions::buildAll($this->values);
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
