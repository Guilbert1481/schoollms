<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/*
|--------------------------------------------------------------------------
| Generate Table Columns
|--------------------------------------------------------------------------
*/
if (!function_exists('generateColumns')) {
    function generateColumns($table)
    {
        $dbColumns = Schema::getColumnListing($table);
        $columns = [];

        // Custom labels per table
        $customLabels = [
            'departments' => [
                'head_user_id' => 'Department Head',
            ],
            'offices' => [
                'head_user_id' => 'Office Head',
            ],
            'positions' => [
                'head_user_id' => 'Position Head',
            ],
        ];

        foreach ($dbColumns as $col) {

            // Skip system columns
            if (in_array($col, ['id','school_id','created_at','updated_at','user_id'])) {
                continue;
            }

            // If foreign key, show name instead of id
            if (Str::endsWith($col, '_id')) {

                $label = $customLabels[$table][$col]
                    ?? Str::title(str_replace('_id', '', str_replace('_', ' ', $col)));

                $columns[] = [
                    'key' => $col . '_name',
                    'label' => $label
                ];

            } else {
                $columns[] = [
                    'key' => $col,
                    'label' => Str::title(str_replace('_', ' ', $col))
                ];
            }
        }

        return $columns;
    }
}


/*
|--------------------------------------------------------------------------
| Automatically Join Foreign Tables
|--------------------------------------------------------------------------
*/
if (!function_exists('buildForeignJoins')) {
    function buildForeignJoins($table, $query)
    {
        $columns = Schema::getColumnListing($table);

        foreach ($columns as $col) {

            if (Str::endsWith($col, '_id') && !in_array($col, ['school_id'])) {

                // Determine related table
                $relatedTable = Str::plural(str_replace('_id', '', $col));

                // Special cases
                if ($col == 'user_id' || $col == 'head_user_id') {
                    $relatedTable = 'users';
                }

                // Only join if table exists
                if (Schema::hasTable($relatedTable)) {

                    $alias = $col . '_ref';

                    $query->leftJoin(
                        $relatedTable . ' as ' . $alias,
                        $table . '.' . $col,
                        '=',
                        $alias . '.id'
                    );

                    // Select display column
                    if (Schema::hasColumn($relatedTable, 'name')) {
                        $query->addSelect($alias . '.name as ' . $col . '_name');
                    } elseif (Schema::hasColumn($relatedTable, 'title')) {
                        $query->addSelect($alias . '.title as ' . $col . '_name');
                    } else {
                        $query->addSelect($alias . '.id as ' . $col . '_name');
                    }
                }
            }
        }

        return $query;
    }
}