<?php

/**
 * Parses the definition of a Check component.
 */

namespace PhpMyAdmin\SqlParser\Components;

use PhpMyAdmin\SqlParser\Component;
use PhpMyAdmin\SqlParser\Context;
use PhpMyAdmin\SqlParser\Parser;
use PhpMyAdmin\SqlParser\Token;
use PhpMyAdmin\SqlParser\TokensList;

/**
 * Parses the definition of a Check component.
 *
 * Used for parsing `CREATE TABLE` statement.
 *
 * @category   Components
 *
 * @license    https://www.gnu.org/licenses/gpl-2.0.txt GPL-2.0+
 */
class Check extends Component
{

    /**
     * Columns.
     *
     * @var array
     */
    public $columns;

    /**
     * The operators in this component.
     *
     * @var OptionsArray
     */
    public $operators;

    /**
     * The operands in this component.
     *
     * @var operands
     */
    public $operands;

    /**
     * The logical operators in this component.
     *
     * @var logicalOperatorsArray
     */
    public $logicalOperators;

    /**
     * Possible operators in the component.
     *
     * @var OperatorsArray
     */
    public static $operatorsArray = array("=", ">", "<", "<=", ">=", "!=", "LIKE", "NOT", "BETWEEN", "IS", "NULL", "REGEXP", "REGEXP");

    /**
     * Constructor.
     *
     * @param string       $name    the name of the component
     */
    public function __construct($name = null) {
        $this->name = $name;
    }

    /**
     * @param Parser     $parser  the parser that serves as context
     * @param TokensList $list    the list of tokens that are being parsed
     * @param array      $options parameters for parsing
     *
     * @return component
     */
    public static function parse(Parser $parser, TokensList $list, array $options = array())
    {
        $ret = new self();

        /**
         * Last parsed column.
         *
         * @var array
         */
        $lastColumn = array();

        /**
         * Bracket stack
         *
         * @var bracketStack
         */
        $bracketStack = array();

        /**
         * The state of the parser.
         *
         * Below are the states of the parser.
         *
         *      0 ----------------------[ column name ]-----------------------> 1
         *
         *      1 ----------------------[ operator ]-----------------------> 2
         *      2 ---------------------[ operand ]---------------------> 3
         *      2 ---------------------[ operand ]---------------------> 4
         *
         *      3 ---------------------[ logical operator ]---------------------> 0
         *
         * @var int
         */
        $state = 0;

        for (; $list->idx < $list->count; ++$list->idx) {
            /**
             * Token parsed at this moment.
             *
             * @var Token
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
                if (($token->type === Token::TYPE_KEYWORD) && $token->keyword === 'CHECK') {
                    $state = 0;
                } elseif(($token->type === Token::TYPE_OPERATOR) && ($token->value === '(')) {
                    $state = 1;
                    array_push($bracketStack, '(');
                } else {
                    $parser->error(
                        'Invalid token',
                        $token
                    );
                    return $ret;
                }
            } else if($state === 1) {
                if(($token->type === Token::TYPE_OPERATOR) && ($token->value === '(')) {
                    array_push($bracketStack, '(');
                    $state = 1;
                } elseif($token->type === Token::TYPE_OPERATOR) {
                    $parser->error(
                        'A Column name was expected!',
                        $token
                    );
                    return $ret;
                } else {
                    $ret->columns[] = $token->value;
                    $state = 2;
                }
            } elseif ($state === 2) {
                if(($token->type === Token::TYPE_OPERATOR) || in_array($token->value, self::$operatorsArray)) {
                    if(($token->value === ')')) {
                        if(count($bracketStack) > 1) {
                            array_pop($bracketStack);
                            $state = 2;
                        } else $state = 5;
                    } elseif(in_array($token->value, self::$operatorsArray)) {
                        $ret->operators[] = $token->value;
                        $state = 3;
                    } else {
                        $parser->error(
                            'Unrecognized operator!',
                            $token
                        );
                        return $ret;
                    }
                } else {
                    $parser->error(
                        'An operator was expected!',
                        $token
                    );
                    return $ret;
                }
            } elseif ($state === 3) {
                if(($token->type === Token::TYPE_OPERATOR) && ($token->value === '(')) {
                    $state = 3;
                    array_push($bracketStack, '(');
                } elseif($token->type === Token::TYPE_OPERATOR) {
                    $parser->error(
                        'An operand was expected!',
                        $token
                    );
                    return $ret;
                } else {
                    $ret->operands[] = $token->value;
                    $state = 4;
                }
            } elseif ($state === 4) {
                if (($token->value === 'AND') || ($token->value === 'OR')) {
                    $ret->logicalOperators[] = $token->value;
                    $state = 1;
                } elseif (($token->type === Token::TYPE_OPERATOR) && ($token->value === ')')) {
                    if(count($bracketStack) === 1) {
                        $state = 5;
                    } else {
                        array_pop($bracketStack);
                        $state = 4;
                    }
                } elseif($token->type === Token::TYPE_OPERATOR) {
                    $parser->error(
                        'Unrecognized token!',
                        $token
                    );
                    return $ret;
                } else {
                    $state = 5;
                }
            } elseif ($state === 5) {
                break;
            }
        }

        --$list->idx;

        return $ret;
    }

    /**
     * @param Check   $component the component to be built
     * @param array $options   parameters for building
     *
     * @return string
     */
    public static function build($component, array $options = array())
    {
        $columns = $component->columns;
        $logical_op = $component->logicalOperators;
        $operators = $component->operators;
        $operands = $component->operands;
        $definition = 'CHECK (';
        for($i=0; $i<count($columns); ++$i) {
            $columns[$i] = trim($columns[$i]);
            if($i>0) {
                $definition .= ' ' . $logical_op[$i-1] . ' ';
            }
            $definition .= Context::escape($columns[$i]);
            if($operators[$i] === 'IS NULL' || $operators[$i] === 'IS NOT NULL') {
                $definition .= ' ' . $operators[$i];
            } else {
                $definition .= ' ' . $operators[$i] . ' \'' . $operands[$i] . '\'';
            }
        }
        $definition .= ')';
        return trim($definition);
    }
}
