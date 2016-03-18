# SQL Parser

A validating SQL lexer and parser with a focus on MySQL dialect.

## Code status

[![Build Status](https://travis-ci.org/phpmyadmin/sql-parser.svg?branch=master)](https://travis-ci.org/phpmyadmin/sql-parser)
[![Code Coverage](https://scrutinizer-ci.com/g/phpmyadmin/sql-parser/badges/coverage.png?b=master)](https://scrutinizer-ci.com/g/phpmyadmin/sql-parser/?branch=master)
[![codecov.io](https://codecov.io/github/phpmyadmin/sql-parser/coverage.svg?branch=master)](https://codecov.io/github/phpmyadmin/sql-parser?branch=master)
[![Scrutinizer Code Quality](https://scrutinizer-ci.com/g/phpmyadmin/sql-parser/badges/quality-score.png?b=master)](https://scrutinizer-ci.com/g/phpmyadmin/sql-parser/?branch=master)

## Installation

Please use [Composer][1] to install:

```
composer require phpmyadmin/sql-parser
```

## Usage

### Command line utility

Command line utility to syntax highlight SQL query:

```sh
./vendor/bin/highlight-query --query "SELECT 1"
```

Command line utility to lint SQL query:

```sh
./vendor/bin/lint-query --query "SELECT 1"
```

### Formatting SQL query

```php
echo SqlParser\Utils\Formatter::format($query, array('type' => 'html'));
```

### Parsing and building SQL query

```php
$parsed = new SqlParser\Parser($query);

// you can now inspect or change query
var_dump($parser->statements[0]);

// and build it again
$statement = $parser->statements[0];
$statement->build()
```

## More information

This library was originally during the Google Summer of Code 2015 and has been used by phpMyAdmin since version 4.5.

[1]:https://getcomposer.org/
