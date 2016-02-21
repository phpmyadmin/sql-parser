<?php

namespace SqlParser\Tools;

require_once '../vendor/autoload.php';

/**
 * Used for context generation.
 *
 * @category   Contexts
 * @package    SqlParser
 * @subpackage Tools
 * @author     Dan Ungureanu <udan1107@gmail.com>
 * @license    http://opensource.org/licenses/GPL-2.0 GNU Public License
 */
class ContextGenerator
{

    /**
     * Labels and flags that may be used when defining keywords.
     *
     * @var array
     */
    public static $LABELS_FLAGS = array(
        '(R)'   =>  2, // reserved
        '(D)'   =>  8, // data type
        '(K)'   => 16, // keyword
        '(F)'   => 32, // function name
    );

    /**
     * Documentation links for each context.
     *
     * @var array
     */
    public static $LINKS = array(
        'MySql50000' => 'https://dev.mysql.com/doc/refman/5.0/en/keywords.html',
        'MySql50100' => 'https://dev.mysql.com/doc/refman/5.1/en/keywords.html',
        'MySql50500' => 'https://dev.mysql.com/doc/refman/5.5/en/keywords.html',
        'MySql50600' => 'https://dev.mysql.com/doc/refman/5.6/en/keywords.html',
        'MySql50700' => 'https://dev.mysql.com/doc/refman/5.7/en/keywords.html',
    );

    /**
     * The template of a context.
     *
     * Parameters:
     *     1 - name
     *     2 - class
     *     3 - link
     *     4 - keywords array
     *
     * @var string
     */
    const TEMPLATE =
        '<?php'                                                                             . "\n" .
        ''                                                                                  . "\n" .
        '/**'                                                                               . "\n" .
        ' * Context for %1$s.'                                                              . "\n" .
        ' *'                                                                                . "\n" .
        ' * This file was auto-generated.'                                                  . "\n" .
        ' *'                                                                                . "\n" .
        ' * @package    SqlParser'                                                          . "\n" .
        ' * @subpackage Contexts'                                                           . "\n" .
        ' * @link       %3$s'                                                               . "\n" .
        ' */'                                                                               . "\n" .
        'namespace SqlParser\\Contexts;'                                                    . "\n" .
        ''                                                                                  . "\n" .
        'use SqlParser\\Context;'                                                           . "\n" .
        ''                                                                                  . "\n" .
        '/**'                                                                               . "\n" .
        ' * Context for %1$s.'                                                              . "\n" .
        ' *'                                                                                . "\n" .
        ' * @category   Contexts'                                                           . "\n" .
        ' * @package    SqlParser'                                                          . "\n" .
        ' * @subpackage Contexts'                                                           . "\n" .
        ' * @author     Dan Ungureanu <udan1107@gmail.com>'                                 . "\n" .
        ' * @license    http://opensource.org/licenses/GPL-2.0 GNU Public License'          . "\n" .
        ' */'                                                                               . "\n" .
        'class %2$s extends Context'                                                        . "\n" .
        '{'                                                                                 . "\n" .
        ''                                                                                  . "\n" .
        '    /**'                                                                           . "\n" .
        '     * List of keywords.'                                                          . "\n" .
        '     *'                                                                            . "\n" .
        '     * The value associated to each keyword represents its flags.'                 . "\n" .
        '     *'                                                                            . "\n" .
        '     * @see Token::FLAG_KEYWORD_*'                                                 . "\n" .
        '     *'                                                                            . "\n" .
        '     * @var array'                                                                 . "\n" .
        '     */'                                                                           . "\n" .
        '    public static $KEYWORDS = array('                                              . "\n" .
        ''                                                                                  . "\n" .
        '%4$s'                                                                              .
        '    );'                                                                            . "\n" .
        '}'                                                                                 . "\n";

    /**
     * Sorts an array of words.
     *
     * @param array $arr
     *
     * @return array
     */
    public static function sortWords(array &$arr)
    {
        ksort($arr);
        foreach ($arr as $type => &$wordsByLen) {
            ksort($wordsByLen);
            foreach ($wordsByLen as $len => &$words) {
                sort($words, SORT_STRING);
            }
        }
        return $arr;
    }

