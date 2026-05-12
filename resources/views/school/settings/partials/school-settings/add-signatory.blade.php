<!-- Add Signatory Modal -->
<div id="addSignatoryModal" class="fixed inset-0 bg-black/40 hidden z-50">

    <div id="addSignatoryDraggableModal"
         class="bg-white rounded-2xl shadow-xl w-full max-w-md p-6 absolute"
         style="top:150px; left:400px;">

        <!-- Draggable Header -->
        <div id="addSignatoryModalHeader" class="flex justify-between mb-4 cursor-move">
            <h2 class="text-lg font-extrabold">Add Signatory</h2>
            <button type="button" onclick="closeAddSignatoryModal()">✕</button>
        </div>

        <form method="POST" action="{{ route('school.settings.storeSignatory') }}">
            @csrf

            <div class="space-y-4">

                <div>
                    <label class="text-sm font-bold">Signatory Name</label>
                    <input type="text" name="name"
                        class="w-full border rounded-lg px-3 py-2"
                        placeholder="Registrar">
                </div>

                <div>
                    <label class="text-sm font-bold">Position</label>
                    <input type="text" name="position"
                        class="w-full border rounded-lg px-3 py-2"
                        placeholder="University Registrar">
                </div>

                <div>
                    <label class="text-sm font-bold">Link to Role</label>
                    <select name="role_id" class="w-full border rounded-lg px-3 py-2">
                        @foreach($roles as $role)
                            <option value="{{ $role->id }}">{{ $role->name }}</option>
                        @endforeach
                    </select>
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
function openAddSignatoryModal() {
    document.getElementById('addSignatoryModal').classList.remove('hidden');
}

function closeAddSignatoryModal() {
    document.getElementById('addSignatoryModal').classList.add('hidden');
}

document.addEventListener("DOMContentLoaded", function () {
    if (typeof makeDraggable === "function") {
        makeDraggable("addSignatoryDraggableModal", "addSignatoryModalHeader");
    }
});
</script>