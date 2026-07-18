<!-- Signatory Table -->
<div class="bg-white border border-slate-200 rounded-2xl p-6">

    <div class="flex items-center justify-between mb-4">
        <h3 class="text-2xl font-black text-slate-800">Assign Signatories</h3>

        <div class="flex gap-2">
            <button onclick="openAddSignatoryModal()"
                class="px-4 py-2 bg-slate-600 text-white rounded-lg font-bold hover:bg-slate-700">
                Add Signatory
            </button>

            <button onclick="openAddDocumentModal()"
                class="px-4 py-2 bg-indigo-600 text-white rounded-lg font-bold hover:bg-indigo-700">
                Assign Document
            </button>
        </div>
    </div>

    <div class="overflow-x-auto">
    <table class="w-full text-sm">
        <thead>
            <tr class="text-left border-b">
                <th class="py-3">Document</th>
                <th>Code</th>
                <th>No. of Requests</th>
                <th class="text-center">Action</th>
            </tr>
        </thead>

        <tbody>
            @foreach($documents as $doc)
            <tr class="border-b">
                <td class="py-3">{{ $doc->name }}</td>
                <td>{{ $doc->code }}</td>
                <td>{{ $doc->request_count }}</td>
                <td class="text-center space-x-2">
                    <button class="px-3 py-1 bg-blue-100 text-blue-600 rounded">Assign</button>
                    <button class="px-3 py-1 bg-yellow-100 text-yellow-600 rounded">Edit</button>
                    <button class="px-3 py-1 bg-red-100 text-red-600 rounded">Delete</button>
                </td>
            </tr>
            @endforeach
        </tbody>
    </table>
    </div>

</div>

{{-- Modals --}}
@include('school.settings.partials.school-settings.add-signatory')
@include('school.settings.partials.school-settings.add-document')
