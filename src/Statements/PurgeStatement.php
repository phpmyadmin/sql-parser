<?php
/**
 * `PURGE` statement.
 */

declare(strict_types=1);

namespace PhpMyAdmin\SqlParser\Statements;

use PhpMyAdmin\SqlParser\Components\Expression;
use PhpMyAdmin\SqlParser\Parser;
use PhpMyAdmin\SqlParser\Statement;
use PhpMyAdmin\SqlParser\Token;
use PhpMyAdmin\SqlParser\TokensList;

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
     *
     * @var string|null
     */
    public $logType;

    /**
     * The end option of this query.
     *
     * @var string|null
     */
    public $endOption;

    /**
     * The end expr of this query.
     *
     * @var string|null
     */
    public $endExpr;

    /**
     * @return string
     */
    public function build()
    {
        $ret = 'PURGE ' . $this->logType . ' LOGS '
            . ($this->endOption !== null ? ($this->endOption . ' ' . $this->endExpr) : '');

        return trim($ret);
    }

    /**
     * @param Parser     $parser the instance that requests parsing
     * @param TokensList $list   the list of tokens to be parsed
     */
    public function parse(Parser $parser, TokensList $list)
    {
        ++$list->idx; // Skipping `PURGE`.

        /**
         * The state of the parser.
         *
         * @var int
         */
        $state = 0;

        $prevToken = null;
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
                    $this->endExpr = Expression::parse($parser, $list, []);
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
     * @param Parser $parser           the instance that requests parsing
     * @param Token  $token            token to be parsed
     * @param array  $expectedKeywords array of possibly expected keywords at this point
     *
     * @return mixed|null
     */
    private static function parseExpectedKeyword($parser, $token, $expectedKeywords)
    {
        if ($token->type === Token::TYPE_KEYWORD) {
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
