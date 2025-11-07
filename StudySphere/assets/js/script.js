document.addEventListener('DOMContentLoaded', function() {
    initDarkMode();
    initColorPickers();
    initAutoHideAlerts();
    initTaskCheckboxes();
    initAnimations();
    initTooltips();
});

function initDarkMode() {
    const darkModeToggle = document.getElementById('darkModeToggle');
    const isDarkMode = localStorage.getItem('darkMode') === 'true';
    
    if (isDarkMode) {
        document.body.classList.add('dark-mode');
        if (darkModeToggle) {
            darkModeToggle.innerHTML = '<i class="fas fa-sun"></i>';
        }
    }
    
    if (darkModeToggle) {
        darkModeToggle.addEventListener('click', function() {
            document.body.classList.toggle('dark-mode');
            const isDark = document.body.classList.contains('dark-mode');
            localStorage.setItem('darkMode', isDark);
            this.innerHTML = isDark ? '<i class="fas fa-sun"></i>' : '<i class="fas fa-moon"></i>';
            
            showToast(isDark ? 'Đã bật chế độ tối' : 'Đã tắt chế độ tối', 'info');
        });
    }
}

function initColorPickers() {
    const colorPickers = document.querySelectorAll('.color-option');
    colorPickers.forEach(option => {
        option.addEventListener('click', function() {
            const parent = this.closest('.color-picker-container');
            parent.querySelectorAll('.color-option').forEach(c => c.classList.remove('selected'));
            this.classList.add('selected');
            
            const colorInputId = this.closest('.modal-body').querySelector('input[type="hidden"][name="color"]')?.id;
            if (colorInputId) {
                document.getElementById(colorInputId).value = this.dataset.color;
            }
        });
    });
}

function initAutoHideAlerts() {
    const autoHideAlerts = document.querySelectorAll('.alert:not(.alert-permanent)');
    autoHideAlerts.forEach(alert => {
        setTimeout(() => {
            const bsAlert = new bootstrap.Alert(alert);
            bsAlert.close();
        }, 5000);
    });
}

function initTaskCheckboxes() {
    const taskCheckboxes = document.querySelectorAll('.task-checkbox');
    taskCheckboxes.forEach(checkbox => {
        checkbox.addEventListener('change', function() {
            const taskId = this.dataset.taskId;
            const isCompleted = this.checked;
            updateTaskStatus(taskId, isCompleted);
        });
    });
}

function initAnimations() {
    const observer = new IntersectionObserver((entries) => {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                entry.target.classList.add('animate-in');
            }
        });
    }, { threshold: 0.1 });
    
    document.querySelectorAll('.card, .task-item').forEach(el => {
        observer.observe(el);
    });
}

function initTooltips() {
    const tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
    tooltipTriggerList.map(function (tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl);
    });
}

function updateTaskStatus(taskId, isCompleted) {
    const formData = new FormData();
    formData.append('id', taskId);
    formData.append('action', 'toggle_status');
    formData.append('status', isCompleted ? 'completed' : 'pending');
    
    fetch('tasks.php', {
        method: 'POST',
        headers: { 'X-Requested-With': 'XMLHttpRequest' },
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            if (isCompleted) {
                showCelebration();
            }
            location.reload();
        }
    })
    .catch(error => console.error('Error:', error));
}

function showCelebration() {
    const celebration = document.createElement('div');
    celebration.className = 'celebration';
    celebration.innerHTML = '<img src="https://raw.githubusercontent.com/Tarikul-Islam-Anik/Animated-Fluent-Emojis/master/Emojis/Smilies/Party%20Popper.png" alt="Celebration">';
    document.body.appendChild(celebration);
    
    setTimeout(() => {
        celebration.remove();
    }, 1000);
    
    showToast('Chúc mừng! Bạn đã hoàn thành công việc! 🎉', 'success');
}

function showToast(message, type = 'info') {
    const toast = document.createElement('div');
    toast.className = `alert alert-${type} position-fixed top-0 end-0 m-3`;
    toast.style.zIndex = '9999';
    toast.style.minWidth = '300px';
    toast.innerHTML = `
        <div class="d-flex align-items-center">
            <div class="flex-grow-1">${message}</div>
            <button type="button" class="btn-close ms-2" data-bs-dismiss="alert"></button>
        </div>
    `;
    document.body.appendChild(toast);
    
    setTimeout(() => {
        const bsAlert = new bootstrap.Alert(toast);
        bsAlert.close();
    }, 3000);
}

function confirmDelete(message) {
    return confirm(message || 'Bạn có chắc chắn muốn xóa?');
}

function formatDeadline(deadline) {
    const date = new Date(deadline);
    const now = new Date();
    const diff = date - now;
    const hours = diff / (1000 * 60 * 60);
    
    if (hours < 0) return 'Đã quá hạn';
    if (hours < 24) return 'Sắp đến hạn';
    return 'Còn thời gian';
}

function exportTableToCSV(tableId, filename = 'data.csv') {
    const table = document.getElementById(tableId);
    if (!table) return;
    
    let csv = [];
    const rows = table.querySelectorAll('tr');
    
    rows.forEach(row => {
        const cols = row.querySelectorAll('td, th');
        const rowData = [];
        cols.forEach(col => rowData.push(col.textContent.trim()));
        csv.push(rowData.join(','));
    });
    
    const csvContent = csv.join('\n');
    const blob = new Blob([csvContent], { type: 'text/csv' });
    const url = window.URL.createObjectURL(blob);
    const a = document.createElement('a');
    a.href = url;
    a.download = filename;
    a.click();
    window.URL.revokeObjectURL(url);
    
    showToast('Đã xuất file CSV thành công!', 'success');
}

function duplicateTask(taskId) {
    if (confirm('Bạn muốn nhân bản công việc này?')) {
        window.location.href = `tasks.php?duplicate=${taskId}`;
    }
}

function printPage() {
    window.print();
}

window.showCelebration = showCelebration;
window.showToast = showToast;
window.confirmDelete = confirmDelete;
window.formatDeadline = formatDeadline;
window.exportTableToCSV = exportTableToCSV;
window.duplicateTask = duplicateTask;
window.printPage = printPage;
