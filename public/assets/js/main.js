// Main JavaScript file for Salvio POS

document.addEventListener('DOMContentLoaded', function() {
    // Auto-hide alerts after 5 seconds
    const alerts = document.querySelectorAll('.alert');
    alerts.forEach(alert => {
        setTimeout(() => {
            alert.style.opacity = '0';
            setTimeout(() => alert.remove(), 500);
        }, 5000);
    });

    // Enable tooltips
    const tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
    tooltipTriggerList.map(function (tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl);
    });

    // Enable popovers
    const popoverTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="popover"]'));
    popoverTriggerList.map(function (popoverTriggerEl) {
        return new bootstrap.Popover(popoverTriggerEl);
    });

    // Form validation
    const forms = document.querySelectorAll('.needs-validation');
    forms.forEach(form => {
        form.addEventListener('submit', event => {
            if (!form.checkValidity()) {
                event.preventDefault();
                event.stopPropagation();
            }
            form.classList.add('was-validated');
        });
    });

    // Confirm delete actions
    const deleteButtons = document.querySelectorAll('[data-confirm]');
    deleteButtons.forEach(button => {
        button.addEventListener('click', event => {
            if (!confirm(button.dataset.confirm || 'Are you sure you want to delete this item?')) {
                event.preventDefault();
            }
        });
    });

    // Handle dynamic form inputs
    const addRowButtons = document.querySelectorAll('.add-row');
    addRowButtons.forEach(button => {
        button.addEventListener('click', () => {
            const template = document.querySelector(button.dataset.template);
            if (template) {
                const clone = template.content.cloneNode(true);
                const container = document.querySelector(button.dataset.container);
                if (container) {
                    container.appendChild(clone);
                }
            }
        });
    });

    // Handle responsive tables
    const tables = document.querySelectorAll('.table-responsive');
    tables.forEach(table => {
        const headerHeight = table.querySelector('thead').offsetHeight;
        table.style.setProperty('--header-height', `${headerHeight}px`);
    });
});

// Utility functions
const formatCurrency = (amount, currency = 'IDR') => {
    return new Intl.NumberFormat('id-ID', {
        style: 'currency',
        currency: currency
    }).format(amount);
};

const formatDate = (date, format = 'long') => {
    const options = format === 'long' 
        ? { weekday: 'long', year: 'numeric', month: 'long', day: 'numeric' }
        : { year: 'numeric', month: 'short', day: 'numeric' };
    return new Date(date).toLocaleDateString('id-ID', options);
};

// AJAX helper function
const ajax = async (url, options = {}) => {
    try {
        const response = await fetch(url, {
            headers: {
                'Content-Type': 'application/json',
                'X-Requested-With': 'XMLHttpRequest'
            },
            ...options
        });
        
        if (!response.ok) {
            throw new Error(`HTTP error! status: ${response.status}`);
        }
        
        return await response.json();
    } catch (error) {
        console.error('Ajax Error:', error);
        throw error;
    }
};
