<?php
/**
 * `CHECK` keyword parser.
 */
declare(strict_types=1);

namespace PhpMyAdmin\SqlParser\Components;

use PhpMyAdmin\SqlParser\Component;
use PhpMyAdmin\SqlParser\Context;
use PhpMyAdmin\SqlParser\Parser;
use PhpMyAdmin\SqlParser\Token;
use PhpMyAdmin\SqlParser\TokensList;

/**
 * `CHECK` keyword parser.
 *
 * @category   Keywords
 *
 * @license    https://www.gnu.org/licenses/gpl-2.0.txt GPL-2.0+
 */
class Check extends Component
{
    /**
     * The check rule.
     *
     * @var string
     */
    public $rule;

    /**
     * Constructor.
     *
     * @param string $rule the rule to check
     */
    public function __construct($rule = null)
    {
        $this->rule = $rule;
    }

    /**
     * @param Parser     $parser  the parser that serves as context
     * @param TokensList $list    the list of tokens that are being parsed
     * @param array      $options parameters for parsing
     *
     * @return Check
     */
    public static function parse(Parser $parser, TokensList $list, array $options = [])
    {
        $ret = new self();

        /** @var int $numberOpenedParenthesis */
        $numberOpenedParenthesis = 0;

        /** @var bool $parenthesisOpened */
        $parenthesisOpened = false;

        $startIdx = $list->idx;

        for (; $list->idx < $list->count; ++$list->idx) {
            // If last parenthesis closed, we can leave
            if ($parenthesisOpened && 0 === $numberOpenedParenthesis) {
                break;
            }

            /**
             * Token parsed at this moment.
             *
             * @var Token
             */
            $token = $list->tokens[$list->idx];

            if (($token->type === Token::TYPE_OPERATOR) && ($token->value === '(')) {
                ++$numberOpenedParenthesis;
                $parenthesisOpened = true;
                continue;
            }

            if (($token->type === Token::TYPE_OPERATOR) && ($token->value === ')')) {
                --$numberOpenedParenthesis;
            }
        }

        --$list->idx;

        $ret->rule = implode(array_column(array_slice($list->tokens, $startIdx, $list->idx - $startIdx + 1), 'value'));

        return $ret;
    }

    /**
     * @param Check $component the component to be built
     * @param array $options   parameters for building
     *
     * @return string
     */
    public static function build($component, array $options = [])
    {
        return trim($component->rule);
    }
}
