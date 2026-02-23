document.addEventListener('DOMContentLoaded', function() {
    const commentForms = document.querySelectorAll('.comment-form');
    
    commentForms.forEach(form => {
        form.addEventListener('submit', async function(e) {
            e.preventDefault();
            
            const formData = new FormData(this);
            const taskId = this.dataset.taskId;
            const commentInput = this.querySelector('textarea[name="content"]');
            const commentText = commentInput.value.trim();
            
            if (!commentText) {
                showNotification('Komentar tidak boleh kosong', 'error');
                return;
            }
            
            // Tampilkan loading
            const submitBtn = this.querySelector('button[type="submit"]');
            const originalText = submitBtn.textContent;
            submitBtn.disabled = true;
            submitBtn.textContent = 'Menyimpan...';
            
            try {
                const response = await fetch('api/comments.php?action=add', {
                    method: 'POST',
                    body: formData
                });
                
                const result = await response.json();
                
                if (result.success) {
                    // Bersihkan form
                    commentInput.value = '';
                    
                    // Tampilkan notifikasi sukses
                    showNotification('Komentar berhasil ditambahkan', 'success');
                    
                    // Refresh daftar komentar (opsional)
                    loadComments(taskId);
                } else {
                    showNotification(result.message || 'Gagal menambahkan komentar', 'error');
                }
            } catch (error) {
                console.error('Error:', error);
                showNotification('Terjadi kesalahan koneksi', 'error');
            } finally {
                // Kembalikan tombol ke keadaan semula
                submitBtn.disabled = false;
                submitBtn.textContent = originalText;
            }
        });
    });
    
    // Fungsi untuk menampilkan notifikasi
    function showNotification(message, type = 'info') {
        // Cek apakah sudah ada container notifikasi
        let container = document.querySelector('.notification-container');
        
        if (!container) {
            container = document.createElement('div');
            container.className = 'notification-container';
            container.style.cssText = `
                position: fixed;
                top: 20px;
                right: 20px;
                z-index: 9999;
            `;
            document.body.appendChild(container);
        }
        
        const notification = document.createElement('div');
        notification.className = `notification notification-${type}`;
        notification.style.cssText = `
            background: ${type === 'success' ? '#4CAF50' : type === 'error' ? '#f44336' : '#2196F3'};
            color: white;
            padding: 12px 20px;
            margin-bottom: 10px;
            border-radius: 4px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.2);
            animation: slideIn 0.3s ease;
            cursor: pointer;
        `;
        notification.textContent = message;
        
        container.appendChild(notification);
        
        // Auto remove setelah 3 detik
        setTimeout(() => {
            notification.style.animation = 'slideOut 0.3s ease';
            setTimeout(() => {
                if (notification.parentNode) {
                    notification.remove();
                }
            }, 300);
        }, 3000);
        
        // Hapus jika diklik
        notification.addEventListener('click', () => {
            notification.remove();
        });
    }
    
    // Fungsi untuk load komentar (opsional)
    async function loadComments(taskId) {
        const commentsContainer = document.querySelector(`.comments-list[data-task-id="${taskId}"]`);
        if (!commentsContainer) return;
        
        try {
            const response = await fetch(`api/comments.php?action=list&task_id=${taskId}`);
            const result = await response.json();
            
            if (result.success && result.data) {
                renderComments(commentsContainer, result.data);
            }
        } catch (error) {
            console.error('Error loading comments:', error);
        }
    }
    
    function renderComments(container, comments) {
        if (!comments || comments.length === 0) {
            container.innerHTML = '<p class="no-comments">Belum ada komentar</p>';
            return;
        }
        
        let html = '';
        comments.forEach(comment => {
            html += `
                <div class="comment-item" data-comment-id="${comment.id}">
                    <div class="comment-header">
                        <strong>${comment.full_name}</strong>
                        <span class="comment-date">${formatDate(comment.created_at)}</span>
                    </div>
                    <div class="comment-content">${escapeHtml(comment.content)}</div>
                </div>
            `;
        });
        
        container.innerHTML = html;
    }
    
    function formatDate(dateString) {
        const date = new Date(dateString);
        const now = new Date();
        const diffTime = Math.abs(now - date);
        const diffDays = Math.ceil(diffTime / (1000 * 60 * 60 * 24));
        
        if (diffDays === 0) {
            return 'Hari ini, ' + date.toLocaleTimeString('id-ID', { hour: '2-digit', minute: '2-digit' });
        } else if (diffDays === 1) {
            return 'Kemarin, ' + date.toLocaleTimeString('id-ID', { hour: '2-digit', minute: '2-digit' });
        } else {
            return date.toLocaleDateString('id-ID', { 
                day: 'numeric', 
                month: 'short', 
                year: 'numeric',
                hour: '2-digit',
                minute: '2-digit'
            });
        }
    }
    
    function escapeHtml(text) {
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }
});

// Tambahkan CSS animations
const style = document.createElement('style');
style.textContent = `
    @keyframes slideIn {
        from {
            transform: translateX(100%);
            opacity: 0;
        }
        to {
            transform: translateX(0);
            opacity: 1;
        }
    }
    
    @keyframes slideOut {
        from {
            transform: translateX(0);
            opacity: 1;
        }
        to {
            transform: translateX(100%);
            opacity: 0;
        }
    }
    
    .comment-form {
        margin-top: 10px;
    }
    
    .comment-form textarea {
        width: 100%;
        padding: 8px;
        border: 1px solid #ddd;
        border-radius: 4px;
        resize: vertical;
        min-height: 60px;
        font-family: inherit;
    }
    
    .comment-form button {
        margin-top: 5px;
        padding: 8px 15px;
        background: #4CAF50;
        color: white;
        border: none;
        border-radius: 4px;
        cursor: pointer;
    }
    
    .comment-form button:hover {
        background: #45a049;
    }
    
    .comment-form button:disabled {
        background: #cccccc;
        cursor: not-allowed;
    }
    
    .comments-list {
        margin-top: 15px;
        max-height: 300px;
        overflow-y: auto;
    }
    
    .comment-item {
        padding: 10px;
        border-bottom: 1px solid #eee;
    }
    
    .comment-item:last-child {
        border-bottom: none;
    }
    
    .comment-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 5px;
        font-size: 0.9em;
    }
    
    .comment-date {
        color: #999;
        font-size: 0.85em;
    }
    
    .comment-content {
        line-height: 1.4;
        word-wrap: break-word;
    }
    
    .no-comments {
        text-align: center;
        color: #999;
        padding: 20px;
        font-style: italic;
    }
`;

document.head.appendChild(style);