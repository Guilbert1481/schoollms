{{-- resources/views/school/settings/master-data/training/partials/modals/create-certificate.blade.php --}}

<x-modal.form id="certificateCreateModal" title="Create Certificate Template">

<form method="POST" action="{{ route('school.settings.master-data.certificates.store') }}">
    @csrf

    @php
        $config = config('tables.certificate.certificate_templates'); // ✅ fixed path
        $formColumns = $config['form'] ?? [];
        $labels = $config['labels'] ?? [];
        $relations = $config['relations'] ?? [];
    @endphp

    {{-- 🔹 DYNAMIC FIELDS --}}
    <div class="grid grid-cols-2 gap-4 mb-4">

        @foreach($formColumns as $column)
            <div>
                <label class="text-sm font-medium">
                    {{ $labels[$column] ?? ucfirst(str_replace('_',' ', $column)) }}
                </label>

                @if(isset($relations[$column]))

                    @php
                        $relation = $relations[$column];
                        $options = \Illuminate\Support\Facades\DB::table($relation['table'])->get();
                    @endphp

                    <select name="{{ $column }}" class="border p-2 w-full rounded">
                        <option value="">Select...</option>
                        @foreach($options as $option)
                            <option value="{{ $option->id }}">
                                {{ $option->{$relation['label']} }}
                            </option>
                        @endforeach
                    </select>

                @elseif($column === 'certificate_type')

                    <select name="certificate_type" class="border p-2 w-full rounded">
                        <option value="internal">Internal</option>
                        <option value="external">External</option>
                    </select>

                @else

                    <input type="text" name="{{ $column }}" class="border p-2 w-full rounded">

                @endif
            </div>
        @endforeach

    </div>

    {{-- 🔥 CERTIFICATE BUILDER --}}
    <div class="border rounded p-4 bg-gray-50">

        <h3 class="font-bold mb-3">Certificate Builder</h3>

        <div class="grid grid-cols-2 gap-4">

            {{-- LEFT: EDITOR --}}
            <div>
                <label class="text-xs text-gray-500 mb-1 block">HTML Layout</label>

                <textarea name="layout_html"
                          id="builder"
                          class="w-full h-64 border p-2 font-mono text-sm rounded"
                          placeholder="<h1>Certificate of Completion</h1>"></textarea>

                <p class="text-xs text-gray-500 mt-2">
                    Placeholders:
                    <strong>{{ '{{name}}' }}</strong>,
                    <strong>{{ '{{course}}' }}</strong>,
                    <strong>{{ '{{date}}' }}</strong>
                </p>
            </div>

            {{-- RIGHT: LIVE PREVIEW --}}
            <div>
                <label class="text-xs text-gray-500 mb-1 block">Live Preview</label>

                <iframe id="preview"
                        class="w-full h-64 border rounded bg-white"></iframe>
            </div>

        </div>

    </div>

</form>

{{-- 🔥 LIVE PREVIEW SCRIPT --}}
<script>
document.addEventListener('DOMContentLoaded', function () {
    const builder = document.getElementById('builder');
    const preview = document.getElementById('preview');

    if (builder && preview) {

        const renderPreview = () => {
            let html = builder.value;

            // Replace placeholders for preview
            html = html
                .replace(/{{name}}/g, 'Juan Dela Cruz')
                .replace(/{{course}}/g, 'Sample Course')
                .replace(/{{date}}/g, new Date().toLocaleDateString());

            preview.srcdoc = html;
        };

        builder.addEventListener('input', renderPreview);

        // initial render
        renderPreview();
    }
});
</script>

</x-modal.form>