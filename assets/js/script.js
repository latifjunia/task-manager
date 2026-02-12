// Main JavaScript File for Task Manager

document.addEventListener('DOMContentLoaded', function() {
    // Initialize tooltips
    const tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
    const tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl);
    });

    // Handle form submissions with AJAX
    const forms = document.querySelectorAll('form[data-ajax="true"]');
    forms.forEach(form => {
        form.addEventListener('submit', function(e) {
            e.preventDefault();
            submitFormAjax(this);
        });
    });

    // Mark notifications as read
    const notificationItems = document.querySelectorAll('.notification-item');
    notificationItems.forEach(item => {
        item.addEventListener('click', function() {
            const notificationId = this.dataset.notificationId;
            markNotificationAsRead(notificationId);
        });
    });

    // Update task due date warnings
    updateDueDateWarnings();
    
    // Initialize any date pickers
    initializeDatePickers();
    
    // Initialize search functionality
    initializeSearch();
});

// AJAX Form Submission
function submitFormAjax(form) {
    const formData = new FormData(form);
    const submitBtn = form.querySelector('button[type="submit"]');
    const originalText = submitBtn.innerHTML;

    // Show loading state
    submitBtn.innerHTML = '<span class="spinner-border spinner-border-sm"></span> Memproses...';
    submitBtn.disabled = true;

    fetch(form.action, {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showAlert('success', data.message);
            if (form.dataset.reload === 'true') {
                setTimeout(() => location.reload(), 1500);
            }
            // Clear form if needed
            if (form.dataset.clear === 'true') {
                form.reset();
            }
        } else {
            showAlert('danger', data.message);
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showAlert('danger', 'Terjadi kesalahan: ' + error);
    })
    .finally(() => {
        submitBtn.innerHTML = originalText;
        submitBtn.disabled = false;
    });
}

// Show Bootstrap Alert
function showAlert(type, message) {
    // Remove existing alerts
    const existingAlerts = document.querySelectorAll('.alert');
    existingAlerts.forEach(alert => {
        const bsAlert = bootstrap.Alert.getOrCreateInstance(alert);
        bsAlert.close();
    });
    
    const alertDiv = document.createElement('div');
    alertDiv.className = `alert alert-${type} alert-dismissible fade show`;
    alertDiv.innerHTML = `
        <i class="bi ${type === 'success' ? 'bi-check-circle' : 'bi-exclamation-triangle'} me-2"></i>
        ${message}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    `;

    const container = document.querySelector('.container-fluid') || document.querySelector('.container');
    if (container) {
        container.insertBefore(alertDiv, container.firstChild);
    } else {
        document.body.insertBefore(alertDiv, document.body.firstChild);
    }

    // Auto remove after 5 seconds
    setTimeout(() => {
        const alert = bootstrap.Alert.getOrCreateInstance(alertDiv);
        alert.close();
    }, 5000);
}

// Mark notification as read
function markNotificationAsRead(notificationId) {
    fetch('api/notifications.php?action=mark_read', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: 'notification_id=' + notificationId
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            const notificationBadge = document.querySelector('.notification-badge');
            if (notificationBadge) {
                const currentCount = parseInt(notificationBadge.textContent);
                if (currentCount > 1) {
                    notificationBadge.textContent = currentCount - 1;
                } else {
                    notificationBadge.remove();
                }
            }
        }
    })
    .catch(error => console.error('Error:', error));
}

// Update due date warnings
function updateDueDateWarnings() {
    const taskCards = document.querySelectorAll('.task-card');
    const today = new Date();
    
    taskCards.forEach(card => {
        const dueDateStr = card.dataset.dueDate;
        if (!dueDateStr) return;
        
        const dueDate = new Date(dueDateStr);
        const timeDiff = dueDate.getTime() - today.getTime();
        const daysDiff = Math.ceil(timeDiff / (1000 * 3600 * 24));
        
        if (daysDiff < 0) {
            // Overdue
            card.classList.add('overdue');
            if (!card.querySelector('.overdue-badge')) {
                const badge = document.createElement('span');
                badge.className = 'badge bg-danger position-absolute top-0 end-0 m-1 overdue-badge';
                badge.textContent = 'Terlambat';
                badge.style.fontSize = '0.7rem';
                card.appendChild(badge);
            }
        } else if (daysDiff <= 2) {
            // Due soon
            if (!card.querySelector('.due-soon-badge')) {
                const badge = document.createElement('span');
                badge.className = 'badge bg-warning position-absolute top-0 end-0 m-1 due-soon-badge';
                badge.textContent = 'Segera';
                badge.style.fontSize = '0.7rem';
                card.appendChild(badge);
            }
        }
    });
}

