<?php

namespace App\Http\Controllers\System;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;


class DynamicCrudController extends Controller
{
    public function index($table)
    {
        $schoolId = auth()->user()->school_id;

        $query = DB::table($table)
            ->where($table . '.school_id', $schoolId)
            ->select($table . '.*');

        $query = buildForeignJoins($table, $query);

        $data = $query->get();

        $columns = generateColumns($table);
        $formColumns = Schema::getColumnListing($table);

        return view('system.dynamic.index', [
            'table' => $table,
            'columns' => $columns,
            'data' => $data,
            'formColumns' => $formColumns
        ]);
    }

    public function store(Request $request, $table)
    {
        $data = $request->except(['_token','table']);
        $data['school_id'] = auth()->user()->school_id;

        DB::table($table)->insert($data);

        return redirect()->back()->with('success', 'Record created successfully.');
    }

    public function update(Request $request, $table, $id)
    {
        $data = $request->except(['_token','_method','table']);

        DB::table($table)
            ->where('id', $id)
            ->where('school_id', auth()->user()->school_id)
            ->update($data);

        return redirect()->back()->with('success', 'Record updated successfully.');
    }

    public function destroy($table, $id)
    {
        DB::table($table)
            ->where('id', $id)
            ->where('school_id', auth()->user()->school_id)
            ->delete();

        return redirect()->back()->with('success', 'Record deleted successfully.');
    }
}