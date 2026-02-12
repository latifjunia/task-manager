<?php
require_once '../includes/config.php';
require_once '../includes/functions.php';

if (!isLoggedIn()) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$action = $_GET['action'] ?? '';

switch ($action) {
    case 'add':
        addComment();
        break;
    default:
        echo json_encode(['success' => false, 'message' => 'Invalid action']);
}

function addComment() {
    global $pdo;
    
    $task_id = (int)($_POST['task_id'] ?? 0);
    $content = trim($_POST['content'] ?? '');
    
    if ($task_id <= 0 || empty($content)) {
        echo json_encode(['success' => false, 'message' => 'Data tidak valid']);
        return;
    }
    
    $task = getTaskById($task_id);
    if (!$task || !hasProjectAccess($task['project_id'], $_SESSION['user_id'])) {
        echo json_encode(['success' => false, 'message' => 'Tidak memiliki akses']);
        return;
    }
    
    $stmt = $pdo->prepare("INSERT INTO comments (task_id, user_id, content) VALUES (?, ?, ?)");
    $success = $stmt->execute([$task_id, $_SESSION['user_id'], $content]);
    
    if ($success) {
        // Notify task creator and assignee
        $users_to_notify = [];
        if ($task['created_by'] != $_SESSION['user_id']) $users_to_notify[] = $task['created_by'];
        if ($task['assignee_id'] && $task['assignee_id'] != $_SESSION['user_id']) $users_to_notify[] = $task['assignee_id'];
        
        foreach (array_unique($users_to_notify) as $user_id) {
            createNotification(
                $user_id,
                'Komentar Baru',
                $_SESSION['full_name'] . ' mengomentari: ' . $task['title'],
                'comment'
            );
        }
        
        echo json_encode(['success' => true, 'message' => 'Komentar berhasil ditambahkan']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Gagal menambahkan komentar']);
    }
}
?>