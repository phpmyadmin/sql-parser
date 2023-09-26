<?php

declare(strict_types=1);

namespace PhpMyAdmin\SqlParser\Components;

use PhpMyAdmin\SqlParser\Component;
use PhpMyAdmin\SqlParser\Context;
use PhpMyAdmin\SqlParser\Parser;
use PhpMyAdmin\SqlParser\TokensList;
use PhpMyAdmin\SqlParser\TokenType;

use function implode;
use function trim;

/**
 * The definition of a parameter of a function or procedure.
 */
final class ParameterDefinition implements Component
{
    /**
     * The name of the new column.
     *
     * @var string
     */
    public $name;

    /**
     * Parameter's direction (IN, OUT or INOUT).
     *
     * @var string
     */
    public $inOut;

    /**
     * The data type of thew new column.
     *
     * @var DataType
     */
    public $type;

    /**
     * @param string   $name  parameter's name
     * @param string   $inOut parameter's directional type (IN / OUT or None)
     * @param DataType $type  parameter's type
     */
    public function __construct($name = null, $inOut = null, $type = null)
    {
        $this->name = $name;
        $this->inOut = $inOut;
        $this->type = $type;
    }

    /**
     * @param Parser               $parser  the parser that serves as context
     * @param TokensList           $list    the list of tokens that are being parsed
     * @param array<string, mixed> $options parameters for parsing
     *
     * @return ParameterDefinition[]
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
         *      0 -----------------------[ ( ]------------------------> 1
         *
         *      1 ----------------[ IN / OUT / INOUT ]----------------> 1
         *      1 ----------------------[ name ]----------------------> 2
         *
         *      2 -------------------[ data type ]--------------------> 3
         *
         *      3 ------------------------[ , ]-----------------------> 1
         *      3 ------------------------[ ) ]-----------------------> (END)
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
            if ($token->type === TokenType::Delimiter) {
                break;
            }

            // Skipping whitespaces and comments.
            if (($token->type === TokenType::Whitespace) || ($token->type === TokenType::Comment)) {
                continue;
            }

            if ($state === 0) {
                if (($token->type === TokenType::Operator) && ($token->value === '(')) {
                    $state = 1;
                }
            } elseif ($state === 1) {
                if (($token->value === 'IN') || ($token->value === 'OUT') || ($token->value === 'INOUT')) {
                    $expr->inOut = $token->value;
                    ++$list->idx;
                } elseif ($token->value === ')') {
                    ++$list->idx;
                    break;
                } else {
                    $expr->name = $token->value;
                    $state = 2;
                }
            } elseif ($state === 2) {
                $expr->type = DataType::parse($parser, $list);
                $state = 3;
            } elseif ($state === 3) {
                $ret[] = $expr;
                $expr = new static();
                if ($token->value === ',') {
                    $state = 1;
                } elseif ($token->value === ')') {
                    ++$list->idx;
                    break;
                }
            }
        }

        // Last iteration was not saved.
        if (isset($expr->name) && ($expr->name !== '')) {
            $ret[] = $expr;
        }

        --$list->idx;

        return $ret;
    }

    public function build(): string
    {
        $tmp = '';
        if (! empty($this->inOut)) {
            $tmp .= $this->inOut . ' ';
        }

        return trim(
            $tmp . Context::escape($this->name) . ' ' . $this->type
        );
    }

    /**
     * @param ParameterDefinition[] $component the component to be built
     */
    public static function buildAll(array $component): string
    {
        return '(' . implode(', ', $component) . ')';
    }

    public function __toString(): string
    {
        return $this->build();
    }
}
