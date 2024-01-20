<?php

declare(strict_types=1);

namespace PhpMyAdmin\SqlParser\Parsers;

use PhpMyAdmin\SqlParser\Components\IndexHint;
use PhpMyAdmin\SqlParser\Parseable;
use PhpMyAdmin\SqlParser\Parser;
use PhpMyAdmin\SqlParser\TokensList;
use PhpMyAdmin\SqlParser\TokenType;

use function implode;

/**
 * Parses an Index hint.
 */
final class IndexHints implements Parseable
{
    /**
     * @param Parser               $parser  the parser that serves as context
     * @param TokensList           $list    the list of tokens that are being parsed
     * @param array<string, mixed> $options parameters for parsing
     *
     * @return IndexHint[]
     */
    public static function parse(Parser $parser, TokensList $list, array $options = []): array
    {
        $ret = [];
        $expr = new IndexHint();
        $expr->type = $options['type'] ?? null;
        /**
         * The state of the parser.
         *
         * Below are the states of the parser.
         *      0 ----------------- [ USE/IGNORE/FORCE ]-----------------> 1
         *      1 -------------------- [ INDEX/KEY ] --------------------> 2
         *      2 ----------------------- [ FOR ] -----------------------> 3
         *      2 -------------------- [ expr_list ] --------------------> 0
         *      3 -------------- [ JOIN/GROUP BY/ORDER BY ] -------------> 4
         *      4 -------------------- [ expr_list ] --------------------> 0
         */
        $state = 0;

        // By design, the parser will parse first token after the keyword. So, the keyword
        // must be analyzed too, in order to determine the type of this index hint.
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

            switch ($state) {
                case 0:
                    if ($token->type === TokenType::Keyword) {
                        if ($token->keyword !== 'USE' && $token->keyword !== 'IGNORE' && $token->keyword !== 'FORCE') {
                            break 2;
                        }

                        $expr->type = $token->keyword;
                        $state = 1;
                    }

                    break;
                case 1:
                    if ($token->type === TokenType::Keyword) {
                        if ($token->keyword === 'INDEX' || $token->keyword === 'KEY') {
                            $expr->indexOrKey = $token->keyword;
                        } else {
                            $parser->error('Unexpected keyword.', $token);
                        }

                        $state = 2;
                    } else {
                        // we expect the token to be a keyword
                        $parser->error('Unexpected token.', $token);
                    }

                    break;
                case 2:
                    if ($token->type === TokenType::Keyword && $token->keyword === 'FOR') {
                        $state = 3;
                    } else {
                        $expr->indexes = ExpressionArray::parse($parser, $list);
                        $state = 0;
                        $ret[] = $expr;
                        $expr = new IndexHint();
                    }

                    break;
                case 3:
                    if ($token->type === TokenType::Keyword) {
                        if (
                            $token->keyword === 'JOIN'
                            || $token->keyword === 'GROUP BY'
                            || $token->keyword === 'ORDER BY'
                        ) {
                            $expr->for = $token->keyword;
                        } else {
                            $parser->error('Unexpected keyword.', $token);
                        }

                        $state = 4;
                    } else {
                        // we expect the token to be a keyword
                        $parser->error('Unexpected token.', $token);
                    }

                    break;
                case 4:
                    $expr->indexes = ExpressionArray::parse($parser, $list);
                    $state = 0;
                    $ret[] = $expr;
                    $expr = new IndexHint();
                    break;
            }
        }

        --$list->idx;

        return $ret;
    }

    /** @param IndexHint[] $component the component to be built */
    public static function buildAll(array $component): string
    {
        return implode(' ', $component);
    }
}
