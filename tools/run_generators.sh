#!/bin/bash

export LC_ALL=C

#
# Runs the test and context generators.
#

BASE="$(dirname $0)"

echo "Using base dir: $BASE"
cd $BASE

php ContextGenerator.php contexts/ ../src/Contexts
php TestGenerator.php ../tests/data ../tests/data

echo "Done."
