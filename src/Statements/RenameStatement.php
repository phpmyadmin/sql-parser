<?php

declare(strict_types=1);

namespace PhpMyAdmin\SqlParser\Statements;

use PhpMyAdmin\SqlParser\Components\RenameOperation;
use PhpMyAdmin\SqlParser\Parser;
use PhpMyAdmin\SqlParser\Parsers\RenameOperations;
use PhpMyAdmin\SqlParser\Statement;
use PhpMyAdmin\SqlParser\Token;
use PhpMyAdmin\SqlParser\TokensList;
use PhpMyAdmin\SqlParser\TokenType;

/**
 * `RENAME` statement.
 *
 * RENAME TABLE tbl_name TO new_tbl_name
 *  [, tbl_name2 TO new_tbl_name2] ...
 */
class RenameStatement extends Statement
{
    /**
     * The old and new names of the tables.
     *
     * @var RenameOperation[]|null
     */
    public array|null $renames = null;

    /**
     * Function called before the token is processed.
     *
     * Skips the `TABLE` keyword after `RENAME`.
     *
     * @param Parser     $parser the instance that requests parsing
     * @param TokensList $list   the list of tokens to be parsed
     * @param Token      $token  the token that is being parsed
     */
    public function before(Parser $parser, TokensList $list, Token $token): void
    {
        if (($token->type !== TokenType::Keyword) || ($token->keyword !== 'RENAME')) {
            return;
        }

        // Checking if it is the beginning of the query.
        $list->getNextOfTypeAndValue(TokenType::Keyword, 'TABLE');
    }

    public function build(): string
    {
        return 'RENAME TABLE ' . RenameOperations::buildAll($this->renames);
    }
}
