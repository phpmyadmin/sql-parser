<?php

declare(strict_types=1);

namespace PhpMyAdmin\SqlParser\Parsers;

use PhpMyAdmin\SqlParser\Components\JoinKeyword;
use PhpMyAdmin\SqlParser\Parseable;
use PhpMyAdmin\SqlParser\Parser;
use PhpMyAdmin\SqlParser\TokensList;
use PhpMyAdmin\SqlParser\TokenType;

use function implode;

/**
 * `JOIN` keyword parser.
 */
final class JoinKeywords implements Parseable
{
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

        $expr = new JoinKeyword();

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
                if (($token->type !== TokenType::Keyword) || empty(JoinKeyword::JOINS[$token->keyword])) {
                    break;
                }

                $expr->type = JoinKeyword::JOINS[$token->keyword];
                $state = 1;
            } elseif ($state === 1) {
                $expr->expr = Expressions::parse($parser, $list, ['field' => 'table']);
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
                            if (empty(JoinKeyword::JOINS[$token->keyword])) {
                                /* Next clause is starting */
                                break 2;
                            }

                            $ret[] = $expr;
                            $expr = new JoinKeyword();
                            $expr->type = JoinKeyword::JOINS[$token->keyword];
                            $state = 1;

                            break;
                    }
                }
            } elseif ($state === 3) {
                $expr->on = Conditions::parse($parser, $list);
                $ret[] = $expr;
                $expr = new JoinKeyword();
                $state = 0;
            } else {
                $expr->using = ArrayObjs::parse($parser, $list);
                $ret[] = $expr;
                $expr = new JoinKeyword();
                $state = 0;
            }
        }

        if (! empty($expr->type)) {
            $ret[] = $expr;
        }

        --$list->idx;

        return $ret;
    }

    /** @param JoinKeyword[] $component the component to be built */
    public static function buildAll(array $component): string
    {
        return implode(' ', $component);
    }
}
