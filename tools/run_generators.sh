#!/bin/bash

export LC_ALL=C

#
# Runs the test and context generators.
#

PROJECT_ROOT=$(dirname $(dirname $(readlink -m $0)))

echo "Using base dir: ${PROJECT_ROOT}"
cd ${PROJECT_ROOT}

php tools/ContextGenerator.php tools/contexts/ src/Contexts
php tools/TestGenerator.php tests/data tests/data

echo "Done."
