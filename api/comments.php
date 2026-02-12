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
    case 'add':
        addCommentHandler();
        break;
    case 'get':
        getCommentsHandler();
        break;
    case 'delete':
        deleteCommentHandler();
        break;
    default:
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'Invalid action']);
}

function addCommentHandler() {
    global $pdo;
    
    header('Content-Type: application/json');
    
    try {
        $task_id = intval($_POST['task_id'] ?? 0);
        $content = trim($_POST['content'] ?? '');
        
        if ($task_id <= 0) {
            echo json_encode(['success' => false, 'message' => 'Task ID tidak valid']);
            return;
        }
        
        if (empty($content)) {
            echo json_encode(['success' => false, 'message' => 'Konten komentar wajib diisi']);
            return;
        }
        
        // Check access to task
        if (!hasTaskAccess($task_id, $_SESSION['user_id'])) {
            echo json_encode(['success' => false, 'message' => 'Tidak memiliki akses ke tugas ini']);
            return;
        }
        
        // Get task info
        $task = getTaskById($task_id);
        if (!$task) {
            echo json_encode(['success' => false, 'message' => 'Tugas tidak ditemukan']);
            return;
        }
        
        // Insert comment
        $stmt = $pdo->prepare("
            INSERT INTO comments (task_id, user_id, content) 
            VALUES (?, ?, ?)
        ");
        
        $success = $stmt->execute([
            $task_id, 
            $_SESSION['user_id'], 
            $content
        ]);
        
        if ($success) {
            $comment_id = $pdo->lastInsertId();
            
            // Create notifications for task creator and assignee (if different from commenter)
            $users_to_notify = [];
            
            // Notify task creator if different from commenter
            if ($task['created_by'] != $_SESSION['user_id']) {
                $users_to_notify[] = $task['created_by'];
            }
            
            // Notify assignee if different from commenter
            if ($task['assignee_id'] && $task['assignee_id'] != $_SESSION['user_id']) {
                $users_to_notify[] = $task['assignee_id'];
            }
            
            // Remove duplicates
            $users_to_notify = array_unique($users_to_notify);
            
            // Send notifications
            foreach ($users_to_notify as $user_id) {
                createNotification(
                    $user_id,
                    'Komentar Baru',
                    $_SESSION['full_name'] . ' mengomentari tugas: ' . $task['title'],
                    'comment'
                );
            }
            
            echo json_encode([
                'success' => true, 
                'message' => 'Komentar berhasil ditambahkan',
                'comment_id' => $comment_id
            ]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Gagal menambahkan komentar']);
        }
        
    } catch (Exception $e) {
        error_log("Error addCommentHandler: " . $e->getMessage());
        echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
    }
}

function getCommentsHandler() {
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
        
        $comments = getTaskComments($task_id);
        
        echo json_encode([
            'success' => true,
            'comments' => $comments,
            'total' => count($comments)
        ]);
        
    } catch (Exception $e) {
        error_log("Error getCommentsHandler: " . $e->getMessage());
        echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
    }
}

function deleteCommentHandler() {
    global $pdo;
    
    header('Content-Type: application/json');
    
    try {
        $comment_id = intval($_POST['comment_id'] ?? 0);
        
        if ($comment_id <= 0) {
            echo json_encode(['success' => false, 'message' => 'Comment ID tidak valid']);
            return;
        }
        
        // Get comment info
        $stmt = $pdo->prepare("SELECT user_id, task_id FROM comments WHERE id = ?");
        $stmt->execute([$comment_id]);
        $comment = $stmt->fetch();
        
        if (!$comment) {
            echo json_encode(['success' => false, 'message' => 'Komentar tidak ditemukan']);
            return;
        }
        
        // Check permission
        $can_delete = false;
        
        // Comment owner can delete
        if ($comment['user_id'] == $_SESSION['user_id']) {
            $can_delete = true;
        }
        // Admin can delete
        elseif (isAdmin()) {
            $can_delete = true;
        }
        // Task creator can delete
        else {
            $task = getTaskById($comment['task_id']);
            if ($task && $task['created_by'] == $_SESSION['user_id']) {
                $can_delete = true;
            }
        }
        
        if (!$can_delete) {
            echo json_encode(['success' => false, 'message' => 'Tidak diizinkan menghapus komentar ini']);
            return;
        }
        
        // Delete comment
        $stmt = $pdo->prepare("DELETE FROM comments WHERE id = ?");
        $success = $stmt->execute([$comment_id]);
        
        if ($success) {
            echo json_encode(['success' => true, 'message' => 'Komentar berhasil dihapus']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Gagal menghapus komentar']);
        }
        
    } catch (Exception $e) {
        error_log("Error deleteCommentHandler: " . $e->getMessage());
        echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
    }
}
?>