    /**
     * Reads a list of words and sorts it by type, length and keyword.
     *
     * @param string[] $files
     *
     * @return array
     */
    public static function readWords(array $files)
    {
        $words = array();
        foreach ($files as $file) {
            $words = array_merge($words, file($file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES));
        }

        $types = array();

        for ($i = 0, $count = count($words); $i != $count; ++$i) {
            $type = 1;
            $value = trim($words[$i]);

            // Reserved, data types, keys, functions, etc. keywords.
            foreach (static::$LABELS_FLAGS as $label => $flags) {
                if (strstr($value, $label) !== false) {
                    $type |= $flags;
                    $value = trim(str_replace($label, '', $value));
                }
            }

            // Composed keyword.
            if (strstr($value, ' ') !== false) {
                $type |= 2; // Reserved keyword.
                $type |= 4; // Composed keyword.
            }

            $len = strlen($words[$i]);
            if ($len === 0) {
                continue;
            }

            $value = strtoupper($value);
            if (!isset($types[$value])) {
                $types[$value] = $type;
            } else {
                $types[$value] |= $type;
            }
        }

        $ret = array();
        foreach ($types as $word => $type) {
            $len = strlen($word);
            if (!isset($ret[$type])) {
                $ret[$type] = array();
            }
            if (!isset($ret[$type][$len])) {
                $ret[$type][$len] = array();
            }
            $ret[$type][$len][] = $word;
        }

        return static::sortWords($ret);
    }

    /**
     * Prints an array of a words in PHP format.
     *
     * @param array $words  The list of words to be formatted.
     * @param int   $spaces The number of spaces that starts every line.
     * @param int   $line   The length of a line.
     *
     * @return string
     */
    public static function printWords($words, $spaces = 8, $line = 80)
    {
        $ret = '';

        foreach ($words as $type => $wordsByType) {
            foreach ($wordsByType as $len => $wordsByLen) {
                $count = round(($line - $spaces) / ($len + 9)); // strlen("'' => 1, ") = 9
                $i = 0;

                foreach ($wordsByLen as $word) {
                    if ($i == 0) {
                        $ret .= str_repeat(' ', $spaces);
                    }
                    $ret .= "'" . $word . "' => " . $type . ', ';
                    if (++$i == $count) {
                        $ret .= "\n";
                        $i = 0;
                    }
                }

                if ($i != 0) {
                    $ret .= "\n";
                }
            }

            $ret .= "\n";
        }

        // Trim trailing spaces and return.
        return str_replace(" \n", "\n", $ret);
    }

    /**
     * Generates a context's class.
     *
     * @param array $options The options that are used in generating this context.
     *
     * @return string
     */
    public static function generate($options)
    {
        if (isset($options['keywords'])) {
            $options['keywords'] = static::printWords($options['keywords']);
        }

        return sprintf(
            static::TEMPLATE,
            $options['name'],
            $options['class'],
            $options['link'],
            $options['keywords']
        );
    }

    /**
     * Builds a test.
     *
     * Reads the input file, generates the data and writes it back.
     *
     * @param string $input  The input file.
     * @param string $output The output directory.
     *
     * @return void
     */
    public static function build($input, $output)
    {

        /**
         * The directory that contains the input file.
         *
         * Used to include common files.
         *
         * @var string
         */
        $directory = dirname($input) . '/';

        /**
         * The name of the file that contains the context.
         *
         * @var string
         */
        $file = basename($input);

        /**
         * The name of the context.
         *
         * @var string
         */
        $name = substr($file, 0, -4);

        /**
         * The name of the class that defines this context.
         *
         * @var string
         */
        $class = 'Context' . $name;

        /**
         * The formatted name of this context.
         *
         * @var string
         */
        $formattedName = str_replace(
            array('Context', 'MySql',  '00', '0'),
            array('',        'MySQL ', '',   '.'),
            $class
        );

        file_put_contents(
            $output . '/' . $class . '.php',
            static::generate(
                array(
                    'name'      => $formattedName,
                    'class'     => $class,
                    'link'      => static::$LINKS[$name],
                    'keywords'  => static::readWords(
                        array(
                            $directory . '_common.txt',
                            $directory . '_functions' . $file,
                            $directory . $file
                        )
                    ),
                )
            )
        );
    }

    /**
     * Generates recursively all tests preserving the directory structure.
     *
     * @param string $input  The input directory.
     * @param string $output The output directory.
     *
     * @return void
     */
    public static function buildAll($input, $output)
    {
        $files = scandir($input);

        foreach ($files as $file) {
            // Skipping current and parent directories.
            if (($file === '.') || ($file === '..') || ($file[0] === '_')) {
                continue;
            }

            // Building the context.
            sprintf("Building context for %s...\n", $file);
            static::build($input . '/' . $file, $output);
        }
    }
}

// Test generator.
//
// Example of usage:
//
//      php ContextGenerator.php data data
//
// Input data must be in the `data` folder.
// The output will be generated in the same `data` folder.
//
if (count($argv) >= 3) {
    // Extracting directories' name from command line and trimming unnecessary
    // slashes at the end.
    $input = rtrim($argv[1], '/');
    $output = rtrim($argv[2], '/');

    // Checking if all directories are valid.
    if (!is_dir($input)) {
        throw new \Exception('The input directory does not exist.');
    } elseif (!is_dir($output)) {
        throw new \Exception('The output directory does not exist.');
    }

    // Finally, building the tests.
    ContextGenerator::buildAll($input, $output);
}
