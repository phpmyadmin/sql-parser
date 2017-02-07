<?php

namespace PhpMyAdmin\SqlParser\Tools;

require_once '../vendor/autoload.php';

use PhpMyAdmin\SqlParser\Lexer;
use PhpMyAdmin\SqlParser\Parser;

/**
 * Used for test generation.
 *
 * @category   Tests
 *
 * @license    https://www.gnu.org/licenses/gpl-2.0.txt GPL-2.0+
 */
class TestGenerator
{
    /**
     * Generates a test's data.
     *
     * @param string $query the query to be analyzed
     * @param string $type  test's type (may be `lexer` or `parser`)
     *
     * @return array
     */
    public static function generate($query, $type = 'parser')
    {
        /**
         * Lexer used for tokenizing the query.
         *
         * @var Lexer
         */
        $lexer = new Lexer($query);

        /**
         * Parsed used for analyzing the query.
         * A new instance of parser is generated only if the test requires.
         *
         * @var Parser
         */
        $parser = ($type === 'parser') ? new Parser($lexer->list) : null;

        /**
         * Lexer's errors.
         *
         * @var array
         */
        $lexerErrors = array();

        /**
         * Parser's errors.
         *
         * @var array
         */
        $parserErrors = array();

        // Both the lexer and the parser construct exception for errors.
        // Usually, exceptions contain a full stack trace and other details that
        // are not required.
        // The code below extracts only the relevant information.

        // Extracting lexer's errors.
        if (!empty($lexer->errors)) {
            foreach ($lexer->errors as $err) {
                $lexerErrors[] = array($err->getMessage(), $err->ch, $err->pos, $err->getCode());
            }
            $lexer->errors = array();
        }

        // Extracting parser's errors.
        if (!empty($parser->errors)) {
            foreach ($parser->errors as $err) {
                $parserErrors[] = array($err->getMessage(), $err->token, $err->getCode());
            }
            $parser->errors = array();
        }

        return array(
            'query' => $query,
            'lexer' => $lexer,
            'parser' => $parser,
            'errors' => array(
                'lexer' => $lexerErrors,
                'parser' => $parserErrors,
            ),
        );
    }

    /**
     * Builds a test.
     *
     * Reads the input file, generates the data and writes it back.
     *
     * @param string $type   the type of this test
     * @param string $input  the input file
     * @param string $output the output file
     * @param string $debug  the debug file
     */
    public static function build($type, $input, $output, $debug = null)
    {
        // Support query types: `lexer` / `parser`.
        if (!in_array($type, array('lexer', 'parser'))) {
            throw new \Exception('Unknown test type (expected `lexer` or `parser`).');
        }

        /**
         * The query that is used to generate the test.
         *
         * @var string
         */
        $query = file_get_contents($input);

        // There is no point in generating a test without a query.
        if (empty($query)) {
            throw new \Exception('No input query specified.');
        }

        $test = static::generate($query, $type);

        // Writing test's data.
        file_put_contents($output, serialize($test));

        // Dumping test's data in human readable format too (if required).
        if (!empty($debug)) {
            file_put_contents($debug, print_r($test, true));
        }
    }

    /**
     * Generates recursively all tests preserving the directory structure.
     *
     * @param string     $input  the input directory
     * @param string     $output the output directory
     * @param null|mixed $debug
     */
    public static function buildAll($input, $output, $debug = null)
    {
        $files = scandir($input);

        foreach ($files as $file) {
            // Skipping current and parent directories.
            if (($file === '.') || ($file === '..')) {
                continue;
            }

            // Appending the filename to directories.
            $inputFile = $input . '/' . $file;
            $outputFile = $output . '/' . $file;
            $debugFile = ($debug !== null) ? $debug . '/' . $file : null;

            if (is_dir($inputFile)) {
                // Creating required directories to maintain the structure.
                // Ignoring errors if the folder structure exists already.
                if (!is_dir($outputFile)) {
                    mkdir($outputFile);
                }
                if (($debug !== null) && (!is_dir($debugFile))) {
                    mkdir($debugFile);
                }

                // Generating tests recursively.
                static::buildAll($inputFile, $outputFile, $debugFile);
            } elseif (substr($inputFile, -3) === '.in') {
                // Generating file names by replacing `.in` with `.out` and
                // `.debug`.
                $outputFile = substr($outputFile, 0, -3) . '.out';
                if ($debug !== null) {
                    $debugFile = substr($debugFile, 0, -3) . '.debug';
                }

                // Building the test.
                if (!file_exists($outputFile)) {
                    sprintf("Building test for %s...\n", $inputFile);
                    static::build(
                        strpos($inputFile, 'lex') !== false ? 'lexer' : 'parser',
                        $inputFile,
                        $outputFile,
                        $debugFile
                    );
                } else {
                    sprintf("Test for %s already built!\n", $inputFile);
                }
            }
        }
    }
}

// Test generator.
//
// Example of usage:
//
//      php TestGenerator.php ../tests/data ../tests/data
//
// Input data must be in the `../tests/data` folder.
// The output will be generated in the same `../tests/data` folder.
//
if (count($argv) >= 3) {
    // Extracting directories' name from command line and trimming unnecessary
    // slashes at the end.
    $input = rtrim($argv[1], '/');
    $output = rtrim($argv[2], '/');
    $debug = empty($argv[3]) ? null : rtrim($argv[3], '/');

    // Checking if all directories are valid.
    if (!is_dir($input)) {
        throw new \Exception('The input directory does not exist.');
    } elseif (!is_dir($output)) {
        throw new \Exception('The output directory does not exist.');
    } elseif (($debug !== null) && (!is_dir($debug))) {
        throw new \Exception('The debug directory does not exist.');
    }

    // Finally, building the tests.
    TestGenerator::buildAll($input, $output, $debug);
}
