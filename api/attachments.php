<?php
require_once '../includes/config.php';
require_once '../includes/functions.php';

if (!isLoggedIn()) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$action = $_GET['action'] ?? '';

switch ($action) {
    case 'upload':
        uploadAttachmentHandler();
        break;
    case 'get':
        getAttachmentsHandler();
        break;
    case 'delete':
        deleteAttachmentHandler();
        break;
    default:
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'Invalid action']);
}

function uploadAttachmentHandler() {
    global $pdo;
    
    header('Content-Type: application/json');
    
    try {
        $task_id = intval($_POST['task_id'] ?? 0);
        
        if ($task_id <= 0) {
            echo json_encode(['success' => false, 'message' => 'Task ID tidak valid']);
            return;
        }
        
        if (!isset($_FILES['file']) || $_FILES['file']['error'] !== UPLOAD_ERR_OK) {
            echo json_encode(['success' => false, 'message' => 'File tidak ditemukan atau error saat upload']);
            return;
        }
        
        // Check access to task
        if (!hasTaskAccess($task_id, $_SESSION['user_id'])) {
            echo json_encode(['success' => false, 'message' => 'Tidak memiliki akses ke tugas ini']);
            return;
        }
        
        $file = $_FILES['file'];
        
        // Validation
        $max_size = 10 * 1024 * 1024; // 10MB
        $allowed_types = [
            'image/jpeg', 'image/png', 'image/gif', 'image/webp',
            'application/pdf',
            'application/msword',
            'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            'application/vnd.ms-excel',
            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'application/vnd.ms-powerpoint',
            'application/vnd.openxmlformats-officedocument.presentationml.presentation',
            'text/plain', 'text/csv',
            'application/zip', 'application/x-rar-compressed',
            'audio/mpeg', 'video/mp4'
        ];
        
        // File size validation
        if ($file['size'] > $max_size) {
            echo json_encode(['success' => false, 'message' => 'Ukuran file maksimal 10MB']);
            return;
        }
        
        // File type validation
        if (!in_array($file['type'], $allowed_types)) {
            echo json_encode(['success' => false, 'message' => 'Tipe file tidak diizinkan']);
            return;
        }
        
        // File extension validation
        $file_ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        $allowed_ext = ['jpg', 'jpeg', 'png', 'gif', 'webp', 'pdf', 'doc', 'docx', 'xls', 'xlsx', 'ppt', 'pptx', 'txt', 'csv', 'zip', 'rar', 'mp3', 'mp4'];
        
        if (!in_array($file_ext, $allowed_ext)) {
            echo json_encode(['success' => false, 'message' => 'Ekstensi file tidak diizinkan']);
            return;
        }
        
        // Create upload directory if not exists
        $upload_dir = '../uploads/';
        if (!file_exists($upload_dir)) {
            mkdir($upload_dir, 0777, true);
        }
        
        // Create year-month directory
        $year_month = date('Y-m');
        $month_dir = $upload_dir . $year_month . '/';
        if (!file_exists($month_dir)) {
            mkdir($month_dir, 0777, true);
        }
        
        // Generate unique filename
        $filename = uniqid() . '_' . preg_replace('/[^a-zA-Z0-9._-]/', '_', basename($file['name']));
        $filepath = $month_dir . $filename;
        $relative_path = 'uploads/' . $year_month . '/' . $filename;
        
        // Move uploaded file
        if (move_uploaded_file($file['tmp_name'], $filepath)) {
            // Get task info
            $task = getTaskById($task_id);
            if (!$task) {
                unlink($filepath); // Delete file
                echo json_encode(['success' => false, 'message' => 'Tugas tidak ditemukan']);
                return;
            }
            
            // Insert attachment record
            $stmt = $pdo->prepare("
                INSERT INTO attachments (task_id, filename, filepath, uploaded_by) 
                VALUES (?, ?, ?, ?)
            ");
            
            $success = $stmt->execute([
                $task_id,
                $file['name'],
                $relative_path,
                $_SESSION['user_id']
            ]);
            
            if ($success) {
                // Add comment about attachment
                $stmt = $pdo->prepare("
                    INSERT INTO comments (task_id, user_id, content, attachment) 
                    VALUES (?, ?, ?, ?)
                ");
                
                $comment = $_SESSION['full_name'] . ' mengunggah file: ' . $file['name'];
                $stmt->execute([
                    $task_id,
                    $_SESSION['user_id'],
                    $comment,
                    $relative_path
                ]);
                
                // Create notifications for task creator and assignee
                $users_to_notify = [];
                
                if ($task['created_by'] != $_SESSION['user_id']) {
                    $users_to_notify[] = $task['created_by'];
                }
                
                if ($task['assignee_id'] && $task['assignee_id'] != $_SESSION['user_id']) {
                    $users_to_notify[] = $task['assignee_id'];
                }
                
                $users_to_notify = array_unique($users_to_notify);
                
                foreach ($users_to_notify as $user_id) {
                    createNotification(
                        $user_id,
                        'File Diunggah',
                        $_SESSION['full_name'] . ' mengunggah file ke tugas: ' . $task['title'],
                        'system'
                    );
                }
                
                echo json_encode([
                    'success' => true, 
                    'message' => 'File berhasil diunggah',
                    'filename' => $file['name'],
                    'filepath' => $relative_path,
                    'filetype' => $file['type'],
                    'filesize' => formatFileSize($file['size'])
                ]);
            } else {
                unlink($filepath); // Delete file if db insert fails
                echo json_encode(['success' => false, 'message' => 'Gagal menyimpan informasi file']);
            }
            
        } else {
            echo json_encode(['success' => false, 'message' => 'Gagal menyimpan file']);
        }
        
    } catch (Exception $e) {
        error_log("Error uploadAttachmentHandler: " . $e->getMessage());
        echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
    }
}

function getAttachmentsHandler() {
    global $pdo;
    
    header('Content-Type: application/json');
    
    try {
        $task_id = intval($_GET['task_id'] ?? 0);
        
        if ($task_id <= 0) {
            echo json_encode(['success' => false, 'message' => 'Task ID tidak valid']);
            return;
        }
        
        // Check access to task
        if (!hasTaskAccess($task_id, $_SESSION['user_id'])) {
            echo json_encode(['success' => false, 'message' => 'Tidak memiliki akses ke tugas ini']);
            return;
        }
        
        $attachments = getTaskAttachments($task_id);
        
        // Format file sizes
        foreach ($attachments as &$attachment) {
            if (file_exists('../' . $attachment['filepath'])) {
                $attachment['filesize'] = formatFileSize(filesize('../' . $attachment['filepath']));
            } else {
                $attachment['filesize'] = 'Tidak ditemukan';
            }
            
            // Get file icon
            $extension = strtolower(pathinfo($attachment['filename'], PATHINFO_EXTENSION));
            $attachment['fileicon'] = getFileIcon($extension);
        }
        
        echo json_encode([
            'success' => true,
            'attachments' => $attachments,
            'total' => count($attachments)
        ]);
        
    } catch (Exception $e) {
        error_log("Error getAttachmentsHandler: " . $e->getMessage());
        echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
    }
}

function deleteAttachmentHandler() {
    global $pdo;
    
    header('Content-Type: application/json');
    
    try {
        $attachment_id = intval($_POST['attachment_id'] ?? 0);
        
        if ($attachment_id <= 0) {
            echo json_encode(['success' => false, 'message' => 'Attachment ID tidak valid']);
            return;
        }
        
        // Get attachment info
        $stmt = $pdo->prepare("
            SELECT a.*, t.created_by, t.assignee_id 
            FROM attachments a
            JOIN tasks t ON a.task_id = t.id
            WHERE a.id = ?
        ");
        $stmt->execute([$attachment_id]);
        $attachment = $stmt->fetch();
        
        if (!$attachment) {
            echo json_encode(['success' => false, 'message' => 'File tidak ditemukan']);
            return;
        }
        
        // Check permission
        $can_delete = false;
        
        // Uploader can delete
        if ($attachment['uploaded_by'] == $_SESSION['user_id']) {
            $can_delete = true;
        }
        // Task creator can delete
        elseif ($attachment['created_by'] == $_SESSION['user_id']) {
            $can_delete = true;
        }
        // Admin can delete
        elseif (isAdmin()) {
            $can_delete = true;
        }
        
        if (!$can_delete) {
            echo json_encode(['success' => false, 'message' => 'Tidak diizinkan menghapus file ini']);
            return;
        }
        
        // Delete file from server
        $filepath = '../' . $attachment['filepath'];
        if (file_exists($filepath)) {
            unlink($filepath);
        }
        
        // Delete from database
        $stmt = $pdo->prepare("DELETE FROM attachments WHERE id = ?");
        $success = $stmt->execute([$attachment_id]);
        
        if ($success) {
            // Also remove from comments if exists
            $stmt = $pdo->prepare("DELETE FROM comments WHERE attachment = ?");
            $stmt->execute([$attachment['filepath']]);
            
            echo json_encode(['success' => true, 'message' => 'File berhasil dihapus']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Gagal menghapus file dari database']);
        }
        
    } catch (Exception $e) {
        error_log("Error deleteAttachmentHandler: " . $e->getMessage());
        echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
    }
}

// Helper function to format file size
function formatFileSize($bytes) {
    if ($bytes == 0) return '0 Bytes';
    
    $k = 1024;
    $sizes = ['Bytes', 'KB', 'MB', 'GB'];
    $i = floor(log($bytes) / log($k));
    
    return round($bytes / pow($k, $i), 2) . ' ' . $sizes[$i];
}

// Helper function to get file icon
function getFileIcon($extension) {
    $icons = [
        'pdf' => 'bi-file-pdf',
        'doc' => 'bi-file-word',
        'docx' => 'bi-file-word',
        'xls' => 'bi-file-excel',
        'xlsx' => 'bi-file-excel',
        'ppt' => 'bi-file-ppt',
        'pptx' => 'bi-file-ppt',
        'jpg' => 'bi-file-image',
        'jpeg' => 'bi-file-image',
        'png' => 'bi-file-image',
        'gif' => 'bi-file-image',
        'webp' => 'bi-file-image',
        'txt' => 'bi-file-text',
        'csv' => 'bi-file-text',
        'zip' => 'bi-file-zip',
        'rar' => 'bi-file-zip',
        'mp3' => 'bi-file-music',
        'mp4' => 'bi-file-play'
    ];
    
    return $icons[$extension] ?? 'bi-file-earmark';
}
?>