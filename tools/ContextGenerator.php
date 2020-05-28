<?php

declare(strict_types=1);

use PhpMyAdmin\SqlParser\Tools\ContextGenerator;

require_once __DIR__ . '/../vendor/autoload.php';

/**
 * Test generator.
 *
 * Example of usage:
 *
 *     php ContextGenerator.php data data
 *
 * Input data must be in the `data` folder.
 * The output will be generated in the same `data` folder.
 */
if (count($argv) < 3) {
    return;
}

// Extracting directories' name from command line and trimming unnecessary
// slashes at the end.
$input = rtrim($argv[1], '/');
$output = rtrim($argv[2], '/');

// Checking if all directories are valid.
if (! is_dir($input)) {
    throw new Exception('The input directory does not exist.');
} elseif (! is_dir($output)) {
    throw new Exception('The output directory does not exist.');
}

// Finally, building the tests.
ContextGenerator::buildAll($input, $output);
