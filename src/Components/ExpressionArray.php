<?php

/**
 * Parses a list of expressions delimited by a comma.
 */

namespace PhpMyAdmin\SqlParser\Components;

use PhpMyAdmin\SqlParser\Component;
use PhpMyAdmin\SqlParser\Parser;
use PhpMyAdmin\SqlParser\Token;
use PhpMyAdmin\SqlParser\TokensList;

/**
 * Parses a list of expressions delimited by a comma.
 *
 * @category   Keywords
 *
 * @license    https://www.gnu.org/licenses/gpl-2.0.txt GPL-2.0+
 */
class ExpressionArray extends Component
{
    /**
     * @param Parser $parser the parser that serves as context
     * @param TokensList $list the list of tokens that are being parsed
     * @param array $options parameters for parsing
     *
     * @return Expression[]
     * @throws \PhpMyAdmin\SqlParser\Exceptions\ParserException
     */
    public static function parse(Parser $parser, TokensList $list, array $options = array())
    {
        $ret = array();

        /**
         * The state of the parser.
         *
         * Below are the states of the parser.
         *
         *      0 ----------------------[ array ]---------------------> 1
         *
         *      1 ------------------------[ , ]------------------------> 0
         *      1 -----------------------[ else ]----------------------> (END)
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

            echo __METHOD__ . '@' . __LINE__ . ' handle token: ' . $token . PHP_EOL;

            // End of statement.
            if ($token->type === Token::TYPE_DELIMITER) {
                echo __METHOD__ . '@' . __LINE__ . ' met delimiter, break' . PHP_EOL;
                break;
            }

            // Skipping whitespaces and comments.
            if (($token->type === Token::TYPE_WHITESPACE) || ($token->type === Token::TYPE_COMMENT)) {
                echo __METHOD__ . '@' . __LINE__ . ' skip non-sql part' . PHP_EOL;
                continue;
            }

            if (($token->type === Token::TYPE_KEYWORD)
                && ($token->flags & Token::FLAG_KEYWORD_RESERVED)
                && ((~$token->flags & Token::FLAG_KEYWORD_FUNCTION))
                && ($token->value !== 'DUAL')
                && ($token->value !== 'NULL')
                && ($token->value !== 'CASE')
            ) {
                // No keyword is expected.
                echo __METHOD__ . '@' . __LINE__ . ' no keyword, break' . PHP_EOL;
                break;
            }

            echo __METHOD__ . '@' . __LINE__ . ' state=' . $state . PHP_EOL;

            if ($state === 0) {
                if ($token->type === Token::TYPE_KEYWORD
                    && $token->value === 'CASE'
                ) {
                    echo __METHOD__ . '@' . __LINE__ . ' ready to parse CASE Expression' . PHP_EOL;
                    //original
                    //$expr = CaseExpression::parse($parser, $list, $options);
                    //try fix
                    $caseBeginIdx = $list->idx;
                    $caseExpr = CaseExpression::parse($parser, $list, $options);
                    $leftBracketToken = new Token('(', 2, 16);
                    array_splice($list, $caseBeginIdx, 0, $leftBracketToken);
                    $rightBracketToken = new Token(')', 2, 16);
                    array_splice($list, $list->idx, 0, $rightBracketToken);
                    $list->idx = $caseBeginIdx;
                    $expr = Expression::parse($parser, $list, $options);
                } else {
                    echo __METHOD__ . '@' . __LINE__ . ' ready to parse common Expression' . PHP_EOL;
                    $expr = Expression::parse($parser, $list, $options);
                }

                echo __METHOD__ . '@' . __LINE__ . ' express parse result: ' . $expr . PHP_EOL;

                if ($expr === null) {
                    break;
                }
                $ret[] = $expr;
                $state = 1;
            } elseif ($state === 1) {
                if ($token->value === ',') {
                    $state = 0;
                } else {
                    break;
                }
            }
        }

        if ($state === 0) {
            $parser->error(
                'An expression was expected.',
                $list->tokens[$list->idx]
            );
        }

        --$list->idx;

        return $ret;
    }

    /**
     * @param ExpressionArray[] $component the component to be built
     * @param array             $options   parameters for building
     *
     * @return string
     */
    public static function build($component, array $options = array())
    {
        $ret = array();
        foreach ($component as $frag) {
            $ret[] = $frag::build($frag);
        }

        return implode($ret, ', ');
    }
}
