<?php

declare(strict_types=1);

namespace PhpMyAdmin\SqlParser\Statements;

use PhpMyAdmin\SqlParser\Components\Expression;
use PhpMyAdmin\SqlParser\Parser;
use PhpMyAdmin\SqlParser\Parsers\Expressions;
use PhpMyAdmin\SqlParser\Statement;
use PhpMyAdmin\SqlParser\Token;
use PhpMyAdmin\SqlParser\TokensList;
use PhpMyAdmin\SqlParser\TokenType;

use function in_array;
use function trim;

/**
 * `PURGE` statement.
 *
 * PURGE { BINARY | MASTER } LOGS
 *   { TO 'log_name' | BEFORE datetime_expr }
 */
class PurgeStatement extends Statement
{
    /**
     * The type of logs
     */
    public string|null $logType = null;

    /**
     * The end option of this query.
     */
    public string|null $endOption = null;

    /**
     * The end expr of this query.
     */
    public Expression|null $endExpr = null;

    public function build(): string
    {
        $ret = 'PURGE ' . $this->logType . ' LOGS '
            . ($this->endOption !== null ? ($this->endOption . ' ' . $this->endExpr) : '');

        return trim($ret);
    }

    /**
     * @param Parser     $parser the instance that requests parsing
     * @param TokensList $list   the list of tokens to be parsed
     */
    public function parse(Parser $parser, TokensList $list): void
    {
        ++$list->idx; // Skipping `PURGE`.

        /**
         * The state of the parser.
         */
        $state = 0;

        $prevToken = null;
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
                    // parse `{ BINARY | MASTER }`
                    $this->logType = self::parseExpectedKeyword($parser, $token, ['BINARY', 'MASTER']);
                    break;
                case 1:
                    // parse `LOGS`
                    self::parseExpectedKeyword($parser, $token, ['LOGS']);
                    break;
                case 2:
                    // parse `{ TO | BEFORE }`
                    $this->endOption = self::parseExpectedKeyword($parser, $token, ['TO', 'BEFORE']);
                    break;
                case 3:
                    // parse `expr`
                    $this->endExpr = Expressions::parse($parser, $list, []);
                    break;
                default:
                    $parser->error('Unexpected token.', $token);
                    break;
            }

            $state++;
            $prevToken = $token;
        }

        // Only one possible end state
        if ($state === 4) {
            return;
        }

        $parser->error('Unexpected token.', $prevToken);
    }

    /**
     * Parse expected keyword (or throw relevant error)
     *
     * @param Parser   $parser           the instance that requests parsing
     * @param Token    $token            token to be parsed
     * @param string[] $expectedKeywords array of possibly expected keywords at this point
     */
    private static function parseExpectedKeyword(Parser $parser, Token $token, array $expectedKeywords): string|null
    {
        if ($token->type === TokenType::Keyword) {
            if (in_array($token->keyword, $expectedKeywords)) {
                return $token->keyword;
            }

            $parser->error('Unexpected keyword', $token);
        } else {
            $parser->error('Unexpected token.', $token);
        }

        return null;
    }
}
