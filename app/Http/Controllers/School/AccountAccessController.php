<?php

namespace App\Http\Controllers\School;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Models\User;

class AccountAccessController extends Controller
{
    public function index()
{
    $schoolId = auth()->user()->school_id;

    // Accounts
    $accounts = DB::table('users as u')

        // ACTIVE ACCESS (current holder)
        ->leftJoin('account_access as active', function ($join) {
            $join->on('u.id', '=', 'active.user_id')
                ->where('active.is_active', 1);
        })

        // LATEST ACCESS (role + office even if inactive)
        ->leftJoin('account_access as latest', function ($join) {
            $join->on('u.id', '=', 'latest.user_id')
                ->whereRaw('latest.id = (
                    SELECT MAX(id)
                    FROM account_access
                    WHERE user_id = u.id
                )');
        })

        ->leftJoin('profiles as p', 'p.id', '=', 'active.person_id')
        ->leftJoin('roles as r', 'r.id', '=', 'latest.role_id')
        ->leftJoin('offices as o', 'o.id', '=', 'latest.office_id')

        ->where('u.school_id', auth()->user()->school_id)
        ->whereNotIn('u.role', ['student'])

        ->select(
            'u.id',
            'u.email',
            'r.name as role',
            'o.name as office',
            'p.first_name',
            'p.middle_name',
            'p.last_name',
            'active.id as access_id',
            'active.is_active'
        )

        ->orderBy('u.email')
        ->get();

    // Profiles
    $profiles = DB::table('profiles')
        ->join('users', 'users.id', '=', 'profiles.user_id')
        ->where('profiles.school_id', $schoolId)
        ->whereNotIn('users.role', ['student', 'trainee'])
        ->select(
            'profiles.id',
            'profiles.first_name',
            'profiles.middle_name',
            'profiles.last_name'
        )
        ->orderBy('profiles.last_name')
        ->get();

    // CURRENT access holders
    $access = DB::table('account_access as a')
        ->join('profiles as p', 'p.id', '=', 'a.person_id')
        ->leftJoin('roles as r', 'r.id', '=', 'a.role_id')
        ->where('p.school_id', $schoolId)
        ->select(
            'a.user_id',
            'p.first_name',
            'p.middle_name',
            'p.last_name',
            'r.name as role',
            'a.created_at'
        )
        ->where('a.is_active', 1)
        ->orderBy('a.created_at', 'desc')
        ->get();

    // HISTORICAL TRANSFERS
    $historical = DB::table('account_access as new')
        ->join('account_access as old', function ($join) {
            $join->on('old.user_id', '=', 'new.user_id')
                ->whereRaw('old.id = (
                    SELECT MAX(id)
                    FROM account_access
                    WHERE user_id = new.user_id
                    AND id < new.id
                )');
        })
        ->leftJoin('users as u', 'u.id', '=', 'new.user_id')
        ->leftJoin('profiles as old_holder', 'old_holder.id', '=', 'old.person_id')
        ->leftJoin('profiles as new_holder', 'new_holder.id', '=', 'new.person_id')
        ->leftJoin('profiles as assigner', 'assigner.user_id', '=', 'new.assigned_by')
        ->leftJoin('offices as o', 'o.id', '=', 'old.office_id')
        ->where('old_holder.school_id', $schoolId)
        ->select(
            'u.email',

            DB::raw("CONCAT(old_holder.first_name,' ',IFNULL(old_holder.middle_name,''),' ',old_holder.last_name) as last_holder"),

            'o.name as office',
            'old.role_snapshot as role',
            'old.start_date',
            'old.end_date',

            DB::raw("CONCAT(new_holder.first_name,' ',IFNULL(new_holder.middle_name,''),' ',new_holder.last_name) as assigned_to"),

            DB::raw("CONCAT(assigner.first_name,' ',IFNULL(assigner.middle_name,''),' ',assigner.last_name) as assigned_by"),

            'new.remarks',
            'new.created_at'
        )
        ->orderByDesc('new.created_at')
        ->get();

    return view('school.account_access', compact(
        'accounts',
        'profiles',
        'access',
        'historical'
    ));
}

    public function transfer(Request $request)
{
    DB::beginTransaction();

    try {

        $accountId = $request->user_id;
        $personId  = $request->person_id;

        // Get current active access
        $currentAccess = DB::table('account_access')
            ->where('user_id', $accountId)
            ->where('is_active', 1)
            ->first();

        // If no active holder, cannot transfer
        if (!$currentAccess) {
            DB::rollBack();
            return back()->with('error', 'This account has no active holder. Use Assign instead.');
        }

        // Close current access
        DB::table('account_access')
            ->where('id', $currentAccess->id)
            ->update([
                'is_active' => 0,
                'end_date' => now(),
                'updated_at' => now()
            ]);

        // Insert new access
        DB::table('account_access')->insert([
            'user_id'       => $accountId,
            'role_id'       => $currentAccess->role_id,
            'office_id'     => $currentAccess->office_id,
            'person_id'     => $personId,
            'role_snapshot' => $currentAccess->role_snapshot,
            'start_date'    => now(),
            'assigned_by'   => auth()->id(),
            'remarks'       => $request->remarks,
            'is_active'     => 1,
            'created_at'    => now(),
            'updated_at'    => now()
        ]);

        DB::commit();

        return back()->with('success', 'Access transferred successfully.');

    } catch (\Exception $e) {
        DB::rollBack();
        return back()->with('error', $e->getMessage());
    }
}

    public function deactivate(Request $request)
{
    DB::beginTransaction();

    try {

        $access = DB::table('account_access')
            ->where('id', $request->access_id)
            ->where('is_active', 1)
            ->first();

        if (!$access) {
            return back()->with('error', 'No active access found.');
        }

        DB::table('account_access')
            ->where('id', $request->access_id)
            ->update([
                'is_active' => 0,
                'end_date' => now(),
                'updated_at' => now()
            ]);

        DB::commit();

        return back()->with('success', 'Role deactivated. Account is now vacant.');

    } catch (\Exception $e) {
        DB::rollBack();
        return back()->with('error', $e->getMessage());
    }
}

    public function assign(Request $request)
{   
    DB::beginTransaction();

    try {

        $userId   = $request->user_id;
        $personId = $request->person_id;
        $schoolId = auth()->user()->school_id;

        // Get account
        $user = DB::table('users')->where('id', $userId)->first();

        if (!$user) {
            DB::rollBack();
            return back()->with('error', 'Account not found.');
        }

        // Get role from roles table using users.role
        $role = DB::table('roles')
            ->where('school_id', $schoolId)
            ->where('name', $user->role)
            ->first();

        if (!$role) {
            DB::rollBack();
            return back()->with('error', 'Role not found.');
        }

        // Deactivate any active access first
        DB::table('account_access')
            ->where('user_id', $userId)
            ->where('is_active', 1)
            ->update([
                'is_active' => 0,
                'end_date' => now(),
                'updated_at' => now()
            ]);

        // Insert new assignment
        DB::table('account_access')->insert([
            'user_id'       => $userId,
            'role_id'       => $role->id,
            'person_id'     => $personId,
            'role_snapshot' => $role->name,
            'start_date'    => now(),
            'assigned_by'   => auth()->id(),
            'remarks'       => 'Assigned new holder',
            'is_active'     => 1,
            'created_at'    => now(),
            'updated_at'    => now()
        ]);

        DB::commit();

        return back()->with('success', 'Assigned successfully.');

    } catch (\Exception $e) {
        DB::rollBack();
        return back()->with('error', $e->getMessage());
    }
}
}