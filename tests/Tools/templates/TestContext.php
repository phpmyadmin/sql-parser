<?php

declare(strict_types=1);

namespace PhpMyAdmin\SqlParser\Contexts;

use PhpMyAdmin\SqlParser\Context;
use PhpMyAdmin\SqlParser\Token;

/**
 * Context for MYSQL TEST.
 *
 * This class was auto-generated from tools/contexts/*.txt.
 * Use tools/run_generators.sh for update.
 *
 * @see https://www.phpmyadmin.net/contribute
 */
class TestContext extends Context
{
    /**
     * List of keywords.
     *
     * The value associated to each keyword represents its flags.
     *
     * @see Token
     *
     * @var array<string,int>
     * @phpstan-var non-empty-array<non-empty-string,Token::FLAG_KEYWORD_*|int>
     */
    public static $KEYWORDS = [
        'NO_FLAG' => Token::FLAG_KEYWORD,
        'RESERVED' => Token::FLAG_KEYWORD | Token::FLAG_KEYWORD_RESERVED,
        'RESERVED2' => Token::FLAG_KEYWORD | Token::FLAG_KEYWORD_RESERVED,
        'RESERVED3' => Token::FLAG_KEYWORD | Token::FLAG_KEYWORD_RESERVED,
        'RESERVED4' => Token::FLAG_KEYWORD | Token::FLAG_KEYWORD_RESERVED,
        'RESERVED5' => Token::FLAG_KEYWORD | Token::FLAG_KEYWORD_RESERVED,
        'COMPOSED KEYWORD' => Token::FLAG_KEYWORD | Token::FLAG_KEYWORD_RESERVED | Token::FLAG_KEYWORD_COMPOSED,
        'DATATYPE' => Token::FLAG_KEYWORD | Token::FLAG_KEYWORD_DATA_TYPE,
        'KEYWORD' => Token::FLAG_KEYWORD | Token::FLAG_KEYWORD_KEY,
        'FUNCTION' => Token::FLAG_KEYWORD | Token::FLAG_KEYWORD_FUNCTION,
    ];
}
