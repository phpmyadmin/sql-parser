<?php

declare(strict_types=1);

namespace PhpMyAdmin\SqlParser\Components;

use PhpMyAdmin\SqlParser\Component;
use PhpMyAdmin\SqlParser\Context;
use PhpMyAdmin\SqlParser\Parser;
use PhpMyAdmin\SqlParser\Token;
use PhpMyAdmin\SqlParser\TokensList;

use function count;
use function implode;
use function trim;

/**
 * Parses the definition of a key.
 *
 * Used for parsing `CREATE TABLE` statement.
 *
 * @final
 */
class Key extends Component
{
    /**
     * All key options.
     *
     * @var array<string, int|array<int, int|string>>
     * @psalm-var array<string, (positive-int|array{positive-int, ('var'|'var='|'expr'|'expr=')})>
     */
    public static $KEY_OPTIONS = [
        'KEY_BLOCK_SIZE' => [
            1,
            'var=',
        ],
        'USING' => [
            2,
            'var',
        ],
        'WITH PARSER' => [
            3,
            'var',
        ],
        'COMMENT' => [
            4,
            'var',
        ],
        // MariaDB options
        'CLUSTERING' => [
            4,
            'var=',
        ],
        'ENGINE_ATTRIBUTE' => [
            5,
            'var=',
        ],
        'SECONDARY_ENGINE_ATTRIBUTE' => [
            5,
            'var=',
        ],
        // MariaDB & MySQL options
        'VISIBLE' => 6,
        'INVISIBLE' => 6,
        // MariaDB options
        'IGNORED' => 10,
        'NOT IGNORED' => 10,
    ];

    /**
     * The name of this key.
     *
     * @var string
     */
    public $name;

    /**
     * The key columns
     *
     * @var array<int, array<string, int|string>>
     * @phpstan-var array{name?: string, expr?: string, length?: int, order?: string}[]
     */
    public $columns;

    /**
     * The type of this key.
     *
     * @var string
     */
    public $type;

    /**
     * The expression if the Key is not using column names
     *
     * @var string|null
     */
    public $expr = null;

    /**
     * The options of this key or null if none where found.
     *
     * @var OptionsArray|null
     */
    public $options;

    /**
     * @param string                                $name    the name of the key
     * @param array<int, array<string, int|string>> $columns the columns covered by this key
     * @param string                                $type    the type of this key
     * @param OptionsArray                          $options the options of this key
     * @phpstan-param array{name?: string, expr?: string, length?: int, order?: string}[] $columns
     */
    public function __construct(
        $name = null,
        array $columns = [],
        $type = null,
        $options = null
    ) {
        $this->name = $name;
        $this->columns = $columns;
        $this->type = $type;
        $this->options = $options;
    }

