@extends('layouts.app')

@section('content')
<link rel="stylesheet" href="{{ asset('css/modules/filters.css') }}">
<link rel="stylesheet" href="{{ asset('css/modules/pagination.css') }}">
<link rel="stylesheet" href="{{ asset('css/testManagement.css') }}">




<div class="dashboard-content-rail">
    <div class="main-content-container">
        <div class="management-layout"> Put actions button here</div>
        <div class="management-layout">
            {{-- LEFT: FILTER PANEL --}}
            <aside class="management-filters">
                <form method="GET" action="{{ url()->current() }}" class="filter-form">

                    {{-- FILTER TABLE --}}

                    @include('components.filters', [
                        'title' => 'Filters',
                        'filters' => [
                            'subject' => 'Subject',
                            'section' => 'Section',
                            'program' => 'Program',
                            'term'    => 'Term',
                            'mode'    => 'Mode',
                        ]
                    ])

                    

                </form>
            </aside>

            {{-- RIGHT: TABLE --}}
            <section class="management-table">

                <table class="test-table">
                    <thead>
                        <tr>
                            <th>Assessment Type</th>
                            <th>Program</th>
                            <th>Subject</th>
                            <th>Section</th>
                            <th>Term</th>
                            <th>Mode</th>
                            <th>Total Items</th>
                            <th>Duration</th>
                            <th>Schedule</th>
                            <th class="col-status">Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>

                    <tbody>
                    @forelse($testManagement as $item)
                        <tr>
                            <td>{{ $item->type }}</td>

                            <td>{{ is_object($item->program) ? $item->program->name : '-' }}</td>
                            <td>{{ is_object($item->subject) ? $item->subject->name : '-' }}</td>
                            <td>{{ is_object($item->section) ? $item->section->name : '-' }}</td>

                            <td>{{ $item->term }}</td>
                            <td>{{ $item->mode }}</td>
                            <td>{{ $item->total_items }}</td>
                            <td>{{ $item->duration }} mins</td>
                            <td>{{ $item->schedule }}</td>
                            <td class="col-status">
                                <span class="status-pill draft">Draft</span>
                            </td>

                            <td class="actions-cell">
                                <button class="action-btn edit-btn">
                                    <img src="{{ asset('icons/edit.svg') }}" alt="">
                                </button>

                                <button class="action-btn publish-btn">
                                    <img src="{{ asset('icons/publish.svg') }}" alt="">
                                </button>
                            </td>





                        </tr>
                    @empty
                        <tr>
                            <td colspan="11" class="empty">No records found.</td>
                        </tr>
                    @endforelse
                    </tbody>
                </table>

                <div class="pagination-wrapper pagination-right">
                    <x-pagination :paginator="$testManagement" />
                </div>


            </section>

        </div>
</div>
</div>



<script src="{{ asset('js/tests/testManagement.js') }}"></script>
<script src="{{ asset('js/modules/pagination.js') }}" defer></script>

@endsection
