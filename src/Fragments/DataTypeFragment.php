<?php

namespace SqlParser\Fragments;

use SqlParser\Context;
use SqlParser\Fragment;
use SqlParser\Lexer;
use SqlParser\Parser;
use SqlParser\Token;
use SqlParser\TokensList;

/**
 * `RETURN` keyword parser.
 */
class DataTypeFragment extends Fragment
{

    /**
     * The data type returned.
     *
     * @var string
     */
    public $type;

    /**
     * The size of this variable.
     *
     * @var array
     */
    public $size;

    /**
     * @param Parser $parser
     * @param TokensList $list
     * @param array $options
     *
     * @return DataTypeFragment[]
     */
    public static function parse(Parser $parser, TokensList $list, array $options = array())
    {
        $ret = new DataTypeFragment();

        /**
         * The state of the parser.
         *
         * Below are the states of the parser.
         *
         *      0 -------------------[ data type ]--------------------> 1
         *
         *      1 ------------------[ size (array) ]------------------> 4
         *      1 ----------------------[ else ]----------------------> -1
         *
         * @var int
         */
        $state = 0;

        for (; $list->idx < $list->count; ++$list->idx) {
            /** @var Token Token parsed at this moment. */
            $token = $list->tokens[$list->idx];

            // Skipping whitespaces and comments.
            if (($token->type === Token::TYPE_WHITESPACE) || ($token->type === Token::TYPE_COMMENT)) {
                continue;
            }

            if ($state === 0) {
                $ret->type = $token->value;
                $ret->tokens[] = $token;
                if (!isset(Context::$DATA_TYPES[$token->value])) {
                    $parser->error('Unrecognized data type.', $token);
                }
                $state = 1;
            } elseif ($state === 1) {
                if (($token->type === Token::TYPE_OPERATOR) && ($token->value === '(')) {
                    $size = ArrayFragment::parse($parser, $list);
                    $ret->size = $size->array;
                    $ret->tokens = array_merge($ret->tokens, $size->tokens);
                } else {
                    --$list->idx;
                }
                break;
            }

        }

        if (empty($ret->type)) {
            return null;
        }

        return $ret;
    }
}
