<?php

declare(strict_types=1);

namespace PhpMyAdmin\SqlParser\Parsers;

use PhpMyAdmin\SqlParser\Components\PartitionDefinition;
use PhpMyAdmin\SqlParser\Parseable;
use PhpMyAdmin\SqlParser\Parser;
use PhpMyAdmin\SqlParser\TokensList;
use PhpMyAdmin\SqlParser\TokenType;

use function implode;

/**
 * Parses the create definition of a partition.
 *
 * Used for parsing `CREATE TABLE` statement.
 */
final class PartitionDefinitions implements Parseable
{
    /**
     * All field options.
     *
     * @var array<string, int|array<int, int|string>>
     * @psalm-var array<string, (positive-int|array{positive-int, ('var'|'var='|'expr'|'expr=')})>
     */
    public static array $partitionOptions = [
        'STORAGE ENGINE' => [
            1,
            'var',
        ],
        'ENGINE' => [
            1,
            'var',
        ],
        'COMMENT' => [
            2,
            'var',
        ],
        'DATA DIRECTORY' => [
            3,
            'var',
        ],
        'INDEX DIRECTORY' => [
            4,
            'var',
        ],
        'MAX_ROWS' => [
            5,
            'var',
        ],
        'MIN_ROWS' => [
            6,
            'var',
        ],
        'TABLESPACE' => [
            7,
            'var',
        ],
        'NODEGROUP' => [
            8,
            'var',
        ],
    ];

    /**
     * @param Parser               $parser  the parser that serves as context
     * @param TokensList           $list    the list of tokens that are being parsed
     * @param array<string, mixed> $options parameters for parsing
     */
    public static function parse(Parser $parser, TokensList $list, array $options = []): PartitionDefinition
    {
        $ret = new PartitionDefinition();

        /**
         * The state of the parser.
         *
         * Below are the states of the parser.
         *
         *      0 -------------[ PARTITION | SUBPARTITION ]------------> 1
         *
         *      1 -----------------------[ name ]----------------------> 2
         *
         *      2 ----------------------[ VALUES ]---------------------> 3
         *
         *      3 ---------------------[ LESS THAN ]-------------------> 4
         *      3 ------------------------[ IN ]-----------------------> 4
         *
         *      4 -----------------------[ expr ]----------------------> 5
         *
         *      5 ----------------------[ options ]--------------------> 6
         *
         *      6 ------------------[ subpartitions ]------------------> (END)
         */
        $state = 0;

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

            if ($state === 0) {
                $ret->isSubpartition = ($token->type === TokenType::Keyword) && ($token->keyword === 'SUBPARTITION');
                $state = 1;
            } elseif ($state === 1) {
                $ret->name = (string) $token->value;

                // Looking ahead for a 'VALUES' keyword.
                // Loop until the end of the partition name (delimited by a whitespace)
                while ($nextToken = $list->tokens[++$list->idx]) {
                    if ($nextToken->type !== TokenType::None) {
                        break;
                    }

                    $ret->name .= $nextToken->value;
                }

                $idx = $list->idx--;
                // Get the first token after the white space.
                $nextToken = $list->tokens[++$idx];

                $state = ($nextToken->type === TokenType::Keyword)
                    && ($nextToken->value === 'VALUES')
                    ? 2 : 5;
            } elseif ($state === 2) {
                $state = 3;
            } elseif ($state === 3) {
                $ret->type = $token->value;
                $state = 4;
            } elseif ($state === 4) {
                if ($token->value === 'MAXVALUE') {
                    $ret->expr = $token->value;
                } else {
                    $ret->expr = Expressions::parse(
                        $parser,
                        $list,
                        [
                            'parenthesesDelimited' => true,
                            'breakOnAlias' => true,
                        ],
                    );
                }

                $state = 5;
            } elseif ($state === 5) {
                $ret->options = OptionsArrays::parse($parser, $list, static::$partitionOptions);
                $state = 6;
            } elseif ($state === 6) {
                if (($token->type === TokenType::Operator) && ($token->value === '(')) {
                    $ret->subpartitions = ArrayObjs::parse(
                        $parser,
                        $list,
                        ['type' => self::class],
                    );
                    ++$list->idx;
                }

                break;
            }
        }

        --$list->idx;

        return $ret;
    }

    /** @param PartitionDefinition[] $component the component to be built */
    public static function buildAll(array $component): string
    {
        return "(\n" . implode(",\n", $component) . "\n)";
    }
}
