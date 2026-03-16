<h2>Teachers</h2>

<a href="/admin/teachers/create">+ Add Teacher</a>

<ul>
@foreach($teachers as $teacher)
    <li>{{ $teacher->name }} (ID: {{ $teacher->id }})</li>
@endforeach
</ul>
