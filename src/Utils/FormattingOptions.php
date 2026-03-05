<?php

declare(strict_types=1);

namespace PhpMyAdmin\SqlParser\Utils;

use PhpMyAdmin\SqlParser\Token;
use PhpMyAdmin\SqlParser\TokenType;

use function strtolower;
use function strtoupper;

use const PHP_SAPI;

final class FormattingOptions
{
    public string $lineEnding;
    public string $indentation;

    /**
     * @param 'cli'|'text'|'html'                                                                        $type
     * @param list<array{type: TokenType, flags: int, html: string, cli: string, function: callable|''}> $formats
     */
    public function __construct(
        public readonly string $type = PHP_SAPI === 'cli' ? 'cli' : 'text',
        string|null $lineEnding = null,
        string|null $indentation = null,
        public bool $removeComments = false,
        public bool $clauseNewline = true,
        public array $formats = [],
    ) {
        $this->lineEnding = $lineEnding ?? ($this->type === 'html' ? '<br/>' : "\n");
        $this->indentation = $indentation ?? ($this->type === 'html' ? '&nbsp;&nbsp;&nbsp;&nbsp;' : '    ');
        $this->formats = self::mergeFormats(self::getDefaultFormats(), $this->formats);
    }

    /**
     * @param list<array{type: TokenType, flags: int, html: string, cli: string, function: callable|''}> $formats
     * @param list<array{type: TokenType, flags: int, html: string, cli: string, function: callable|''}> $newFormats
     *
     * @return list<array{type: TokenType, flags: int, html: string, cli: string, function: callable|''}>
     */
    private static function mergeFormats(array $formats, array $newFormats): array
    {
        foreach ($newFormats as $new) {
            foreach ($formats as $i => $original) {
                if ($new['type'] !== $original['type'] || $original['flags'] !== $new['flags']) {
                    continue;
                }

                $formats[$i] = $new;
                continue 2;
            }

            $formats[] = $new;
        }

        return $formats;
    }

    /**
     * The styles used for HTML formatting.
     *
     * @return list<array{type: TokenType, flags: int, html: string, cli: string, function: callable|''}>
     */
    public static function getDefaultFormats(): array
    {
        return [
            [
                'type' => TokenType::Keyword,
                'flags' => Token::FLAG_KEYWORD_RESERVED,
                'html' => 'sql-reserved',
                'cli' => "\x1b[35m",
                'function' => strtoupper(...),
            ],
            [
                'type' => TokenType::Keyword,
                'flags' => 0,
                'html' => 'sql-keyword',
                'cli' => "\x1b[95m",
                'function' => strtoupper(...),
            ],
            [
                'type' => TokenType::Comment,
                'flags' => 0,
                'html' => 'sql-comment',
                'cli' => "\x1b[37m",
                'function' => '',
            ],
            [
                'type' => TokenType::Bool,
                'flags' => 0,
                'html' => 'sql-atom',
                'cli' => "\x1b[36m",
                'function' => strtoupper(...),
            ],
            [
                'type' => TokenType::Number,
                'flags' => 0,
                'html' => 'sql-number',
                'cli' => "\x1b[92m",
                'function' => strtolower(...),
            ],
            [
                'type' => TokenType::String,
                'flags' => 0,
                'html' => 'sql-string',
                'cli' => "\x1b[91m",
                'function' => '',
            ],
            [
                'type' => TokenType::Symbol,
                'flags' => Token::FLAG_SYMBOL_PARAMETER,
                'html' => 'sql-parameter',
                'cli' => "\x1b[31m",
                'function' => '',
            ],
            [
                'type' => TokenType::Symbol,
                'flags' => 0,
                'html' => 'sql-variable',
                'cli' => "\x1b[36m",
                'function' => '',
            ],
        ];
    }
}
