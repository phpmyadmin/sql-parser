<?php

declare(strict_types=1);

namespace PhpMyAdmin\SqlParser\Tests;

use PhpMyAdmin\SqlParser\UtfString;

class UtfStringSerializer
{
    /**
     * @return array<string,string>
     * @psalm-return array{str: string}
     */
    public function serialize(UtfString $str): array
    {
        return ['str' => (string) $str];
    }

    /**
     * @param array<string,string> $data
     * @psalm-param array{str: string} $data
     */
    public function unserialize(array $data): UtfString
    {
        return new UtfString($data['str']);
    }
}
