<?php

declare(strict_types=1);

namespace PhpMyAdmin\SqlParser\Utils;

use PhpMyAdmin\SqlParser\Statements\CreateStatement;

use function is_array;
use function str_replace;

/**
 * Table utilities.
 */
class Table
{
    /**
     * Gets the foreign keys of the table.
     *
     * @return list<(string|string[]|null)[]>
     * @psalm-return list<array{
     *  constraint: string|null,
     *  index_list: (int|string)[],
     *  ref_db_name?: string|null,
     *  ref_table_name?: string|null,
     *  ref_index_list?: string[],
     *  on_update?: string|string[],
     *  on_delete?: string|string[],
     * }>
     */
    public static function getForeignKeys(CreateStatement $statement): array
    {
        if (empty($statement->fields) || (! is_array($statement->fields)) || (! $statement->options->has('TABLE'))) {
            return [];
        }

        $ret = [];

        foreach ($statement->fields as $field) {
            if (empty($field->key) || ($field->key->type !== 'FOREIGN KEY')) {
                continue;
            }

            $columns = [];
            foreach ($field->key->columns as $column) {
                if (! isset($column['name'])) {
                    continue;
                }

                $columns[] = $column['name'];
            }

            $tmp = [
                'constraint' => $field->name,
                'index_list' => $columns,
            ];

            if (! empty($field->references)) {
                $tmp['ref_db_name'] = $field->references->table->database;
                $tmp['ref_table_name'] = $field->references->table->table;
                $tmp['ref_index_list'] = $field->references->columns;

                $opt = $field->references->options->has('ON UPDATE');

                if ($opt) {
                    $tmp['on_update'] = str_replace(' ', '_', $opt);
                }

                $opt = $field->references->options->has('ON DELETE');

                if ($opt) {
                    $tmp['on_delete'] = str_replace(' ', '_', $opt);
                }
            }

            $ret[] = $tmp;
        }

        return $ret;
    }

    /**
     * Gets fields of the table.
     *
     * @param CreateStatement $statement the statement to be processed
     *
     * @return array<int|string, array<string, bool|string|mixed>>
     * @psalm-return array<string, array{
     *  type: string,
     *  timestamp_not_null: bool,
     *  default_value?: mixed,
     *  default_current_timestamp?: true,
     *  on_update_current_timestamp?: true,
     *  expr?: mixed
     * }>
     */
    public static function getFields(CreateStatement $statement): array
    {
        if (empty($statement->fields) || (! is_array($statement->fields)) || (! $statement->options->has('TABLE'))) {
            return [];
        }

        $ret = [];

        foreach ($statement->fields as $field) {
            // Skipping keys.
            if (empty($field->type)) {
                continue;
            }

            $ret[$field->name] = [
                'type' => $field->type->name,
                'timestamp_not_null' => false,
            ];

            if (! $field->options) {
                continue;
            }

            if ($field->type->name === 'TIMESTAMP') {
                if ($field->options->has('NOT NULL')) {
                    $ret[$field->name]['timestamp_not_null'] = true;
                }
            }

            $option = $field->options->has('DEFAULT');

            if ($option) {
                $ret[$field->name]['default_value'] = $option;
                if ($option === 'CURRENT_TIMESTAMP') {
                    $ret[$field->name]['default_current_timestamp'] = true;
                }
            }

            $option = $field->options->has('ON UPDATE');

            if ($option === 'CURRENT_TIMESTAMP') {
                $ret[$field->name]['on_update_current_timestamp'] = true;
            }

            $option = $field->options->has('AS');

            if (! $option) {
                continue;
            }

            $ret[$field->name]['expr'] = $option;
        }

        return $ret;
    }
}
