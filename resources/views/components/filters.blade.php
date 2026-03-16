<form method="GET" action="{{ url()->current() }}" class="filter-form">

    @if(!empty($title))
        <h4 class="filter-title">{{ $title }}</h4>
    @endif

    @foreach ($filters as $name => $label)
        <label for="filter-{{ $name }}">{{ $label }}</label>
        <input
            id="filter-{{ $name }}"
            type="text"
            name="{{ $name }}"
            value="{{ request($name) }}"
            placeholder="{{ $label }}"
        >
    @endforeach

    <div class="filter-actions">
        <button type="submit" class="btn-filter">Apply</button>
        <a href="{{ url()->current() }}" class="btn-reset">Reset</a>
    </div>

</form>
