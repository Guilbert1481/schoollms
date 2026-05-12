<!-- Add Document Modal -->
<div id="addDocumentModal" class="fixed inset-0 bg-black/40 hidden z-50">

    <div id="addDocumentDraggableModal"
         class="bg-white rounded-2xl shadow-xl w-full max-w-xl p-6 absolute"
         style="top:150px; left:400px;">

        <!-- Draggable Header -->
        <div id="addDocumentModalHeader" class="flex justify-between mb-4 cursor-move">
            <h2 class="text-lg font-extrabold">Assign Signatories</h2>
            <button type="button" onclick="closeAddDocumentModal()">✕</button>
        </div>

        <form method="POST" action="{{ route('school.settings.storeDocument') }}">
            @csrf

            <div class="space-y-4">

                <div>
                    <label class="text-sm font-bold">Document Type</label>
                    <select name="document_type" class="w-full border rounded-lg px-3 py-2">
                    @foreach($documents as $doc)
                        <option value="{{ $doc->id }}">{{ $doc->name }}</option>
                    @endforeach
                    </select>
                </div>

                <div>
                    <label class="text-sm font-bold">Number of Signatories</label>
                    <select name="number_of_signatories" class="w-full border rounded-lg px-3 py-2">
                        <option>1</option>
                        <option>2</option>
                        <option>3</option>
                        <option>4</option>
                    </select>
                </div>

                <div>
                    <label class="text-sm font-bold">Signatories</label>
                    <select name="signatories[]" multiple class="w-full border rounded-lg px-3 py-2">
                    @foreach($signatories as $sign)
                        <option value="{{ $sign->id }}">{{ $sign->name }}</option>
                    @endforeach
                    </select>
                </div>

                <div>
                    <label class="text-sm font-bold">Signing Order</label>
                    <input type="text" name="signing_order"
                        class="w-full border rounded-lg px-3 py-2"
                        placeholder="Example: Teacher=1, Dean=2, Registrar=3">
                </div>

            </div>

            <div class="flex justify-end mt-6">
                <button type="submit"
                    class="px-4 py-2 bg-indigo-600 text-white rounded-lg font-bold">
                    Save
                </button>
            </div>

        </form>

    </div>

</div>
<script src="{{ asset('js/modules/draggable.js') }}"></script>
<script>
function openAddDocumentModal() {
    document.getElementById('addDocumentModal').classList.remove('hidden');
}

function closeAddDocumentModal() {
    document.getElementById('addDocumentModal').classList.add('hidden');
}

document.addEventListener("DOMContentLoaded", function () {
    if (typeof makeDraggable === "function") {
        makeDraggable("addDocumentDraggableModal", "addDocumentModalHeader");
    }
});
</script>