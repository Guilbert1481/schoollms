<div class="modal fade" id="addSubjectModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">

            <div class="modal-header">
                <h5>Add Subject</h5>
                <button type="button" class="close" data-bs-dismiss="modal">×</button>
            </div>

            <form method="POST" action="{{ route('teacher.subjects.store') }}">
                @csrf

                <div class="modal-body">
                    <input
                        type="text"
                        name="name"
                        class="form-control"
                        placeholder="Subject name"
                        required
                    >
                </div>

                <div class="modal-footer">
                    <button type="submit" class="btn btn-primary">Save</button>
                </div>

            </form>

        </div>
    </div>
</div>