    /**
     * @param Parser               $parser  the parser that serves as context
     * @param TokensList           $list    the list of tokens that are being parsed
     * @param array<string, mixed> $options parameters for parsing
     *
     * @return Key
     */
    public static function parse(Parser $parser, TokensList $list, array $options = [])
    {
        $ret = new static();

        /**
         * Last parsed column.
         *
         * @var array<string,mixed>
         */
        $lastColumn = [];

        /**
         * The state of the parser.
         *
         * Below are the states of the parser.
         *
         *      0 ---------------------[ type ]---------------------------> 1
         *
         *      1 ---------------------[ name ]---------------------------> 1
         *      1 ---------------------[ columns ]------------------------> 2
         *
         *      2 ---------------------[ expression ]---------------------> 2
         *      2 ---------------------[ column length ]------------------> 3
         *      3 ---------------------[ column length ]------------------> 2
         *      2 ---------------------[ options ]------------------------> 4
         *
         * @var int
         */
        $state = 0;

        /**
         * Parentheses depth while skipping an invalid key part, null when not skipping.
         *
         * @var int|null
         */
        $skipDepth = null;

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
                $ret->type = $token->value;
                $state = 1;
            } elseif ($state === 1) {
                if (($token->type === Token::TYPE_OPERATOR) && ($token->value === '(')) {
                    $state = 2;
                } else {
                    $ret->name = $token->value;
                }
            } elseif ($state === 2) {
                if ($skipDepth !== null) {
                    // Skips the rest of an invalid key part.
                    if ($token->type === Token::TYPE_OPERATOR && $token->value === '(') {
                        ++$skipDepth;
                        continue;
                    }

                    if ($token->type === Token::TYPE_OPERATOR && $token->value === ')' && $skipDepth > 0) {
                        --$skipDepth;
                        continue;
                    }

                    $isPartEnd = $token->type === Token::TYPE_OPERATOR
                        && ($token->value === ',' || $token->value === ')');
                    if ($skipDepth > 0 || ! $isPartEnd) {
                        continue;
                    }

                    $skipDepth = null;
                }

                if ($token->type === Token::TYPE_OPERATOR) {
                    if ($token->value === '(' && $lastColumn === []) {
                        // A functional key part, e.g. `(col * 2)` in `KEY (a, (col * 2), b)`.
                        $lastColumn['expr'] = (string) Expression::parse(
                            $parser,
                            $list,
                            ['parenthesesDelimited' => true]
                        );
                    } elseif ($token->value === '(' && ! isset($lastColumn['expr'])) {
                        $state = 3;
                    } elseif (($token->value === ',') || ($token->value === ')')) {
                        $state = $token->value === ',' ? 2 : 4;
                        if (! empty($lastColumn)) {
                            $ret->columns[] = $lastColumn;
                            $lastColumn = [];
                        }

                        if ($state === 4) {
                            static::moveExpressionsOnlyKeyToExpr($ret);
                        }
                    } elseif (isset($lastColumn['expr'])) {
                        $parser->error('Unexpected token.', $token);
                        // An unexpected "(" is already consumed, so its ")" must not end the key.
                        $skipDepth = $token->value === '(' ? 1 : 0;
                    }
                } elseif (
                    (
                        $token->type === Token::TYPE_KEYWORD
                    )
                    &&
                    (
                        ($token->keyword === 'ASC') || ($token->keyword === 'DESC')
                    )
                ) {
                    $lastColumn['order'] = $token->keyword;
                } elseif (isset($lastColumn['expr'])) {
                    $parser->error('Unexpected token.', $token);
                    $skipDepth = 0;
                } else {
                    $lastColumn['name'] = $token->value;
                }
            } elseif ($state === 3) {
                if (($token->type === Token::TYPE_OPERATOR) && ($token->value === ')')) {
                    $state = 2;
                } else {
                    $lastColumn['length'] = $token->value;
                }
            } elseif ($state === 4) {
                $ret->options = OptionsArray::parse($parser, $list, static::$KEY_OPTIONS);
                ++$list->idx;
                break;
            }
        }

        --$list->idx;

        return $ret;
    }

    /**
     * Keeps the `expr` representation for keys made only of expressions.
     */
    private static function moveExpressionsOnlyKeyToExpr(Key $key): void
    {
        $expressions = [];
        foreach ($key->columns as $column) {
            if (! isset($column['expr']) || count($column) !== 1) {
                return;
            }

            $expressions[] = $column['expr'];
        }

        if ($expressions === []) {
            return;
        }

        $key->expr = implode(', ', $expressions);
        $key->columns = [];
    }

    /**
     * @param Key                  $component the component to be built
     * @param array<string, mixed> $options   parameters for building
     *
     * @return string
     */
    public static function build($component, array $options = [])
    {
        $ret = $component->type . ' ';
        if (! empty($component->name)) {
            $ret .= Context::escape($component->name) . ' ';
        }

        if ($component->expr !== null) {
            return $ret . '(' . $component->expr . ') ' . $component->options;
        }

        $columns = [];
        foreach ($component->columns as $column) {
            $tmp = '';
            if (isset($column['name'])) {
                $tmp .= Context::escape($column['name']);
            }

            if (isset($column['expr'])) {
                $tmp .= $column['expr'];
            }

            if (isset($column['length'])) {
                $tmp .= '(' . $column['length'] . ')';
            }

            if (isset($column['order'])) {
                $tmp .= ' ' . $column['order'];
            }

            $columns[] = $tmp;
        }

        $ret .= '(' . implode(',', $columns) . ') ' . $component->options;

        return trim($ret);
    }
}