// Format date for display
function formatDate(dateString) {
    if (!dateString) return '';
    const date = new Date(dateString);
    return date.toLocaleDateString('id-ID', {
        weekday: 'long',
        year: 'numeric',
        month: 'long',
        day: 'numeric'
    });
}

// Upload file with progress
function uploadFile(input, taskId) {
    const file = input.files[0];
    if (!file) return;
    
    const formData = new FormData();
    formData.append('file', file);
    formData.append('task_id', taskId);
    
    // Show progress bar
    const progressDiv = document.createElement('div');
    progressDiv.className = 'progress mt-2';
    progressDiv.innerHTML = `
        <div class="progress-bar" role="progressbar" style="width: 0%"></div>
    `;
    input.parentNode.appendChild(progressDiv);
    
    fetch('api/attachments.php?action=upload', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showAlert('success', 'File berhasil diunggah');
            if (typeof refreshTaskAttachments === 'function') {
                refreshTaskAttachments(taskId);
            }
        } else {
            showAlert('danger', data.message);
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showAlert('danger', 'Upload gagal: ' + error);
    })
    .finally(() => {
        progressDiv.remove();
    });
}

// Search tasks
function searchTasks(query) {
    const taskCards = document.querySelectorAll('.task-card');
    taskCards.forEach(card => {
        const title = card.querySelector('.task-title').textContent.toLowerCase();
        const description = card.querySelector('.task-description')?.textContent.toLowerCase() || '';
        
        if (title.includes(query.toLowerCase()) || description.includes(query.toLowerCase())) {
            card.style.display = 'block';
        } else {
            card.style.display = 'none';
        }
    });
}

// Initialize date pickers
function initializeDatePickers() {
    const dateInputs = document.querySelectorAll('input[type="date"]');
    dateInputs.forEach(input => {
        // Set min date to today
        const today = new Date().toISOString().split('T')[0];
        input.min = today;
        
        // Add date picker styling
        input.addEventListener('focus', function() {
            this.type = 'date';
        });
        
        input.addEventListener('blur', function() {
            if (!this.value) {
                this.type = 'text';
            }
        });
    });
}

// Initialize search
function initializeSearch() {
    const searchInput = document.querySelector('input[type="search"]');
    if (searchInput) {
        searchInput.addEventListener('input', function() {
            searchTasks(this.value);
        });
    }
}

// Keyboard shortcuts
document.addEventListener('keydown', function(e) {
    // Ctrl + N: New task
    if (e.ctrlKey && e.key === 'n') {
        e.preventDefault();
        const newTaskBtn = document.querySelector('[data-bs-target="#newTaskModal"]');
        if (newTaskBtn) {
            newTaskBtn.click();
        }
    }
    
    // Esc: Close modal
    if (e.key === 'Escape') {
        const modal = document.querySelector('.modal.show');
        if (modal) {
            const modalInstance = bootstrap.Modal.getInstance(modal);
            modalInstance.hide();
        }
    }
    
    // Ctrl + F: Focus search
    if (e.ctrlKey && e.key === 'f') {
        e.preventDefault();
        const searchInput = document.querySelector('input[type="search"]');
        if (searchInput) {
            searchInput.focus();
        }
    }
});

// Real-time updates (simulated with polling)
function checkForUpdates() {
    fetch('api/notifications.php?action=check_updates')
        .then(response => response.json())
        .then(data => {
            if (data.has_updates) {
                showAlert('info', 'Ada pembaruan baru di proyek Anda');
            }
        })
        .catch(error => console.error('Error:', error));
}

// Check for updates every 30 seconds
setInterval(checkForUpdates, 30000);

