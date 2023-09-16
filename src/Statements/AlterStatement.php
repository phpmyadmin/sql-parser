<?php

declare(strict_types=1);

namespace PhpMyAdmin\SqlParser\Statements;

use PhpMyAdmin\SqlParser\Components\AlterOperation;
use PhpMyAdmin\SqlParser\Components\Expression;
use PhpMyAdmin\SqlParser\Components\OptionsArray;
use PhpMyAdmin\SqlParser\Parser;
use PhpMyAdmin\SqlParser\Statement;
use PhpMyAdmin\SqlParser\Token;
use PhpMyAdmin\SqlParser\TokensList;

use function implode;
use function trim;

/**
 * `ALTER` statement.
 */
class AlterStatement extends Statement
{
    /**
     * Table affected.
     *
     * @var Expression|null
     */
    public $table;

    /**
     * Column affected by this statement.
     *
     * @var AlterOperation[]|null
     */
    public $altered = [];

    /**
     * Options of this statement.
     *
     * @var array<string, int|array<int, int|string>>
     * @psalm-var array<string, (positive-int|array{positive-int, ('var'|'var='|'expr'|'expr=')})>
     */
    public static $statementOptions = [
        'ONLINE' => 1,
        'OFFLINE' => 1,
        'IGNORE' => 2,
        // `DEFINER` is also used for `ALTER EVENT`
        'DEFINER' => [
            2,
            'expr=',
        ],
        'DATABASE' => 3,
        'EVENT' => 3,
        'FUNCTION' => 3,
        'PROCEDURE' => 3,
        'SERVER' => 3,
        'TABLE' => 3,
        'TABLESPACE' => 3,
        'USER' => 3,
        'VIEW' => 3,
    ];

    /**
     * @param Parser     $parser the instance that requests parsing
     * @param TokensList $list   the list of tokens to be parsed
     */
    public function parse(Parser $parser, TokensList $list): void
    {
        ++$list->idx; // Skipping `ALTER`.
        $parsedOptions = OptionsArray::parse($parser, $list, static::$statementOptions);
        if ($parsedOptions->isEmpty()) {
            $parser->error('Unrecognized alter operation.', $list->tokens[$list->idx]);

            return;
        }

        $this->options = $parsedOptions;
        ++$list->idx;

        // Parsing affected table.
        $this->table = Expression::parse(
            $parser,
            $list,
            [
                'parseField' => 'table',
                'breakOnAlias' => true,
            ]
        );
        ++$list->idx; // Skipping field.

        /**
         * The state of the parser.
         *
         * Below are the states of the parser.
         *
         *      0 -----------------[ alter operation ]-----------------> 1
         *
         *      1 -------------------------[ , ]-----------------------> 0
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

            if ($state === 0) {
                $options = [];
                if ($this->options->has('DATABASE')) {
                    $options = AlterOperation::$databaseOptions;
                } elseif ($this->options->has('TABLE')) {
                    $options = AlterOperation::$tableOptions;
                } elseif ($this->options->has('VIEW')) {
                    $options = AlterOperation::$viewOptions;
                } elseif ($this->options->has('USER')) {
                    $options = AlterOperation::$userOptions;
                } elseif ($this->options->has('EVENT')) {
                    $options = AlterOperation::$eventOptions;
                }

                $this->altered[] = AlterOperation::parse($parser, $list, $options);
                $state = 1;
            } elseif ($state === 1) {
                if (($token->type === Token::TYPE_OPERATOR) && ($token->value === ',')) {
                    $state = 0;
                }
            }
        }
    }

    public function build(): string
    {
        $tmp = [];
        foreach ($this->altered as $altered) {
            $tmp[] = $altered->build();
        }

        return trim(
            'ALTER ' . $this->options->build()
            . ' ' . $this->table->build()
            . ' ' . implode(', ', $tmp)
        );
    }
}
