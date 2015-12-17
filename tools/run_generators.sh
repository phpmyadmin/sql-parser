#!/bin/bash

#
# Runs the test and context generators.
#

php ContextGenerator.php contexts/ ../src/Contexts
php TestGenerator.php ../tests/data ../tests/data
