<?php

declare(strict_types=1);

namespace PhpMyAdmin\SqlParser\Components;

use PhpMyAdmin\SqlParser\Parser;
use PhpMyAdmin\SqlParser\Parsers\ArrayObjs;
use PhpMyAdmin\SqlParser\SerializableComponent;
use RuntimeException;

/**
 * `WITH` keyword builder.
 */
final class WithKeyword extends SerializableComponent
{
    /** @var ArrayObj[] */
    public array $columns = [];

    public Parser|null $statement = null;

    public function __construct(public string $name)
    {
    }

    public function build(): string
    {
        if (! isset($this->statement)) {
            throw new RuntimeException('No statement inside WITH');
        }

        $str = $this->name;

        if ($this->columns) {
            $str .= ArrayObjs::buildAll($this->columns);
        }

        $str .= ' AS (';

        foreach ($this->statement->statements as $statement) {
            $str .= $statement->build();
        }

        $str .= ')';

        return $str;
    }
}
