<?php

namespace App\Http\Controllers\Base;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class BaseCrudController extends Controller
{
    protected function loadTableData($table)
{
    $schoolId = auth()->user()->school_id;

    $config = config("tables.tables.$table");

    $query = DB::table($table)->where("$table.school_id", $schoolId);

    // Handle relations (joins)
    if (!empty($config['relations'])) {
        foreach ($config['relations'] as $column => $relation) {

            if (isset($relation['join_profiles']) && $relation['join_profiles']) {
                $query->leftJoin('users', 'users.id', '=', "$table.$column")
                      ->leftJoin('profiles', 'profiles.user_id', '=', 'users.id')
                      ->addSelect(DB::raw("CONCAT(profiles.first_name, ' ', profiles.last_name) as {$column}_label"));
            }
        }
    }

    $query->addSelect("$table.*");

    $data = $query->get();

    // Convert columns format
    $columns = [];
    foreach ($config['columns'] ?? [] as $col) {
        if (is_array($col)) {
            $columns[] = $col;
        } else {
            $columns[] = [
                'key' => $col,
                'label' => ucwords(str_replace('_', ' ', $col))
            ];
        }
    }

    return [
        'data' => $data,
        'columns' => $columns,
        'table' => $table,
        'formColumns' => $config['form'] ?? [],
        'labels' => $config['labels'] ?? [],
        'relations' => $config['relations'] ?? [],
    ];
}

    protected function storeRecord($table, $request)
{
    $config = config("tables.$table") ?? [];

    $data = $request->except(['_token']);

    // Convert *_id fields
    foreach ($data as $key => $value) {
        if (str_ends_with($key, '_id')) {
            $data[$key] = ($value === '' || $value == 0) ? null : (int) $value;
        }
    }

    // Auto fields
    if (isset($config['auto'])) {
        foreach ($config['auto'] as $field) {
            if ($field == 'school_id') {
                $data['school_id'] = auth()->user()->school_id;
            }

            if ($field == 'user_id') {
                $data['user_id'] = auth()->id();
            }
        }
    }

    // timestamps
    $data['created_at'] = now();
    $data['updated_at'] = now();

    DB::table($table)->insert($data);

    return redirect()->back()->with('success', 'Record created successfully.');
}

    public function updateRecord(Request $request)
    {
        $table = $request->table;
        $id = $request->id;

        $data = $request->except(['_token', '_method', 'table', 'id']);
        $data['updated_at'] = now();

        DB::table($table)->where('id', $id)->update($data);

        return back()->with('success', 'Updated successfully');
    }

    protected function deleteRecord($table, $id)
    {
        DB::table($table)
            ->where('id', $id)
            ->delete();

        return redirect()->back()->with('success', 'Record deleted successfully.');
    }

    public function getRecord($table, $id)
    {
        $record = DB::table($table)->where('id', $id)->first();
        return response()->json($record);
    }
}