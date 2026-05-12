@extends('layouts.enrollment')

@section('content')
@include('acad_enrolment.shared._contact_details', [
    'action'      => route('public.apply.step2.store', $term->id),
    'student'     => $student ?? auth()->user()->student ?? null,
    'stepLabel'   => 'STEP 2 OF 9',
    'progressPct' => 22,
    'backUrl'     => route('public.apply.show', $term->id),
])
@endsection
