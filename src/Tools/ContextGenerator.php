<?php

declare(strict_types=1);

namespace PhpMyAdmin\SqlParser\Tools;

use PhpMyAdmin\SqlParser\Token;

use function array_filter;
use function array_map;
use function array_merge;
use function array_slice;
use function basename;
use function count;
use function dirname;
use function file;
use function file_put_contents;
use function implode;
use function ksort;
use function preg_match;
use function scandir;
use function sort;
use function sprintf;
use function str_contains;
use function str_replace;
use function str_split;
use function strlen;
use function strtoupper;
use function substr;
use function trim;

use const ARRAY_FILTER_USE_KEY;
use const SORT_STRING;

/**
 * Used for context generation.
 */
class ContextGenerator
{
    /**
     * Labels and flags that may be used when defining keywords.
     *
     * @var array<string, int>
     */
    public static array $labelsFlags = [
        '(R)' => Token::FLAG_KEYWORD_RESERVED,
        '(D)' => Token::FLAG_KEYWORD_DATA_TYPE,
        '(K)' => Token::FLAG_KEYWORD_KEY,
        '(F)' => Token::FLAG_KEYWORD_FUNCTION,
    ];

    /**
     * Documentation links for each context.
     *
     * @var array<string, string>
     */
    public static array $links = [
        'MySql50000' => 'https://dev.mysql.com/doc/refman/5.0/en/keywords.html',
        'MySql50100' => 'https://dev.mysql.com/doc/refman/5.1/en/keywords.html',
        'MySql50500' => 'https://dev.mysql.com/doc/refman/5.5/en/keywords.html',
        'MySql50600' => 'https://dev.mysql.com/doc/refman/5.6/en/keywords.html',
        'MySql50700' => 'https://dev.mysql.com/doc/refman/5.7/en/keywords.html',
        'MySql80000' => 'https://dev.mysql.com/doc/refman/8.0/en/keywords.html',
        'MySql80100' => 'https://dev.mysql.com/doc/refman/8.1/en/keywords.html',
        'MySql80200' => 'https://dev.mysql.com/doc/refman/8.2/en/keywords.html',
        'MySql80300' => 'https://dev.mysql.com/doc/refman/8.3/en/keywords.html',
        'MySql80400' => 'https://dev.mysql.com/doc/refman/8.4/en/keywords.html',
        'MySql90000' => 'https://dev.mysql.com/doc/refman/9.0/en/keywords.html',
        'MariaDb100000' => 'https://mariadb.com/kb/en/reserved-words/',
        'MariaDb100100' => 'https://mariadb.com/kb/en/reserved-words/',
        'MariaDb100200' => 'https://mariadb.com/kb/en/reserved-words/',
        'MariaDb100300' => 'https://mariadb.com/kb/en/reserved-words/',
        'MariaDb100400' => 'https://mariadb.com/kb/en/reserved-words/',
        'MariaDb100500' => 'https://mariadb.com/kb/en/reserved-words/',
        'MariaDb100600' => 'https://mariadb.com/kb/en/reserved-words/',
        'MariaDb100700' => 'https://mariadb.com/kb/en/reserved-words/',
        'MariaDb100800' => 'https://mariadb.com/kb/en/reserved-words/',
        'MariaDb100900' => 'https://mariadb.com/kb/en/reserved-words/',
        'MariaDb101000' => 'https://mariadb.com/kb/en/reserved-words/',
        'MariaDb101100' => 'https://mariadb.com/kb/en/reserved-words/',
        'MariaDb110000' => 'https://mariadb.com/kb/en/reserved-words/',
        'MariaDb110100' => 'https://mariadb.com/kb/en/reserved-words/',
        'MariaDb110200' => 'https://mariadb.com/kb/en/reserved-words/',
        'MariaDb110300' => 'https://mariadb.com/kb/en/reserved-words/',
        'MariaDb110400' => 'https://mariadb.com/kb/en/reserved-words/',
        'MariaDb110500' => 'https://mariadb.com/kb/en/reserved-words/',
    ];

    /**
     * Reversed const <=> int from {@see Token} class to write the constant name instead of its value.
     *
     * @var array<int, string>
     */
    private static array $typesNumToConst = [
        1 => 'Token::FLAG_KEYWORD',
        2 => 'Token::FLAG_KEYWORD_RESERVED',
        4 => 'Token::FLAG_KEYWORD_COMPOSED',
        8 => 'Token::FLAG_KEYWORD_DATA_TYPE',
        16 => 'Token::FLAG_KEYWORD_KEY',
        32 => 'Token::FLAG_KEYWORD_FUNCTION',
    ];

    /**
     * The template of a context.
     *
     * Parameters:
     *     1 - name
     *     2 - class
     *     3 - link
     *     4 - keywords array
     */
    public const TEMPLATE = <<<'PHP'
<?php

declare(strict_types=1);

namespace PhpMyAdmin\SqlParser\Contexts;

use PhpMyAdmin\SqlParser\Token;

/**
 * Context for %1$s.
 *
 * This class was auto-generated from tools/contexts/*.txt.
 * Use tools/run_generators.sh for update.
 *
 * @see %3$s
 */
final class %2$s
{
    /**
     * List of keywords.
     *
     * The value associated to each keyword represents its flags.
     *
     * @see Token
     *
     * @psalm-var non-empty-array<string,Token::FLAG_KEYWORD_*|int>
     * @phpstan-var non-empty-array<non-empty-string,Token::FLAG_KEYWORD_*|int>
     */
    public const KEYWORDS = [
%4$s    ];
}

PHP;

