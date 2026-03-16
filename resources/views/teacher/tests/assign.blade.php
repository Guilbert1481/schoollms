@extends('layouts.app')

@section('page-title', 'Assign Test')
@section('page-subtitle', $test->title)

@section('content')
<form method="POST" action="{{ route('teacher.tests.assign.store', $test->id) }}">
@csrf

<h4>Assignment Mode</h4>
<label><input type="radio" name="mode" value="test" required> Test</label>
<label><input type="radio" name="mode" value="mastery"> Mastery</label>

<hr>

<h4>Assign To</h4>
<label><input type="radio" name="assign_to" value="class" required> Class</label>
<label><input type="radio" name="assign_to" value="student"> Student</label>

<hr>

<h4>Select Targets</h4>

<div>
@foreach($classes as $class)
    <label>
        <input type="checkbox" name="targets[]" value="{{ $class->id }}">
        {{ $class->name }}
    </label><br>
@endforeach
</div>

<hr>

<button class="btn btn-primary">Assign Test</button>
<a href="{{ route('teacher.tests.index') }}" class="btn btn-secondary">Cancel</a>

</form>
@endsection
