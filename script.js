// Replace default alert with custom notifications
function showNotification(message, type = 'info') {
    const toastContainer = document.createElement('div');
    toastContainer.className = 'toast-container';
    
    const toast = document.createElement('div');
    toast.className = `toast show bg-${type} text-white`;
    toast.setAttribute('role', 'alert');
    toast.setAttribute('aria-live', 'assertive');
    toast.setAttribute('aria-atomic', 'true');
    
    const toastBody = document.createElement('div');
    toastBody.className = 'toast-body d-flex align-items-center';
    
    let icon = 'info-circle';
    if (type === 'success') icon = 'check-circle';
    if (type === 'danger') icon = 'exclamation-circle';
    if (type === 'warning') icon = 'exclamation-triangle';
    
    toastBody.innerHTML = `<i class="fas fa-${icon} me-2"></i> ${message}`;
    
    const closeButton = document.createElement('button');
    closeButton.className = 'btn-close btn-close-white ms-auto';
    closeButton.setAttribute('data-bs-dismiss', 'toast');
    closeButton.setAttribute('aria-label', 'Close');
    
    toastBody.appendChild(closeButton);
    toast.appendChild(toastBody);
    toastContainer.appendChild(toast);
    document.body.appendChild(toastContainer);
    
    setTimeout(() => {
        document.body.removeChild(toastContainer);
    }, 3000);
}

// Add animation for table rows
document.addEventListener('DOMContentLoaded', function() {
    const tableRows = document.querySelectorAll('.task-table tbody tr');
    tableRows.forEach((row, index) => {
        row.style.opacity = '0';
        row.style.transform = 'translateY(20px)';
        setTimeout(() => {
            row.style.transition = 'opacity 0.5s ease, transform 0.5s ease';
            row.style.opacity = '1';
            row.style.transform = 'translateY(0)';
        }, 100 * index);
    });
});

// Theme switcher
document.addEventListener('DOMContentLoaded', function() {
    const themeSwitch = document.getElementById('themeSwitch');
    const currentTheme = document.body.classList.contains('dark') ? 'dark' : 'light';

    themeSwitch.addEventListener('click', function() {
        document.body.classList.toggle('dark');
        const newTheme = document.body.classList.contains('dark') ? 'dark' : 'light';
        document.cookie = `theme=${newTheme}; path=/;`;
        themeSwitch.classList.toggle('fa-moon');
        themeSwitch.classList.toggle('fa-sun');
    });

    if (currentTheme === 'dark') {
        themeSwitch.classList.replace('fa-moon', 'fa-sun');
    } else {
        themeSwitch.classList.replace('fa-sun', 'fa-moon');
    }
});

const urlParams = new URLSearchParams(window.location.search);
    const searchParam = urlParams.get('search');
    if (searchParam) {
        const searchInput = document.querySelector('input[name="search"]');
        if (searchInput) {
            searchInput.focus();
        }
    }
