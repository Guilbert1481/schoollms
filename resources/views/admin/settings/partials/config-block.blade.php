{{-- resources/views/admin/settings/partials/config-block.blade.php --}}

<div class="config-section mb-4">
    <h3>{{ $title }}</h3>
    
    <div class="row">
        @foreach($items as $item)
            <div class="col-md-3 col-sm-6 mb-2">
                <label class="checkbox-label">
                    <input type="checkbox" 
                           name="enabled_configs[]" 
                           value="{{ $item->id }}"
                           {{ $item->is_active ? 'checked' : '' }}
                           class="config-checkbox">
                    <span class="config-label" data-id="{{ $item->id }}">{{ $item->label }}</span>
                    <button type="button" 
                            class="btn btn-sm btn-link text-danger delete-config" 
                            data-id="{{ $item->id }}"
                            title="Delete">
                        <i class="fas fa-times"></i>
                    </button>
                </label>
            </div>
        @endforeach
    </div>

    {{-- Add New Item --}}
    <div class="add-new-section mt-3">
        <div class="input-group" style="max-width: 400px;">
            <input type="text" 
                   class="form-control new-config-input" 
                   placeholder="Add new {{ strtolower($title) }}"
                   data-category="{{ $category }}">
            <button type="button" 
                    class="btn btn-outline-secondary add-config-btn"
                    data-category="{{ $category }}">
                Add
            </button>
        </div>
    </div>
</div>

<hr class="my-4">