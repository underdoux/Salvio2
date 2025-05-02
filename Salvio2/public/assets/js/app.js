// Initialize Bootstrap tooltips and popovers
document.addEventListener('DOMContentLoaded', function() {
    var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
    tooltipTriggerList.map(function(tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl);
    });

    var popoverTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="popover"]'));
    popoverTriggerList.map(function(popoverTriggerEl) {
        return new bootstrap.Popover(popoverTriggerEl);
    });
});

// AJAX Setup
$.ajaxSetup({
    headers: {
        'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
    }
});

// Loading Spinner
const showLoading = () => {
    const spinner = document.createElement('div');
    spinner.className = 'spinner-overlay';
    spinner.innerHTML = `
        <div class="spinner-border text-primary" role="status">
            <span class="visually-hidden">Loading...</span>
        </div>
    `;
    document.body.appendChild(spinner);
};

const hideLoading = () => {
    const spinner = document.querySelector('.spinner-overlay');
    if (spinner) {
        spinner.remove();
    }
};

// Form Validation
const validateForm = (form) => {
    let isValid = true;
    form.querySelectorAll('[required]').forEach(input => {
        if (!input.value.trim()) {
            isValid = false;
            input.classList.add('is-invalid');
        } else {
            input.classList.remove('is-invalid');
        }
    });
    return isValid;
};

// Format Currency
const formatCurrency = (amount) => {
    return new Intl.NumberFormat('id-ID', {
        style: 'currency',
        currency: 'IDR',
        minimumFractionDigits: 0,
        maximumFractionDigits: 0
    }).format(amount);
};

// Format Date
const formatDate = (date) => {
    return new Intl.DateTimeFormat('id-ID', {
        year: 'numeric',
        month: 'long',
        day: 'numeric'
    }).format(new Date(date));
};

// Format DateTime
const formatDateTime = (datetime) => {
    return new Intl.DateTimeFormat('id-ID', {
        year: 'numeric',
        month: 'long',
        day: 'numeric',
        hour: '2-digit',
        minute: '2-digit'
    }).format(new Date(datetime));
};

// Confirm Dialog
const confirmDialog = (message, callback) => {
    const modal = document.createElement('div');
    modal.className = 'modal fade';
    modal.innerHTML = `
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Confirm Action</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <p>${message}</p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-primary confirm-btn">Confirm</button>
                </div>
            </div>
        </div>
    `;
    document.body.appendChild(modal);

    const modalInstance = new bootstrap.Modal(modal);
    modalInstance.show();

    modal.querySelector('.confirm-btn').addEventListener('click', () => {
        modalInstance.hide();
        callback();
    });

    modal.addEventListener('hidden.bs.modal', () => {
        modal.remove();
    });
};

// Toast Notification
const showToast = (message, type = 'success') => {
    const toast = document.createElement('div');
    toast.className = 'toast align-items-center text-white bg-' + type;
    toast.setAttribute('role', 'alert');
    toast.setAttribute('aria-live', 'assertive');
    toast.setAttribute('aria-atomic', 'true');
    toast.innerHTML = `
        <div class="d-flex">
            <div class="toast-body">
                ${message}
            </div>
            <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"></button>
        </div>
    `;
    
    const container = document.querySelector('.toast-container') || (() => {
        const c = document.createElement('div');
        c.className = 'toast-container position-fixed bottom-0 end-0 p-3';
        document.body.appendChild(c);
        return c;
    })();
    
    container.appendChild(toast);
    const toastInstance = new bootstrap.Toast(toast);
    toastInstance.show();
    
    toast.addEventListener('hidden.bs.toast', () => {
        toast.remove();
        if (!container.hasChildNodes()) {
            container.remove();
        }
    });
};

// Print Function
const printElement = (element) => {
    const printWindow = window.open('', '_blank');
    printWindow.document.write(`
        <html>
            <head>
                <title>Print</title>
                <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
                <link href="${window.location.origin}/assets/css/style.css" rel="stylesheet">
                <style>
                    @media print {
                        @page { margin: 0; }
                        body { margin: 1cm; }
                    }
                </style>
            </head>
            <body>
                ${element.innerHTML}
                <script>window.onload = () => window.print();</script>
            </body>
        </html>
    `);
    printWindow.document.close();
};

// Export to Excel
const exportToExcel = (table, filename) => {
    const wb = XLSX.utils.table_to_book(table, { sheet: "Sheet1" });
    XLSX.writeFile(wb, filename);
};

// Handle Form Submission
document.addEventListener('submit', function(e) {
    const form = e.target;
    if (form.classList.contains('needs-validation')) {
        e.preventDefault();
        if (!validateForm(form)) {
            showToast('Please fill in all required fields', 'danger');
            return;
        }
        form.submit();
    }
});

// Handle Delete Buttons
document.addEventListener('click', function(e) {
    if (e.target.classList.contains('delete-btn')) {
        e.preventDefault();
        const url = e.target.href || e.target.dataset.url;
        confirmDialog('Are you sure you want to delete this item?', () => {
            showLoading();
            fetch(url, {
                method: 'DELETE',
                headers: {
                    'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                }
            })
            .then(response => response.json())
            .then(data => {
                hideLoading();
                if (data.success) {
                    showToast(data.message);
                    if (data.redirect) {
                        window.location.href = data.redirect;
                    } else {
                        e.target.closest('tr')?.remove();
                    }
                } else {
                    showToast(data.message, 'danger');
                }
            })
            .catch(error => {
                hideLoading();
                showToast('An error occurred', 'danger');
            });
        });
    }
});
