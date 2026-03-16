@extends('layouts.app')

@section('content')
<div class="p-6">
    <h2 class="text-2xl font-bold mb-4">View User</h2>
    <ul class="space-y-2">
        <li><strong>Full Name:</strong> {{ $user->full_name }}</li>
        <li><strong>Email:</strong> {{ $user->email }}</li>
        <li><strong>Role:</strong> {{ ucfirst($user->role) }}</li>
        <li><strong>Mobile Number:</strong> {{ $user->mobile_number }}</li>
        <li><strong>Birthday:</strong> {{ $user->birthday }}</li>
    </ul>
    <a href="{{ route('settings.users.edit', $user->id) }}" class="mt-4 inline-block bg-indigo-500 text-white px-4 py-2 rounded hover:bg-indigo-700">Edit User</a>
</div>
@endsection