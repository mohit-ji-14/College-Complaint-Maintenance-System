// assets/js/main.js - Front-end Interactions & Utilities

document.addEventListener('DOMContentLoaded', function() {
    
    // Image Upload Live Preview
    const imageInputs = document.querySelectorAll('.image-file-input');
    imageInputs.forEach(input => {
        input.addEventListener('change', function(e) {
            const previewTargetId = this.getAttribute('data-preview-target');
            const previewImage = document.getElementById(previewTargetId);
            const previewContainer = document.getElementById(previewTargetId + '_container');
            
            if (this.files && this.files[0]) {
                const reader = new FileReader();
                reader.onload = function(evt) {
                    if (previewImage) previewImage.src = evt.target.result;
                    if (previewContainer) previewContainer.classList.remove('d-none');
                }
                reader.readAsDataURL(this.files[0]);
            }
        });
    });

    // Auto-dismiss alerts after 6 seconds
    const alerts = document.querySelectorAll('.alert-dismissible');
    alerts.forEach(alert => {
        setTimeout(() => {
            const bsAlert = new bootstrap.Alert(alert);
            bsAlert.close();
        }, 6000);
    });

    // Enable Tooltips
    const tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
    tooltipTriggerList.map(function (tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl);
    });

    // Live search filter for tables
    const tableSearchInput = document.getElementById('tableSearchInput');
    if (tableSearchInput) {
        tableSearchInput.addEventListener('keyup', function() {
            const query = this.value.toLowerCase();
            const tableRows = document.querySelectorAll('.filterable-table tbody tr');
            
            tableRows.forEach(row => {
                const text = row.innerText.toLowerCase();
                if (text.includes(query)) {
                    row.style.display = '';
                } else {
                    row.style.display = 'none';
                }
            });
        });
    }
});
