@extends('layouts.app')

@section('content')
<div class="p-6">
    <h2 class="text-2xl font-bold mb-4">Edit User</h2>
    <form method="POST" action="{{ route('settings.users.update', $user->id) }}">
        @csrf
        @method('PUT')
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
            <div>
                <label>First Name</label>
                <input class="w-full border rounded p-2" type="text" name="first_name" value="{{ old('first_name', $user->first_name) }}" required>
            </div>
            <div>
                <label>Middle Name</label>
                <input class="w-full border rounded p-2" type="text" name="middle_name" value="{{ old('middle_name', $user->middle_name) }}">
            </div>
            <div>
                <label>Last Name</label>
                <input class="w-full border rounded p-2" type="text" name="last_name" value="{{ old('last_name', $user->last_name) }}" required>
            </div>
            <div>
                <label>Email</label>
                <input class="w-full border rounded p-2" type="email" name="email" value="{{ old('email', $user->email) }}" required>
            </div>
            <div>
                <label>Role</label>
                <select name="roles[]" multiple class="w-full border rounded p-2">
                    @foreach($roles as $role)
                        <option value="{{ $role->id }}"
                            @if(isset($user) && $user->roles->contains($role->id)) selected @endif>
                            {{ ucfirst(str_replace('_',' ', $role->name)) }}
                        </option>
                    @endforeach
                </select>
            </div>
            <div>
                <label>Mobile Number</label>
                <input class="w-full border rounded p-2" type="text" name="mobile_number" value="{{ old('mobile_number', $user->mobile_number) }}">
            </div>
            <div>
                <label>Birthday</label>
                <input class="w-full border rounded p-2" type="date" name="birthday" value="{{ old('birthday', $user->birthday) }}">
            </div>
        </div>
        <div class="mb-6">
            <label>Address</label>
            <input class="w-full border rounded p-2" type="text" name="address" value="{{ old('address', $user->address) }}">
        </div>
        <button class="bg-indigo-600 text-white px-4 py-2 rounded hover:bg-indigo-700" type="submit">Save Changes</button>
    
</div>
@endsection