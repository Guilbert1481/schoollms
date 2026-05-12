@extends('layouts.enrollment')

@section('content')
@include('acad_enrolment.shared._family', [
    'action'      => route('public.apply.family.store', $term->id),
    'parents'     => $parents,
    'emergency'   => $emergencyContact,
    'stepLabel'   => 'STEP 3 OF 7',
    'progressPct' => 43,
    'backUrl'     => route('public.apply.step2', $term->id),
])
@endsection
