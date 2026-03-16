document.addEventListener('testSaved', () => {

    const saveBtn = document.getElementById('saveTestBtn');
    const testId = document.getElementById('testId')?.value;

    if (!saveBtn || !testId) return;

    let editBtn = document.getElementById('editTestBtn');

    if (!editBtn) {
        editBtn = document.createElement('button');
        editBtn.id = 'editTestBtn';
        editBtn.className = 'btn btn-edit';
        editBtn.textContent = 'Edit Test';

        editBtn.addEventListener('click', () => {
            window.location.href = `/teacher/tests/${testId}/edit`;
        });

        saveBtn.parentNode.appendChild(editBtn);
    }

});