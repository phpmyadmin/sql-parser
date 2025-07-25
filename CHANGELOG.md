# Change Log

## [6.0.x] - YYYY-MM-DD

- Drop support for PHP 7.2, 7.3, 7.4, 8.0 and 8.1
- Move `Misc::getAliases()` into `SelectStatement::getAliases()` (#454)
- Drop `USE_UTF_STRINGS` constant (#471)

## [5.11.1] - 2025-07-20

### Added

- Add context files for MySQL 9.2, MySQL 9.3 and MariaDB 12.1 (#628)
- Add context files for MariaDB 11.8 and MariaDB 12.0 (#620)

### Fixed

-  Fix Window function handling that failed because of "OVER" keyword case-sensitive comparison (#623)

## [5.11.0] - 2025-02-22

### Added

- Add Translator::setLocale() method (#599)

### Fixed

- Fix FORCE INDEX not been parsed correctly (#614)
- Fix parsing of ADD UNIQUE and ADD UNIQUE KEY for ALTER TABLE statements (#611)

## [5.10.3] - 2025-01-18

### Fixed

- Add "RECURSIVE" on build() for "WITH RECURSIVE" on the WithStatement class (#605)
- Fix for quadratic complexity in certain queries, which could have caused long execution times. Thanks to Maximilian Krög (GitHub user MoonE) for this fix to help improve security.

## [5.10.2] - 2024-12-05

### Added

- Add MariaDb 11.6 and 11.7 contexts (#601)
- Add context files for MySQL 9.1 (#603)

## [5.10.1] - 2024-11-10

### Fixed

- Fix parsing of ALTER TABLE … RENAME KEY (#580)
- Fix parsing table names that start with "e1" (#578)
- Improve handling of negative and overflowed offsets on TokensList (#582)
- Fix parsing of queries with 'AND' (#590)
- Fix C style comments with two asterisks (#597)
- Fix parsing of SRID in column definition (#595)

## [5.10.0] - 2024-08-29

- Fix parsing of UPDATE ... SET (#577)
- Fix parsing of WITH PARSER (#563)
- Fix context files for MySQL and MariaDB (#572) (#576)
- Allow using `::class` keyword to load a context (#571)
- Fix query flags for lower-case functions (#564)
- Improve context files by using constants (#570)
- Fix case when a condition is not parsed correctly (#560)
- Support parsing KILL statements (#556)
- Fix replace clause of select statement with FOR UPDATE (#555)
- Add support for ALTER FUNCTION and ALTER PROCEDURE statements (#553)

## [5.9.1] - 2024-08-13

- Allow parsing ALTER TABLE statement with column check constraint (#554)
- Add support for PHPUnit 10 (#573)

## [5.9.0] - 2024-01-20

- Fix keywords not being recognized as table alias (#496)
- Add `bin/sql-parser` executable file (#517)
- Fix bind parameter in LIMIT OFFSET (#498)
- Fix using ? as a parameter (#515)

## [5.8.2] - 2023-09-19

- Fix a regression with the ALTER operation (#511)

## [5.8.1] - 2023-09-15

- Fix `:=` was not recognized as an operator just like `=` (#306)
- Fix `ALTER TABLE … MODIFY … ENUM('<reserved_keyword>')` is being wrongly parsed (#234)
- Fix `ALTER TABLE … MODIFY … ENUM('<reserved_keyword>')` is being wrongly parsed (#478)
- Fix MariaDB window function with alias gives bad linting errors (#283)
- Fix unrecognized keyword `COLLATE` in `WHERE` clauses (#491)
- Fix invalid hexadecimal prefix 0X (#508)

## [5.8.0] - 2023-06-05

- Fix `ALTER EVENT RENAME TO` to use expression instead of var (#419)
- Fix incorrect order of operations to parse table/db called `` (#422)
- Fix ALTER EVENT statement with DEFINER=user modifier fails to be parsed (#418)
- Fix GROUP BY modifier WITH ROLLUP is treated as a syntax error and prevents export of SQL query results
- Fix `TokensList::getPrevious` was not able to reach very first token (#428)
- Fix `TransactionStatement::build()` "Call to a member function build() on null" when the transaction has no end
- Fix MySQL-specific commands parsing (#226)
- Fix `ALTER TABLE … RENAME COLUMN … TO …` is not understood by the parser/linter (#430)
- Fix `PARTITION` syntax errors (#377)
- Fix `ALTER USER` when used with `IDENTIFIED WITH/VIA/BY` option (#431)
- Fix `COALESCE PARTITION` in `ALTER TABLE`, rather than `COALESCE` (#323)
- Support `ALGORITHM` and `LOCK` options in `ALTER TABLE` statements (#319)
- Fix way end of functions, procedures and triggers' bodies is identified (#438)
- Fix `enclosed by` is not recognized by the parser when `fields` is in lower case (#236)
- Support `KEY` on `CreateDefinition` (#330)
- Fix `CALL` statements parsing (#372)
- Implement support for `LEFT JOIN`, `JOIN`, `INNER JOIN` on `UpdateStatement` (#260)
- Implement support for `TABLE` and `REPLACE` statements on `DESCRIBE` statements
- Fix `DESCRIBE` to allow a schema.table syntax (#445)
- Fix parsing insert queries with functions trims commas (#450)

## [5.7.0] - 2023-01-25

* Performance improvement to use less the `nextToken()` function (#397)
* Lexer - Solving ambiguity on function keywords (#385)
* Implement `ALTER EVENT` (#404)
* Add `ALTER EVENT` keywords (#404)
* Drop PHP 7.1 support
* Fix the alter operation table options `RENAME INDEX x TO y` (#405)
* Fix `CreateStatement` function's options (#406)
* Fix a PHP notice on Linter using `ANALYZE` (#413)

## [5.6.0] - 2023-01-02

* Add missing return types annotations
* Improve the WITH statements parser (#363)
* Add support for passing `Context::SQL_MODE*` constants to `Context::setMode` method
* Fix additional body tokens issue with `CREATE VIEW` statements (#371)
* Exclude from composer vendor bundle /tests and /phpunit.xml.dist
* Support table structure with `COMPRESSED` columns (#351)
* Add `#[\AllowDynamicProperties]` on `Statement` and `Expression` classes for PHP 8.2 support
* Support `ALTER` queries of `PARTITIONS` (#329)
* Change `Context::load()` error handling to returning a boolean value instead of throwing a `LoaderException` (#384)
* Fixed differentiating between `ANALYZE` and `EXPLAIN` statements (#386)
* Added "NOT" to the select options (#374)
* Implement the `EXPLAIN` Parser (#389)
* Context: Updated contexts to contain `multipoint` and `multipolygon` data types (#393)
* Support more keywords on `Expression` component (#399)
* Fix PHP 8.3 failing tests (#400)

## [5.5.0] - 2021-12-08

* Add WITH support (#165, #331)
* Fixed BufferedQuery when it has an odd number of backslashes in the end (#340)
* Fixed the issue that ignored the body tokens when creating views with union (#343)
* Fixed parser errors on "ALTER TABLE" statements to add columns with SET type (#168)
* Fixed PHP 8.1 fatal errors on classes that "implements ArrayAccess"
* Add new contexts for MariaDB 10.4, 10.5, 10.6 (#328)
* Fixed parser errors for "ALTER USER" with options (#342)
* Fixed handling of the procedures and functions's options where part of the body (#339)
* Fix PHP notice "Undefined index: name in src/Components/Key.php#206" for table keys using expressions (#347)
* Added support for MySQL 8.0 table structure KEY expressions (#347)
* Added support for KEY order (ASC/DESC) (#296)
* Added missing KEY options for MySQL and MariaDB (#348)
* Added support for ENFORCED and NOT ENFORCED on table create queries (#341)
* Performance improvement to use less the "ord()" function (#352)
* Added support for OVER() with an alias (AS) (#197)

## [5.4.2] - 2021-02-05

* Added check for quoted symbol to avoid parser error in case of keyword (#317)
* Allow PHP 8

## [5.4.1] - 2020-10-15

* Fix array_key_exists warning when parsing a "DEFAULT FALSE" token (#299)

## [5.4.0] - 2020-10-08

* EXISTS is also a function. (#297)
* Fix lexer to not allow numbers with letters (#300)
* Add support for INVISIBLE keyword (#292)
* Fix the "$" might be a character used in a name (#301)
* Fix use stream_select instead of non-blocking STDIN (#309)
* Add select validation to a create view statement (#310)

## [5.3.1] - 2020-03-20

* Revert some changes with the understanding of ANSI_QUOTES mode and identifiers
* Suggest motranslator 5.0 in README

## [5.3.0] - 2020-03-20

* Stop instanciating an object to check its class name. (#290)
* Replace sscanf by equivalent native PHP functions because sscanf can be disabled for security reasons. (#270)
* Allow phpunit 9
* Allow phpmyadmin/motranslator 5.0
* Fix for php error when "INSERT INTO x SET a = 1" is "INSERT INTO x SET = 1" (#295)
* Fixed lexer fails to detect "*" as a wildcard (#288)
* Fixed ANSI_QUOTES support (#284)
* Fixed parser mistakes with comments (#156)

## [5.2.0] - 2020-01-07

* Fix ALTER TABLE ... PRIMARY/UNIQUE KEY results in error (#267)
* Prevent overwrite of offset in Limit clause by parenthesis (#275)
* Allow SCHEMA to be used in CREATE Database statement (#231)
* Add missing options in SET statement (#255)
* Add support for DROP USER statement (#259)
* Fix php error "undefined index" when replacing a non existing clause (#249)

## [5.1.0] - 2019-11-12

* Fix for PHP deprecations messages about implode for php 7.4+ (#258)
* Parse CHECK keyword on table definition (#264)
* Parse truncate statement (#221)
* Fix wrong parsing of partitions (#265)

## [5.0.0] - 2019-05-09

* Drop support for PHP 5.3, PHP 5.4, PHP 5.5, PHP 5.6, PHP 7.0 and HHVM
* Enable strict mode on PHP files
* Fix redundant whitespaces in build() outputs (#228)
* Fix incorrect error on DEFAULT keyword in ALTER operation (#229)
* Fix incorrect outputs from Query::getClause (#233)
* Add support for reading an SQL file from stdin
* Fix for missing tokenize-query in Composer's vendor/bin/ directory
* Fix for PHP warnings with an incomplete CASE expression (#241)
* Fix for error message with multiple CALL statements (#223)
* Recognize the question mark character as a parameter (#242)

## [4.7.4] - YYYY-MM-DD

## [4.7.3] - 2021-12-08

- Fixed BufferedQuery when it has an odd number of backslashes in the end (#340)
- Fixed the issue that ignored the body tokens when creating views with union (#343)
- Fixed parser errors on "ALTER TABLE" statements to add columns with SET type (#168)
- Fixed parser errors for "ALTER USER" with options (#342)
- Fixed handling of the procedures and functions's options where part of the body (#339)
- Fix PHP notice "Undefined index: name in src/Components/Key.php#206" for table keys using functions (#347)
- Fix MySQL 8.0 table structure KEY expression not recognized (#347)
- Fix KEY order (ASC/DESC) not part of the KEY definition (#296)
- Fix missing KEY options for MySQL and MariaDB (#348)
- Fix validation error when using ENFORCED option (#341)

## [4.7.2] - 2021-02-05

- Added check for quoted symbol to avoid parser error in case of keyword (#317)
- Adjust PHP version constraint to not support PHP 8.0 on the 4.7 series (5.x series supports it)

## [4.7.1] - 2020-10-15

* Fix array_key_exists warning when parsing a "DEFAULT FALSE" token (#299)

## [4.7.0] - 2020-10-08

* EXISTS is also a function. (#297)
* Fix lexer to not allow numbers with letters (#300)
* Add support for INVISIBLE keyword (#292)
* Fix the "$" might be a character used in a name (#301)
* Fix use stream_select instead of non-blocking STDIN (#309)
* Add select validation to a create view statement (#310)

## [4.6.1] - 2020-03-20

* Revert some changes with the understanding of ANSI_QUOTES mode and identifiers
* Suggest motranslator 4.0 in README

## [4.6.0] - 2020-03-20

* Stop instanciating an object to check its class name. (#290)
* Replace sscanf by equivalent native PHP functions because sscanf can be disabled for security reasons. (#270)
* Allow phpunit 7
* Fix for php error when "INSERT INTO x SET a = 1" is "INSERT INTO x SET = 1" (#295)
* Fixed lexer fails to detect "*" as a wildcard (#288)
* Fixed ANSI_QUOTES support (#284)
* Fixed parser mistakes with comments (#156)

## [4.5.0] - 2020-01-07

* Fix ALTER TABLE ... PRIMARY/UNIQUE KEY results in error (#267)
* Prevent overwrite of offset in Limit clause by parenthesis (#275)
* Allow SCHEMA to be used in CREATE Database statement (#231)
* Add missing options in SET statement (#255)
* Add support for DROP USER statement (#259)
* Fix php error "undefined index" when replacing a non existing clause (#249)

## [4.4.0] - 2019-11-12

* Fix for PHP deprecations messages about implode for php 7.4+ (#258)
* Parse CHECK keyword on table definition (#264)
* Parse truncate statement (#221)
* Fix wrong parsing of partitions (#265)

## [4.3.2] - 2019-06-03

Backport fixes from 5.0.0 to QA branch:

* Fix redundant whitespaces in build() outputs (#228)
* Fix incorrect error on DEFAULT keyword in ALTER operation (#229)
* Fix incorrect outputs from Query::getClause (#233)
* Add support for reading an SQL file from stdin
* Fix for missing tokenize-query in Composer's vendor/bin/ directory
* Fix for PHP warnings with an incomplete CASE expression (#241)
* Fix for error message with multiple CALL statements (#223)
* Recognize the question mark character as a parameter (#242)

## [4.3.1] - 2019-01-05

* Fixed incorrect error thrown on DEFAULT keyword in ALTER statement (#218)

## [4.3.0] - 2018-12-25

* Add support for aliases on CASE expressions (#162 and #192)
* Add support for INDEX hints in SELECT statement (#199)
* Add support for LOCK and UNLOCK TABLES statement (#180)
* Add detection of extraneous comma in UPDATE statement (#160)
* Add detection of a missing comma between two ALTER operations (#189)
* Add missing support for STRAIGHT_JOIN (#196)
* Add support for end options in SET statement (#190)
* Fix building of RENAME statements (#201)
* Add support for PURGE statements (#207)
* Add support for COLLATE keyword (#190)

## [4.2.5] - 2018-10-31

* Fix issue with CREATE OR REPLACE VIEW.

## [4.2.4] - 2017-12-06

* Fix parsing of CREATE TABLE with per field COLLATE.
* Improved Context::loadClosest to better deal with corner cases.
* Localization updates.

## [4.2.3] - 2017-10-10

* Make mbstring extension optional (though Symfony polyfill).
* Fixed build CREATE TABLE query with PARTITIONS having ENGINE but not VALUES.

## [4.2.2] - 2017-09-28

* Added support for binding parameters.

## [4.2.1] - 2017-09-08

* Fixed minor bug in Query::getFlags.
* Localization updates.

## [4.2.0] - 2017-08-30

* Initial support for MariaDB SQL contexts.
* Add support for MariaDB 10.3 INTERSECT and EXCEPT.

## [4.1.10] - 2017-08-21

* Use custom LoaderException for context loading errors.

## [4.1.9] - 2017-07-12

* Various code cleanups.
* Improved error handling of several invalid statements.

## [4.1.8] - 2017-07-09

* Fixed parsing SQL comment at the end of query.
* Improved handing of non utf-8 strings.
* Added query flag for SET queries.

## [4.1.7] - 2017-06-06

* Fixed setting combination SQL Modes.

## [4.1.6] - 2017-06-01

* Fixed building query with GROUP BY clause.

## [4.1.5] - 2017-05-15

* Fixed invalid lexing of queries with : in strings.
* Properly handle maximal length of delimiter.

## [4.1.4] - 2017-05-05

* Fixed wrong extract of string tokens with escaped characters.
* Properly handle lowercase begin statement.

## [4.1.3] - 2017-04-06

* Added support for DELETE ... JOIN clauses.
* Changed BufferedQuery to include comments in output.
* Fixed parsing of inline comments.

## [4.1.2] - 2017-02-20

* Coding style improvements.
* Chinese localization.
* Improved order validatin for JOIN clauses.
* Improved pretty printing of JOIN clauses.
* Added support for LOAD DATA statements.

## [4.1.1] - 2017-02-07

* Localization using phpmyadmin/motranslator is now optional.
* Improved testsuite.
* Better handling of non upper cased not reserved keywords.
* Minor performance and coding style improvements.

## [4.1.0] - 2017-01-23

* Use phpmyadmin/motranslator to localize messages.

## [4.0.1] - 2017-01-23

* Fixed CLI wrappers for new API.
* Fixed README for new API.

## [4.0.0] - 2017-01-23

* Added PhpMyAdmin namespace prefix to follow PSR-4.

## [3.4.17] - 2017-01-20

* Coding style fixes.
* Fixed indentation in HTML formatting.
* Fixed parsing of unterminated variables.
* Improved comments lexing.

## [3.4.16] - 2017-01-06

* Coding style fixes.
* Properly handle operators AND, NOT, OR, XOR, DIV, MOD

## [3.4.15] - 2017-01-02

* Fix return value of Formatter.toString() when type is text
* Fix parsing of FIELDS and LINES options in SELECT..INTO
* PHP 7.2 compatibility.
* Better parameter passing to query formatter.

## [3.4.14] - 2016-11-30

* Improved parsing of UNION queries.
* Recognize BINARY function.

## [3.4.13] - 2016-11-15

* Fix broken incorrect clause order detection for Joins.
* Add parsing of end options in Select statements.

## [3.4.12] - 2016-11-09

* Added verification order of SELECT statement clauses.

## [3.4.11] - 2016-10-25

* Fixed parsing of ON UPDATE option in field definition of TIMESTAMP type with precision
* Fixed parsing of NATURAL JOIN, CROSS JOIN and related joins.
* Fixed parsing of BEGIN/END labels.

## [3.4.10] - 2016-10-03

* Fixed API regression on DELETE statement

## [3.4.9] - 2016-10-03

* Added support for CASE expressions
* Support for parsing and building DELETE statement
* Support for parsing subqueries in FROM clause

## [3.4.8] - 2016-09-22

* No change release to sync GitHub releases with Packagist

## [3.4.7] - 2016-09-20

* Fix parsing of DEFINER without backquotes
* Fixed escaping HTML entities in HTML formatter
* Fixed escaping of control chars in CLI formatter

## [3.4.6] - 2016-09-13

* Fix parsing of REPLACE INTO ...
* Fix parsing of INSERT ... ON DUPLICATE KEY UPDATE ...
* Extended testsuite
* Re-enabled PHP 5.3 support

## [3.4.5] - 2016-09-13

* Fix parsing of INSERT...SELECT and INSERT...SET syntax
* Fix parsing of CREATE TABLE ... PARTITION
* Fix parsing of SET CHARACTER SET, CHARSET, NAMES
* Add Support for 'CREATE TABLE `table_copy` LIKE `table`

## [3.4.4] - 2016-04-26

* Add support for FULL OUTER JOIN

## [3.4.3] - 2016-04-19

* Fix parsing of query with \

## [3.4.2] - 2016-04-07

* Recognize UNION DISTINCT
* Recognize REGEXP and RLIKE operators

## [3.4.1] - 2016-04-06

* Add FULLTEXT and SPATIAL keywords
* Properly parse CREATE TABLE [AS] SELECT
* Fix parsing of table with DEFAULT and COMMENT

## [3.4.0] - 2016-02-23

* Fix parsing DEFAULT value on CREATE
* Fix parsing of ALTER VIEW

## [3.3.1] - 2016-02-12

* Condition: Allow keyword `INTERVAL`.

## [3.3.0] - 2016-02-12

* Expression: Refactored parsing options.

## [3.2.0] - 2016-02-11

* Context: Added custom mode that avoids escaping when possible.

## [3.1.0] - 2016-02-10

* ArrayObj: Handle more complex expressions in arrays.
* BufferedQuery: Backslashes in comments escaped characters in comments.
* Condition: Allow `IF` in conditions.
* Context: Add `;` as operator.
* Context: Updated contexts to contain `BIT` data type.
* CreateStatement: The `DEFAULT` option may be an expression.
* DescribeStatement: Added `DESC` as alias for `DESCRIBE`.
* Expression: Rewrote expression parsing.
* Misc: Added PHPUnit's Code Coverage 3.0 as a dependency.
* Misc: Added support for PHP 5.4 back.
* Misc: Removed dependency to Ctype.
* Misc: Repository transferred from @udan11 to @phpMyAdmin.
* Misc: Updated `.gitignore` to ignore `composer.lock`.
* Misc: Updated Composer and Travis configuration for PHP 7 and PHPUnit 5.
* Tools: Documented tags in `ContextGenerator`.

## [3.0.8] - 2015-12-18

* Allow `NULL` in expressions.
* Downgraded PHPUnit to 4.8. Removed old PHP versions.
* Updated PHPUnit to 5.1 and fixed some of the tests.
* Added `UNION ALL` as a type of `UNION`.
* Expressions are permitted in `SET` operations.
* Added `STRAIGHT_JOIN` as a known type of join.
* Added missing definitions for `MATCH` and `AGAINST`.
* Added missing statement (`FLUSH` and `DEALLOCATE`).

## [3.0.7] - 2015-11-12

* Expressions may begin with a function that is also a reserved keyword (e.g. `IF`).

## [3.0.6] - 2015-11-12

* Fixed a bug where formatter split the function name and the parameters list.

## [3.0.5] - 2015-11-08

* Add GRANT as known statement.
* Use JOIN expressions for flag detection.
* Fix the order of clauses in SELECT statements involving UNIONs.
* Added dummy parsers for CREATE USER and SET PASSWORD statements.
* Accept NOT operator in conditions.
* Fixed DELIMITER statements in BufferedQuery.
* Added INSERT statement builder.

## [3.0.4] - 2015-10-21

* Fix error message in `SqlParser\Components\OptionsArray`.

## [3.0.3] - 2015-10-10

* Avoid building a field multiple times if clause has synonyms.

## [3.0.2] - 2015-10-10

* Add EXISTS as an acceptable keyword in conditions.

## [3.0.1] - 2015-10-06

* Handle backslashes separately for `SqlParser\Utils\BufferedQuery`. Fixes a bug where backslashes in combination with strings weren't handled properly.

## [3.0.0] - 2015-10-02

__Breaking changes:__

* `SqlParser\Components\Reference::$table` is now an instance of `SqlParser\Components\Expression` to support references from other tables.

## [2.1.3] - 2015-10-02

* Add definitions for all JOIN clauses.

## [2.1.2] - 2015-10-02

* Properly parse options when the value of the option is '='.

## [2.1.1] - 2015-09-30

* Only RANGE and LIST type partitions support VALUES.

## [2.1.0] - 2015-09-30

* Added utilities for handling tokens and tokens list.

## [2.0.3] - 2015-09-30

* Added missing NOT IN operator. This caused troubles when parsing conditions that contained the `NOT IN` operator.

## [2.0.2] - 2015-09-30

* Added support for `OUTER` as an optional keyword in joins.

## [2.0.1] - 2015-09-30

* Fixed a bug related to (sub)partitions options not being included in the built component. Also, the option `ENGINE` was unrecognized.

## [2.0.0] - 2015-09-25

* Better parsing for CREATE TABLE statements (related to breaking change 1).
* Added support for JSON data type.
* Refactoring and minor documentation improvements.

__Breaking changes:__
* `SqlParser\Components\Key::$columns` is now an array of arrays. Each array must contain a `name` key which represents the name of the column and an optional `length` key which represents the length of the column.

## [1.0.0] - 2015-08-20

* First release of this library.

[5.11.1]: https://github.com/phpmyadmin/sql-parser/compare/5.11.0...5.11.1
[5.11.0]: https://github.com/phpmyadmin/sql-parser/compare/5.10.3...5.11.0
[5.10.3]: https://github.com/phpmyadmin/sql-parser/compare/5.10.2...5.10.3
[5.10.2]: https://github.com/phpmyadmin/sql-parser/compare/5.10.1...5.10.2
[5.10.1]: https://github.com/phpmyadmin/sql-parser/compare/5.10.0...5.10.1
[5.10.0]: https://github.com/phpmyadmin/sql-parser/compare/5.9.1...5.10.0
[5.9.1]: https://github.com/phpmyadmin/sql-parser/compare/5.9.0...5.9.1
[5.9.0]: https://github.com/phpmyadmin/sql-parser/compare/5.8.2...5.9.0