    /**
     * Sorts an array of words.
     *
     * @param array<int, list<string>> $arr
     *
     * @return array<int, list<string>>
     */
    public static function sortWords(array &$arr): array
    {
        ksort($arr);
        foreach ($arr as &$words) {
            sort($words, SORT_STRING);
        }

        return $arr;
    }

    /**
     * Reads a list of words and sorts it by type, length and keyword.
     *
     * @param list<string> $files
     *
     * @return array<int, list<string>>
     */
    public static function readWords(array $files): array
    {
        /** @psalm-var list<string> $words */
        $words = [];
        foreach ($files as $file) {
            $words = array_merge($words, file($file));
        }

        /** @var array<string, int> $types */
        $types = [];

        for ($i = 0, $count = count($words); $i !== $count; ++$i) {
            $value = trim($words[$i]);
            if ($value === '') {
                continue;
            }

            $type = Token::FLAG_KEYWORD;

            // Reserved, data types, keys, functions, etc. keywords.
            foreach (static::$labelsFlags as $label => $flags) {
                if (! str_contains($value, $label)) {
                    continue;
                }

                $type |= $flags;
                $value = trim(str_replace($label, '', $value));
            }

            // Composed keyword.
            if (str_contains($value, ' ')) {
                $type |= Token::FLAG_KEYWORD_RESERVED;
                $type |= Token::FLAG_KEYWORD_COMPOSED;
            }

            $value = strtoupper($value);
            if (! isset($types[$value])) {
                $types[$value] = $type;
            } else {
                $types[$value] |= $type;
            }
        }

        // Prepare an array in a way to sort by type, then by word.
        $ret = [];
        foreach ($types as $word => $type) {
            $ret[$type][] = $word;
        }

        return static::sortWords($ret);
    }

    /**
     * Prints an array of a words in PHP format.
     *
     * @param array<int, list<string>> $words the list of words to be formatted
     */
    public static function printWords(array $words): string
    {
        $ret = '';
        foreach ($words as $type => $wordsByType) {
            foreach ($wordsByType as $word) {
                $ret .= sprintf("        '%s' => %s,\n", $word, self::translateIntTypeToTextConstant($type));
            }
        }

        return $ret;
    }

    private static function translateIntTypeToTextConstant(int $type): string
    {
        $matchingFlags = array_filter(
            self::$typesNumToConst,
            static function (int $num) use ($type): bool {
                return ($type & $num) !== 0;
            },
            ARRAY_FILTER_USE_KEY,
        );

        return implode(' | ', $matchingFlags);
    }

    /**
     * Generates a context's class.
     *
     * @param array<string, string|array<int, list<string>>> $options the options for this context
     * @psalm-param array{
     *   name: string,
     *   class: string,
     *   link: string,
     *   keywords: array<int, list<string>>
     * } $options
     */
    public static function generate(array $options): string
    {
        $options['keywords'] = static::printWords($options['keywords']);

        return sprintf(self::TEMPLATE, $options['name'], $options['class'], $options['link'], $options['keywords']);
    }

    /**
     * Formats context name.
     *
     * @param string $name name to format
     */
    public static function formatName(string $name): string
    {
        /* Split name and version */
        $parts = [];
        if (preg_match('/^(\D+)(\d+)$/', $name, $parts) === 0) {
            return $name;
        }

        /* Format name */
        $base = $parts[1];
        if ($base === 'MySql') {
            $base = 'MySQL';
        } elseif ($base === 'MariaDb') {
            $base = 'MariaDB';
        }

        /* Parse version to array */
        $versionString = $parts[2];
        if (strlen($versionString) % 2 === 1) {
            $versionString = '0' . $versionString;
        }

        $version = array_map('intval', str_split($versionString, 2));
        /* Remove trailing zero */
        if ($version[count($version) - 1] === 0) {
            $version = array_slice($version, 0, -1);
        }

        /* Create name */
        return $base . ' ' . implode('.', $version);
    }

    /**
     * Builds a test.
     *
     * Reads the input file, generates the data and writes it back.
     *
     * @param string $input  the input file
     * @param string $output the output directory
     */
    public static function build(string $input, string $output): void
    {
        /**
         * The directory that contains the input file.
         *
         * Used to include common files.
         */
        $directory = dirname($input) . '/';

        /**
         * The name of the file that contains the context.
         */
        $file = basename($input);

        /**
         * The name of the context.
         */
        $name = substr($file, 0, -4);

        /**
         * The name of the class that defines this context.
         */
        $class = 'Context' . $name;

        /**
         * The formatted name of this context.
         */
        $formattedName = static::formatName($name);

        file_put_contents(
            $output . '/' . $class . '.php',
            static::generate(
                [
                    'name' => $formattedName,
                    'class' => $class,
                    'link' => static::$links[$name],
                    'keywords' => static::readWords(
                        [
                            $directory . '_common.txt',
                            $directory . '_functions' . $file,
                            $directory . $file,
                        ],
                    ),
                ],
            ),
        );
    }

    /**
     * Generates recursively all tests preserving the directory structure.
     *
     * @param string $input  the input directory
     * @param string $output the output directory
     */
    public static function buildAll(string $input, string $output): void
    {
        $files = scandir($input);

        foreach ($files as $file) {
            // Skipping current and parent directories.
            // Skipping _functions* and _common.txt files
            if (($file[0] === '.') || ($file[0] === '_')) {
                continue;
            }

            // Skipping README.md
            if ($file === 'README.md') {
                continue;
            }

            // Building the context.
            echo sprintf("Building context for %s...\n", $file);
            static::build($input . '/' . $file, $output);
        }
    }
}
