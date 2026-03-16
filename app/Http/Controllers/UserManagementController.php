<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;

class UserManagementController extends Controller
{
    public function index()
    {
        $users = User::where('school_id', auth()->user()->school_id)->get();

        return view('admin.settings.users.index', compact('users'));
    }

    public function store(Request $request)
{
    // 1. Validate - ensure 'dean' is lowercase here to match the form
    $request->validate([
        'first_name'  => 'required|string|max:255',
        'middle_name' => 'nullable|string|max:255',
        'last_name'   => 'required|string|max:255',
        'email'       => 'required|email|unique:users,email',
        'password'    => 'required|min:6',
        'role'        => 'required|in:admission,academics,teacher,student,program_head,admin,dean',
    ]);

    try {
        // 2. Create manually to bypass any hidden $fillable issues temporarily
        $user = new User();
        $user->first_name = $request->first_name;
        $user->middle_name = $request->middle_name;
        $user->last_name = $request->last_name;
        $user->email = $request->email;
        $user->password = bcrypt($request->password);
        $user->role = $request->role; // This will now be 'dean'
        $user->phone = $request->mobile_number; // Mapping form 'mobile_number' to DB 'phone'
        $user->school_id = auth()->user()->school_id;
        
        $user->save();

        return back()->with('success', 'User created successfully.');
    } catch (\Exception $e) {
        // If 'dean' fails, this will show you the EXACT database error
        dd("Database Error: " . $e->getMessage());
    }
}

    public function show(User $user)
    {
        return view('admin.settings.users.show', compact('user'));
    }

    // Edit user
    public function edit(User $user)
    {
        return view('admin.settings.users.edit', compact('user'));
    }

    // Update user
    public function update(Request $request, User $user)
    {
        $validated = $request->validate([
            'first_name' => 'required|string|max:255',
            'middle_name' => 'nullable|string|max:255',
            'last_name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email,' . $user->id,
            'role' => 'required|string',
            'phone' => 'nullable|string|max:20',
            'birthday' => 'nullable|date',
            'password' => 'nullable|min:6',
        ]);

        // Only update password if provided
        if ($request->filled('password')) {
            $validated['password'] = bcrypt($request->password);
        } else {
            unset($validated['password']);
        }

        $user->update($validated);

        return redirect()
            ->route('settings.users.index')
            ->with('success', 'User updated successfully!');
    }

    // Delete user
    public function delete(User $user)
    {
        $user->delete();
        return redirect()->route('settings.users.index')->with('success', 'User deleted successfully!');
    }

    public function resetPassword(User $user)
    {
        $temporaryPassword = 'Temp1234'; // or generate random

        $user->password = bcrypt($temporaryPassword);
        $user->save();

        return back()->with('success', 'Temporary password: ' . $temporaryPassword);
    }

        

}
