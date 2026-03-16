/* =========================================================
   QUESTION CONFIGURATION JS
   Handles: Add, Delete, Edit configurations via AJAX
   ========================================================= */

document.addEventListener('DOMContentLoaded', function() {
    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content || window.csrfToken;

    // Add new configuration
    document.querySelectorAll('.add-config-btn').forEach(button => {
        button.addEventListener('click', function() {
            const category = this.dataset.category;
            const input = this.closest('.add-new-section').querySelector('.new-config-input');
            const label = input.value.trim();

            if (!label) {
                alert('Please enter a value');
                return;
            }

            // Send AJAX request
            fetch('/admin/configurations', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': csrfToken,
                    'Accept': 'application/json'
                },
                body: JSON.stringify({
                    category: category,
                    label: label
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success || data.message) {
                    location.reload();
                } else {
                    alert(data.error || 'An error occurred');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Failed to add configuration');
            });
        });
    });

    // Delete configuration
    document.querySelectorAll('.delete-config').forEach(button => {
        button.addEventListener('click', function(e) {
            e.preventDefault();
            
            if (!confirm('Are you sure you want to delete this configuration?')) {
                return;
            }

            const configId = this.dataset.id;

            fetch(`/admin/configurations/${configId}`, {
                method: 'DELETE',
                headers: {
                    'X-CSRF-TOKEN': csrfToken,
                    'Accept': 'application/json'
                }
            })
            .then(response => response.json())
            .then(data => {
                if (data.success || data.message) {
                    location.reload();
                } else {
                    alert(data.error || 'Failed to delete');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Failed to delete configuration');
            });
        });
    });

    // Inline edit label (double-click to edit)
    document.querySelectorAll('.config-label').forEach(label => {
        label.addEventListener('dblclick', function() {
            const configId = this.dataset.id;
            const currentLabel = this.textContent;
            const newLabel = prompt('Edit label:', currentLabel);

            if (newLabel && newLabel !== currentLabel) {
                fetch(`/admin/configurations/${configId}/label`, {
                    method: 'PATCH',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': csrfToken,
                        'Accept': 'application/json'
                    },
                    body: JSON.stringify({ label: newLabel })
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        this.textContent = newLabel;
                    } else {
                        alert(data.error || 'Failed to update label');
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('Failed to update label');
                });
            }
        });
    });
});