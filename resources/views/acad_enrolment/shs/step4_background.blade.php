@extends('layouts.enrollment')

@section('content')
@include('acad_enrolment.shared._academic_background', [
    'action'       => route('public.apply.shs.step4.store', $term->id),
    'backgrounds'  => $backgrounds,
    'levelOptions' => ['kinder','elementary','junior_high','senior_high'],
    'stepLabel'    => 'STEP 5 OF 9',
    'progressPct'  => 55,
    'backUrl'      => route('public.apply.track', $term->id),
])
@endsection
