<?php

declare(strict_types=1);

namespace PhpMyAdmin\SqlParser\Utils;

use const PHP_SAPI;

final class FormattingOptions
{
    public string $lineEnding;
    public string $indentation;

    /** @param 'cli'|'text'|'html' $type */
    public function __construct(
        public readonly string $type = PHP_SAPI === 'cli' ? 'cli' : 'text',
        string|null $lineEnding = null,
        string|null $indentation = null,
        public bool $removeComments = false,
        public bool $clauseNewline = true,
    ) {
        $this->lineEnding = $lineEnding ?? ($this->type === 'html' ? '<br/>' : "\n");
        $this->indentation = $indentation ?? ($this->type === 'html' ? '&nbsp;&nbsp;&nbsp;&nbsp;' : '    ');
    }
}
