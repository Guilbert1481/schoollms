<?php

namespace App\Http\Controllers\Base;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Generic CRUD for the dynamic master-data tables.
 *
 * Hardened so a logged-in user can only ever reach what they are allowed to —
 * nothing more, nothing less:
 *
 *   1. Table allowlist — only tables registered in config/tables/tables.php may
 *      be touched. Anything else (users, roles, account_access, payments, …)
 *      is rejected with 404, even for staff.
 *   2. Tenant scoping — every read/update/delete is constrained to the
 *      authenticated user's school_id, so one school can never reach another's
 *      rows by guessing an id.
 *   3. Column allowlist — only real, non-protected columns are written, so a
 *      request cannot smuggle id / school_id / user_id / timestamps or any
 *      made-up field into the database.
 */
class BaseCrudController extends Controller
{
    /** Columns that are server-controlled and must never come from request input. */
    protected array $protectedColumns = ['id', 'school_id', 'user_id', 'created_at', 'updated_at'];

    /** Registry keys that are not actually manageable tables. */
    protected array $nonTableKeys = ['table_actions'];

    /**
     * Resolve a table's config from the central allowlist registry, or abort.
     * This is the single gate that decides which tables the generic endpoints
     * may operate on.
     */
    protected function tableConfig(string $table): array
    {
        $registry = config('tables.tables', []);

        abort_unless(
            ! in_array($table, $this->nonTableKeys, true)
                && array_key_exists($table, $registry)
                && is_array($registry[$table]),
            404,
            'This table cannot be managed here.'
        );

        return $registry[$table];
    }

    /** Authenticated user's school id (null for a global superadmin). */
    protected function currentSchoolId(): ?int
    {
        return auth()->user()?->school_id;
    }

    /**
     * Keep only request fields that are real, writable columns of the table
     * (excludes id / school_id / user_id / timestamps and any non-column).
     */
    protected function filterWritable(string $table, array $input): array
    {
        $allowed = array_diff(Schema::getColumnListing($table), $this->protectedColumns);
        $data = array_intersect_key($input, array_flip($allowed));

        // Normalise foreign-key fields: '' / 0 -> null.
        foreach ($data as $key => $value) {
            if (str_ends_with($key, '_id')) {
                $data[$key] = ($value === '' || $value === null || $value == 0) ? null : (int) $value;
            }
        }

        return $data;
    }

    /** Apply the current school constraint to a query when the table supports it. */
    protected function scopeSchool($query, string $table)
    {
        $schoolId = $this->currentSchoolId();
        if ($schoolId !== null && Schema::hasColumn($table, 'school_id')) {
            $query->where("$table.school_id", $schoolId);
        }

        return $query;
    }

    protected function loadTableData($table)
    {
        $config = $this->tableConfig($table);

        $query = DB::table($table);
        $this->scopeSchool($query, $table);

        // Handle relations (joins)
        if (! empty($config['relations'])) {
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
                    'label' => ucwords(str_replace('_', ' ', $col)),
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
        $config = $this->tableConfig($table);

        $data = $this->filterWritable($table, $request->except(['_token']));

        // Server-controlled fields (never taken from input).
        if (Schema::hasColumn($table, 'school_id')) {
            $data['school_id'] = $this->currentSchoolId();
        }
        if (in_array('user_id', $config['auto'] ?? [], true) && Schema::hasColumn($table, 'user_id')) {
            $data['user_id'] = auth()->id();
        }
        if (Schema::hasColumn($table, 'created_at')) {
            $data['created_at'] = now();
        }
        if (Schema::hasColumn($table, 'updated_at')) {
            $data['updated_at'] = now();
        }

        DB::table($table)->insert($data);

        return redirect()->back()->with('success', 'Record created successfully.');
    }

    public function updateRecord(Request $request)
    {
        $table = (string) $request->input('table');
        $id    = (int) $request->input('id');

        $this->tableConfig($table);

        $data = $this->filterWritable($table, $request->except(['_token', '_method', 'table', 'id']));

        if (Schema::hasColumn($table, 'updated_at')) {
            $data['updated_at'] = now();
        }

        $query = DB::table($table)->where('id', $id);
        $this->scopeSchool($query, $table);
        $query->update($data);

        return back()->with('success', 'Updated successfully');
    }

    protected function deleteRecord($table, $id)
    {
        $this->tableConfig($table);

        $query = DB::table($table)->where('id', (int) $id);
        $this->scopeSchool($query, $table);
        $query->delete();

        return redirect()->back()->with('success', 'Record deleted successfully.');
    }

    public function getRecord($table, $id)
    {
        $this->tableConfig($table);

        $query = DB::table($table)->where('id', (int) $id);
        $this->scopeSchool($query, $table);

        return response()->json($query->first());
    }
}
