# Context files

This files are used to generate `src/Contexts/*.php` files.
You need to run `./tools/run_generators.sh` to generate/update them.
The file `src/Tools/ContextGenerator.php` is responsible for building them.
And the generated files are loaded and used by `src/Context.php`.

## Default contexts

- For MySQL: MySql50700
- For MariaDB: MariaDb100300

### In tests

If you name your data files with `_mariadb_` it will automatically load the context for you.
For example: `tests/data/parser/parseSelectOverAlias_mariadb_100600.in`.

## Files structure

The file `tools/contexts/_common.txt` contains all the data
that exists in all of the MariaDB and MySQL versions currently supported.

Each `tools/contexts/_functions[MariaDb|MySql]<versionint>.txt` contains the functions supported in this version.

Each `tools/contexts/[MariaDb|MySql]<versionint>.txt` contains the keywords supported in this version.

## Data scheme

- `(R)` -> reserved, can be found on common and version files
- `(D)` -> data type, can be found on `_common.txt`
- `(K)` -> keyword, can be found on common and version files
- `(F)` -> function name, can be found on `_functions[MariaDb|MySql]<versionint>.txt` files
