<div class="d-flex justify-content-between mb-2">

    <div>
        @if($createRoute)
            <a href="{{ route($createRoute) }}" class="btn btn-success btn-sm">
                Add New
            </a>
        @endif
    </div>

    <div class="d-flex">
        <input 
            type="text" 
            class="form-control form-control-sm me-2" 
            placeholder="Search..."
            onkeyup="tableSearch(this)"
        >

        <button class="btn btn-secondary btn-sm" onclick="openColumnSettings('{{ $tableKey }}')">
            Columns
        </button>
    </div>

</div>