@extends('layouts.app')

@section('page-title', 'Question Bank')
@section('page-subtitle', 'Manage Questions by Learning Competency')

@section('content')
<div class="overflow-x-auto">
<table width="100%">
<thead>
<tr>
    <th>Subject</th>
    <th>Topic</th>
    <th>Learning Competency</th>
    <th>Type</th>
    <th>Status</th>
</tr>
</thead>

<tbody>
@foreach($questions as $q)
<tr>
    <td>{{ $q->subject->name }}</td>
    <td>{{ $q->topic->name }}</td>
    <td>{{ $q->description }}</td>
    <td>{{ ucfirst(str_replace('_',' ', $q->question_type)) }}</td>
    <td>{{ $q->status ?? 'active' }}</td>
</tr>
@endforeach
</tbody>
</table>
</div>

{{ $questions->links() }}
@endsection
