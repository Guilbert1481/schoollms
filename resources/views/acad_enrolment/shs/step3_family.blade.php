@extends('layouts.enrollment')

@section('content')
@include('acad_enrolment.shared._family', [
    'action'      => route('public.apply.shs.step3.store', $term->id),
    'parents'     => $parents,
    'emergency'   => $emergencyContact,
    'stepLabel'   => 'STEP 3 OF 9',
    'progressPct' => 33,
    'backUrl'     => route('public.apply.track', $term->id),
])
@endsection
