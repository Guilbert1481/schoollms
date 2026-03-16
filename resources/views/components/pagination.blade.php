@props(['paginator'])

@if ($paginator instanceof \Illuminate\Pagination\LengthAwarePaginator && $paginator->hasPages())

<nav class="pagination-container" aria-label="Pagination">

    {{-- PREVIOUS --}}
    <button
        class="pagination-btn nav-btn {{ $paginator->onFirstPage() ? 'disabled' : '' }}"
        @disabled($paginator->onFirstPage())
        onclick="window.location='{{ $paginator->previousPageUrl() }}'">
        PREVIOUS
    </button>

    {{-- PAGE NUMBERS --}}
    @for ($page = 1; $page <= $paginator->lastPage(); $page++)
        <button
            class="pagination-btn {{ $page === $paginator->currentPage() ? 'active' : '' }}"
            onclick="window.location='{{ $paginator->url($page) }}'">
            {{ $page }}
        </button>
    @endfor

    {{-- NEXT --}}
    <button
        class="pagination-btn nav-btn {{ !$paginator->hasMorePages() ? 'disabled' : '' }}"
        @disabled(! $paginator->hasMorePages())
        onclick="window.location='{{ $paginator->nextPageUrl() }}'">
        NEXT
    </button>

    

</nav>



@endif
