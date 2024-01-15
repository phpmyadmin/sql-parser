<?php

declare(strict_types=1);

namespace PhpMyAdmin\SqlParser\Components;

use PhpMyAdmin\SqlParser\Component;
use PhpMyAdmin\SqlParser\Parser;
use PhpMyAdmin\SqlParser\TokensList;
use PhpMyAdmin\SqlParser\TokenType;
use PhpMyAdmin\SqlParser\Translator;
use RuntimeException;

use function array_search;
use function implode;

/**
 * `JOIN` keyword parser.
 */
final class JoinKeyword implements Component
{
    /**
     * Types of join.
     */
    public const JOINS = [
        'CROSS JOIN' => 'CROSS',
        'FULL JOIN' => 'FULL',
        'FULL OUTER JOIN' => 'FULL',
        'INNER JOIN' => 'INNER',
        'JOIN' => 'JOIN',
        'LEFT JOIN' => 'LEFT',
        'LEFT OUTER JOIN' => 'LEFT',
        'RIGHT JOIN' => 'RIGHT',
        'RIGHT OUTER JOIN' => 'RIGHT',
        'NATURAL JOIN' => 'NATURAL',
        'NATURAL LEFT JOIN' => 'NATURAL LEFT',
        'NATURAL RIGHT JOIN' => 'NATURAL RIGHT',
        'NATURAL LEFT OUTER JOIN' => 'NATURAL LEFT OUTER',
        'NATURAL RIGHT OUTER JOIN' => 'NATURAL RIGHT OUTER',
        'STRAIGHT_JOIN' => 'STRAIGHT',
    ];

    /**
     * Type of this join.
     *
     * @see JoinKeyword::JOINS
     *
     * @var string
     */
    public $type;

    /**
     * Join expression.
     *
     * @var Expression
     */
    public $expr;

    /**
     * Join conditions.
     *
     * @var Condition[]
     */
    public $on;

    /**
     * Columns in Using clause.
     *
     * @var ArrayObj
     */
    public $using;

    /**
     * @see JoinKeyword::JOINS
     *
     * @param string      $type  Join type
     * @param Expression  $expr  join expression
     * @param Condition[] $on    join conditions
     * @param ArrayObj    $using columns joined
     */
    public function __construct(
        string|null $type = null,
        Expression|null $expr = null,
        array|null $on = null,
        ArrayObj|null $using = null,
    ) {
        $this->type = $type;
        $this->expr = $expr;
        $this->on = $on;
        $this->using = $using;
    }

    /**
     * @param Parser               $parser  the parser that serves as context
     * @param TokensList           $list    the list of tokens that are being parsed
     * @param array<string, mixed> $options parameters for parsing
     *
     * @return JoinKeyword[]
     */
    public static function parse(Parser $parser, TokensList $list, array $options = []): array
    {
        $ret = [];

        $expr = new static();

        /**
         * The state of the parser.
         *
         * Below are the states of the parser.
         *
         *      0 -----------------------[ JOIN ]----------------------> 1
         *
         *      1 -----------------------[ expr ]----------------------> 2
         *
         *      2 ------------------------[ ON ]-----------------------> 3
         *      2 -----------------------[ USING ]---------------------> 4
         *
         *      3 --------------------[ conditions ]-------------------> 0
         *
         *      4 ----------------------[ columns ]--------------------> 0
         *
         * @var int
         */
        $state = 0;

        // By design, the parser will parse first token after the keyword.
        // In this case, the keyword must be analyzed too, in order to determine
        // the type of this join.
        if ($list->idx > 0) {
            --$list->idx;
        }

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
                if (($token->type !== TokenType::Keyword) || empty(self::JOINS[$token->keyword])) {
                    break;
                }

                $expr->type = self::JOINS[$token->keyword];
                $state = 1;
            } elseif ($state === 1) {
                $expr->expr = Expression::parse($parser, $list, ['field' => 'table']);
                $state = 2;
            } elseif ($state === 2) {
                if ($token->type === TokenType::Keyword) {
                    switch ($token->keyword) {
                        case 'ON':
                            $state = 3;
                            break;
                        case 'USING':
                            $state = 4;
                            break;
                        default:
                            if (empty(self::JOINS[$token->keyword])) {
                                /* Next clause is starting */
                                break 2;
                            }

                            $ret[] = $expr;
                            $expr = new static();
                            $expr->type = self::JOINS[$token->keyword];
                            $state = 1;

                            break;
                    }
                }
            } elseif ($state === 3) {
                $expr->on = Condition::parse($parser, $list);
                $ret[] = $expr;
                $expr = new static();
                $state = 0;
            } elseif ($state === 4) {
                $expr->using = ArrayObj::parse($parser, $list);
                $ret[] = $expr;
                $expr = new static();
                $state = 0;
            }
        }

        if (! empty($expr->type)) {
            $ret[] = $expr;
        }

        --$list->idx;

        return $ret;
    }

    public function build(): string
    {
        throw new RuntimeException(Translator::gettext('Not implemented yet.'));
    }

    /** @param JoinKeyword[] $component the component to be built */
    public static function buildAll(array $component): string
    {
        $ret = [];
        foreach ($component as $c) {
            $ret[] = array_search($c->type, self::JOINS) . ' ' . $c->expr
                . (! empty($c->on)
                    ? ' ON ' . Condition::buildAll($c->on) : '')
                . (! empty($c->using)
                    ? ' USING ' . $c->using->build() : '');
        }

        return implode(' ', $ret);
    }

    public function __toString(): string
    {
        return $this->build();
    }
}
