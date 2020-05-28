<?php

declare(strict_types=1);

use PhpMyAdmin\SqlParser\Tools\TestGenerator;

require_once '../vendor/autoload.php';

/**
 * Test generator.
 *
 * Example of usage:
 *
 *     php TestGenerator.php ../tests/data ../tests/data
 *
 * Input data must be in the `../tests/data` folder.
 * The output will be generated in the same `../tests/data` folder.
 */
if (count($argv) < 3) {
    return;
}

// Extracting directories' name from command line and trimming unnecessary
// slashes at the end.
$input = rtrim($argv[1], '/');
$output = rtrim($argv[2], '/');
$debug = empty($argv[3]) ? null : rtrim($argv[3], '/');

// Checking if all directories are valid.
if (! is_dir($input)) {
    throw new Exception('The input directory does not exist.');
} elseif (! is_dir($output)) {
    throw new Exception('The output directory does not exist.');
} elseif (($debug !== null) && (! is_dir($debug))) {
    throw new Exception('The debug directory does not exist.');
}

// Finally, building the tests.
TestGenerator::buildAll($input, $output, $debug);
