{{-- resources/views/staff/program_head/subjects/index.blade.php --}}
@extends('layouts.app')

<!DOCTYPE html>
<html>
<head>
    <title>Subjects</title>
</head>
<body>

@if (session('success'))
    <div style="background:#d1fae5;color:#065f46;padding:10px;margin-bottom:10px;">
        {{ session('success') }}
    </div>
@endif

<h2>Subjects</h2>

<a href="{{ route('subjects.create') }}">Add Subject</a>

<ul>
    @foreach ($subjects as $subject)
        <li>{{ $subject->name }}</li>
    @endforeach
</ul>

</body>
</html>