// Toast notification
function showToast(type, message) {
    const toastContainer = document.querySelector('.toast-container') || createToastContainer();
    const toastId = 'toast-' + Date.now();
    const toast = document.createElement('div');
    toast.id = toastId;
    toast.className = `toast align-items-center text-white bg-${type} border-0`;
    toast.setAttribute('role', 'alert');
    toast.setAttribute('aria-live', 'assertive');
    toast.setAttribute('aria-atomic', 'true');
    
    toast.innerHTML = `
        <div class="d-flex">
            <div class="toast-body">
                <i class="bi ${type === 'success' ? 'bi-check-circle' : 'bi-exclamation-triangle'} me-2"></i>
                ${message}
            </div>
            <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"></button>
        </div>
    `;
    
    toastContainer.appendChild(toast);
    
    const bsToast = new bootstrap.Toast(toast, {
        autohide: true,
        delay: 3000
    });
    bsToast.show();
    
    toast.addEventListener('hidden.bs.toast', function () {
        toast.remove();
    });
}

function createToastContainer() {
    const container = document.createElement('div');
    container.className = 'toast-container position-fixed top-0 end-0 p-3';
    document.body.appendChild(container);
    return container;
}

// Confirmation dialog
function confirmAction(message, callback) {
    if (confirm(message)) {
        callback();
    }
}

// Load more items (for pagination)
function loadMoreItems(containerId, url, params = {}) {
    const container = document.getElementById(containerId);
    if (!container) return;
    
    const loader = container.querySelector('.load-more-loader');
    const loadMoreBtn = container.querySelector('.load-more-btn');
    
    if (loader || loadMoreBtn) {
        if (loader) loader.style.display = 'block';
        if (loadMoreBtn) loadMoreBtn.style.display = 'none';
        
        fetch(url + '?' + new URLSearchParams(params))
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Append new items
                    if (data.html) {
                        container.insertAdjacentHTML('beforeend', data.html);
                    }
                    
                    // Update load more button or hide if no more items
                    if (loadMoreBtn) {
                        if (data.has_more) {
                            loadMoreBtn.style.display = 'block';
                            loadMoreBtn.onclick = function() {
                                params.page = (params.page || 1) + 1;
                                loadMoreItems(containerId, url, params);
                            };
                        } else {
                            loadMoreBtn.style.display = 'none';
                        }
                    }
                }
            })
            .catch(error => console.error('Error:', error))
            .finally(() => {
                if (loader) loader.style.display = 'none';
            });
    }
}

// Initialize drag and drop for task cards
function initializeTaskDragDrop() {
    const taskCards = document.querySelectorAll('.task-card');
    taskCards.forEach(card => {
        card.setAttribute('draggable', 'true');
        
        card.addEventListener('dragstart', function(e) {
            e.dataTransfer.setData('text/plain', this.dataset.taskId);
            this.classList.add('dragging');
        });
        
        card.addEventListener('dragend', function() {
            this.classList.remove('dragging');
        });
    });
    
    const columns = document.querySelectorAll('.kanban-column .sortable-list');
    columns.forEach(column => {
        column.addEventListener('dragover', function(e) {
            e.preventDefault();
            this.classList.add('drag-over');
        });
        
        column.addEventListener('dragleave', function() {
            this.classList.remove('drag-over');
        });
        
        column.addEventListener('drop', function(e) {
            e.preventDefault();
            this.classList.remove('drag-over');
            
            const taskId = e.dataTransfer.getData('text/plain');
            const taskCard = document.querySelector(`.task-card[data-task-id="${taskId}"]`);
            
            if (taskCard && taskCard.parentNode !== this) {
                this.appendChild(taskCard);
                
                // Get new status from column
                const columnStatus = this.parentElement.querySelector('h5').textContent.trim();
                const statusMap = {
                    'To Do': 'todo',
                    'In Progress': 'in_progress',
                    'Review': 'review',
                    'Done': 'done'
                };
                
                const newStatus = statusMap[columnStatus] || 'todo';
                
                // Update task status via API
                updateTaskStatus(taskId, newStatus);
            }
        });
    });
}

// Update task status
function updateTaskStatus(taskId, status) {
    fetch('api/tasks.php?action=update_status', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: 'task_id=' + taskId + '&status=' + status
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showToast('success', 'Status tugas berhasil diperbarui');
        } else {
            showToast('error', data.message || 'Gagal memperbarui status');
            // Reload to reset
            setTimeout(() => location.reload(), 1000);
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showToast('error', 'Terjadi kesalahan');
        setTimeout(() => location.reload(), 1000);
    });
}