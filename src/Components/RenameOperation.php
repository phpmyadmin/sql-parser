<?php

declare(strict_types=1);

namespace PhpMyAdmin\SqlParser\Components;

use PhpMyAdmin\SqlParser\SerializableComponent;

/**
 * `RENAME TABLE` keyword parser.
 */
final class RenameOperation extends SerializableComponent
{
    /**
     * The old table name.
     */
    public Expression|null $old = null;

    /**
     * The new table name.
     */
    public Expression|null $new = null;

    /**
     * @param Expression|null $old old expression
     * @param Expression|null $new new expression containing new name
     */
    public function __construct(Expression|null $old = null, Expression|null $new = null)
    {
        $this->old = $old;
        $this->new = $new;
    }

    public function build(): string
    {
        return $this->old . ' TO ' . $this->new;
    }
}
