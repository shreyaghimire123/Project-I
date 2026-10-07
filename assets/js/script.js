// Academic Clearance System - Vanilla JS

document.addEventListener('DOMContentLoaded', function () {
    // Mobile menu toggle
    const menuToggle = document.getElementById('menuToggle');
    const sidebar = document.querySelector('.sidebar');
    if (menuToggle && sidebar) {
        menuToggle.addEventListener('click', () => {
            sidebar.classList.toggle('open');
        });
    }

    // Alert close buttons
    document.querySelectorAll('.alert-close').forEach(btn => {
        btn.addEventListener('click', function () {
            this.parentElement.style.display = 'none';
        });
    });

    // Password visibility toggle
    document.querySelectorAll('.toggle-password').forEach(btn => {
        btn.addEventListener('click', function () {
            const input = document.querySelector(this.dataset.target);
            if (input) {
                input.type = input.type === 'password' ? 'text' : 'password';
                this.textContent = input.type === 'password' ? 'Show' : 'Hide';
            }
        });
    });

    // Confirm dialogs
    document.querySelectorAll('[data-confirm]').forEach(el => {
        el.addEventListener('click', function (e) {
            if (!confirm(this.dataset.confirm)) {
                e.preventDefault();
            }
        });
    });

    // Simple search filter for tables
    const searchInput = document.getElementById('tableSearch');
    if (searchInput) {
        searchInput.addEventListener('keyup', function () {
            const filter = this.value.toLowerCase();
            const rows = document.querySelectorAll('#dataTable tbody tr');
            rows.forEach(row => {
                const text = row.textContent.toLowerCase();
                row.style.display = text.includes(filter) ? '' : 'none';
            });
        });
    }

    // Status filter
    const statusFilter = document.getElementById('statusFilter');
    if (statusFilter) {
        statusFilter.addEventListener('change', function () {
            const val = this.value.toLowerCase();
            const rows = document.querySelectorAll('#dataTable tbody tr');
            rows.forEach(row => {
                if (!val) {
                    row.style.display = '';
                    return;
                }
                const status = row.dataset.status || '';
                row.style.display = status === val ? '' : 'none';
            });
        });
    }

    // Animate progress bars
    document.querySelectorAll('.progress-fill').forEach(bar => {
        const width = bar.style.width;
        bar.style.width = '0';
        setTimeout(() => { bar.style.width = width; }, 100);
    });
});
