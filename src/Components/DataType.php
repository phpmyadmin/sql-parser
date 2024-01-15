<?php

declare(strict_types=1);

namespace PhpMyAdmin\SqlParser\Components;

use PhpMyAdmin\SqlParser\Component;
use PhpMyAdmin\SqlParser\Parser;
use PhpMyAdmin\SqlParser\Token;
use PhpMyAdmin\SqlParser\TokensList;
use PhpMyAdmin\SqlParser\TokenType;

use function implode;
use function strtolower;
use function strtoupper;
use function trim;

/**
 * Parses a data type.
 */
final class DataType implements Component
{
    /**
     * All data type options.
     */
    private const DATA_TYPE_OPTIONS = [
        'BINARY' => 1,
        'CHARACTER SET' => [
            2,
            'var',
        ],
        'CHARSET' => [
            2,
            'var',
        ],
        'COLLATE' => [
            3,
            'var',
        ],
        'UNSIGNED' => 4,
        'ZEROFILL' => 5,
    ];

    /**
     * The name of the data type.
     *
     * @var string
     */
    public $name;

    /**
     * The parameters of this data type.
     *
     * Some data types have no parameters.
     * Numeric types might have parameters for the maximum number of digits,
     * precision, etc.
     * String types might have parameters for the maximum length stored.
     * `ENUM` and `SET` have parameters for possible values.
     *
     * For more information, check the MySQL manual.
     *
     * @var int[]|string[]
     */
    public array $parameters = [];

    /**
     * The options of this data type.
     *
     * @var OptionsArray
     */
    public $options;

    public bool $lowercase = false;

    /**
     * @param string         $name       the name of this data type
     * @param int[]|string[] $parameters the parameters (size or possible values)
     * @param OptionsArray   $options    the options of this data type
     */
    public function __construct(
        string|null $name = null,
        array $parameters = [],
        OptionsArray|null $options = null
    ) {
        $this->name = $name;
        $this->parameters = $parameters;
        $this->options = $options;
    }

    /**
     * @param Parser               $parser  the parser that serves as context
     * @param TokensList           $list    the list of tokens that are being parsed
     * @param array<string, mixed> $options parameters for parsing
     */
    public static function parse(Parser $parser, TokensList $list, array $options = []): DataType|null
    {
        $ret = new static();

        /**
         * The state of the parser.
         *
         * Below are the states of the parser.
         *
         *      0 -------------------[ data type ]--------------------> 1
         *
         *      1 ----------------[ size and options ]----------------> 2
         *
         * @var int
         */
        $state = 0;

        for (; $list->idx < $list->count; ++$list->idx) {
            /**
             * Token parsed at this moment.
             */
            $token = $list->tokens[$list->idx];

            // Skipping whitespaces and comments.
            if (($token->type === TokenType::Whitespace) || ($token->type === TokenType::Comment)) {
                continue;
            }

            if ($state === 0) {
                $ret->name = strtoupper((string) $token->value);
                if (($token->type !== TokenType::Keyword) || (! ($token->flags & Token::FLAG_KEYWORD_DATA_TYPE))) {
                    $parser->error('Unrecognized data type.', $token);
                }

                $state = 1;
            } elseif ($state === 1) {
                if (($token->type === TokenType::Operator) && ($token->value === '(')) {
                    $parameters = ArrayObj::parse($parser, $list);
                    ++$list->idx;
                    $ret->parameters = ($ret->name === 'ENUM') || ($ret->name === 'SET') ?
                        $parameters->raw : $parameters->values;
                }

                $ret->options = OptionsArray::parse($parser, $list, self::DATA_TYPE_OPTIONS);
                ++$list->idx;
                break;
            }
        }

        if (empty($ret->name)) {
            return null;
        }

        --$list->idx;

        return $ret;
    }

    public function build(): string
    {
        $name = $this->lowercase ? strtolower($this->name) : $this->name;

        $parameters = '';
        if ($this->parameters !== []) {
            $parameters = '(' . implode(',', $this->parameters) . ')';
        }

        return trim($name . $parameters . ' ' . $this->options);
    }

    public function __toString(): string
    {
        return $this->build();
    }
}
