<div id="enrollModal" class="modal-overlay hidden">
    <div class="modal-box w-full max-w-lg">

        <div class="modal-header">
            <span>Training Enrollment</span>
            <span class="modal-close" onclick="closeModal('enrollModal')">✕</span>
        </div>

        <form method="POST" action="{{ route('training.enroll') }}">
            @csrf

            <div class="modal-body space-y-4">

                {{-- Hidden --}}
                <input type="hidden" name="training_id" id="training_id">

                {{-- Prefilled Fields --}}
                <div>
                    <label class="text-sm">First Name</label>
                    <input type="text" name="first_name" id="first_name"
                        class="w-full border rounded px-3 py-2 bg-gray-100" readonly>
                </div>

                <div>
                    <label class="text-sm">Last Name</label>
                    <input type="text" name="last_name" id="last_name"
                        class="w-full border rounded px-3 py-2 bg-gray-100" readonly>
                </div>

                {{-- NEW ACCOUNT --}}
                <div>
                    <label class="text-sm">Training Email</label>
                    <input type="email" name="email" required
                        class="w-full border rounded px-3 py-2">
                </div>

                <div>
                    <label class="text-sm">Password</label>
                    <input type="password" name="password" required
                        class="w-full border rounded px-3 py-2">
                </div>

                <div>
                    <label class="text-sm">Confirm Password</label>
                    <input type="password" name="password_confirmation" required
                        class="w-full border rounded px-3 py-2">
                </div>

            </div>

            <div class="modal-footer">
                <button type="button" onclick="closeModal('enrollModal')" class="px-4 py-2 border rounded">
                    Cancel
                </button>

                <button type="submit" class="px-4 py-2 bg-indigo-600 text-white rounded">
                    Enroll
                </button>
            </div>

        </form>
    </div>
</div>


<script>
function openEnrollModal(trainingId, user){
    document.getElementById('training_id').value = trainingId;

    // autofill from profile
    document.getElementById('first_name').value = user.first_name ?? '';
    document.getElementById('last_name').value = user.last_name ?? '';

    openModal('enrollModal');
}

</script>