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
    case 'get':
        getNotificationsHandler();
        break;
    case 'mark_read':
        markNotificationReadHandler();
        break;
    case 'mark_all_read':
        markAllNotificationsReadHandler();
        break;
    case 'delete':
        deleteNotificationHandler();
        break;
    case 'check_updates':
        checkForUpdatesHandler();
        break;
    default:
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'Invalid action']);
}

function getNotificationsHandler() {
    global $pdo;
    
    $user_id = $_SESSION['user_id'];
    $limit = $_GET['limit'] ?? 20;
    $unread_only = isset($_GET['unread_only']) ? (bool)$_GET['unread_only'] : false;
    
    $query = "SELECT * FROM notifications WHERE user_id = ?";
    $params = [$user_id];
    
    if ($unread_only) {
        $query .= " AND is_read = 0";
    }
    
    $query .= " ORDER BY created_at DESC";
    
    if ($limit > 0) {
        $query .= " LIMIT ?";
        $params[] = $limit;
    }
    
    $stmt = $pdo->prepare($query);
    $stmt->execute($params);
    $notifications = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Format dates for display
    foreach ($notifications as &$notification) {
        $notification['time_ago'] = timeAgo($notification['created_at']);
        $notification['formatted_date'] = date('d M Y H:i', strtotime($notification['created_at']));
    }
    
    header('Content-Type: application/json');
    echo json_encode([
        'success' => true,
        'notifications' => $notifications,
        'total_unread' => count(getNotifications($user_id))
    ]);
}

function markNotificationReadHandler() {
    global $pdo;
    
    header('Content-Type: application/json');
    
    $notification_id = $_POST['notification_id'] ?? 0;
    
    if (empty($notification_id)) {
        echo json_encode(['success' => false, 'message' => 'Notification ID wajib diisi']);
        return;
    }
    
    // Verify ownership
    $stmt = $pdo->prepare("SELECT id FROM notifications WHERE id = ? AND user_id = ?");
    $stmt->execute([$notification_id, $_SESSION['user_id']]);
    
    if ($stmt->rowCount() == 0) {
        echo json_encode(['success' => false, 'message' => 'Notifikasi tidak ditemukan']);
        return;
    }
    
    $stmt = $pdo->prepare("UPDATE notifications SET is_read = 1 WHERE id = ?");
    $success = $stmt->execute([$notification_id]);
    
    if ($success) {
        echo json_encode(['success' => true, 'message' => 'Notifikasi ditandai sebagai telah dibaca']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Gagal memperbarui notifikasi']);
    }
}

function markAllNotificationsReadHandler() {
    global $pdo;
    
    header('Content-Type: application/json');
    
    $stmt = $pdo->prepare("UPDATE notifications SET is_read = 1 WHERE user_id = ? AND is_read = 0");
    $success = $stmt->execute([$_SESSION['user_id']]);
    
    if ($success) {
        echo json_encode(['success' => true, 'message' => 'Semua notifikasi ditandai sebagai telah dibaca']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Gagal memperbarui notifikasi']);
    }
}

function deleteNotificationHandler() {
    global $pdo;
    
    header('Content-Type: application/json');
    
    $notification_id = $_POST['notification_id'] ?? 0;
    
    if (empty($notification_id)) {
        echo json_encode(['success' => false, 'message' => 'Notification ID wajib diisi']);
        return;
    }
    
    // Verify ownership
    $stmt = $pdo->prepare("SELECT id FROM notifications WHERE id = ? AND user_id = ?");
    $stmt->execute([$notification_id, $_SESSION['user_id']]);
    
    if ($stmt->rowCount() == 0) {
        echo json_encode(['success' => false, 'message' => 'Notifikasi tidak ditemukan']);
        return;
    }
    
    $stmt = $pdo->prepare("DELETE FROM notifications WHERE id = ?");
    $success = $stmt->execute([$notification_id]);
    
    if ($success) {
        echo json_encode(['success' => true, 'message' => 'Notifikasi berhasil dihapus']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Gagal menghapus notifikasi']);
    }
}

function checkForUpdatesHandler() {
    global $pdo;
    
    header('Content-Type: application/json');
    
    $user_id = $_SESSION['user_id'];
    
    // Check for unread notifications
    $stmt = $pdo->prepare("SELECT COUNT(*) as unread_count FROM notifications WHERE user_id = ? AND is_read = 0");
    $stmt->execute([$user_id]);
    $result = $stmt->fetch();
    
    // Check for new tasks assigned in last 5 minutes
    $five_minutes_ago = date('Y-m-d H:i:s', strtotime('-5 minutes'));
    $stmt = $pdo->prepare("
        SELECT COUNT(*) as new_tasks 
        FROM tasks 
        WHERE assignee_id = ? 
        AND created_at > ?
    ");
    $stmt->execute([$user_id, $five_minutes_ago]);
    $new_tasks = $stmt->fetch();
    
    $has_updates = ($result['unread_count'] > 0) || ($new_tasks['new_tasks'] > 0);
    
    echo json_encode([
        'success' => true,
        'has_updates' => $has_updates,
        'unread_notifications' => $result['unread_count'],
        'new_tasks' => $new_tasks['new_tasks']
    ]);
}

// Helper function to display time ago
function timeAgo($datetime) {
    $time = strtotime($datetime);
    $now = time();
    $diff = $now - $time;
    
    if ($diff < 60) {
        return 'baru saja';
    } elseif ($diff < 3600) {
        $minutes = floor($diff / 60);
        return $minutes . ' menit yang lalu';
    } elseif ($diff < 86400) {
        $hours = floor($diff / 3600);
        return $hours . ' jam yang lalu';
    } elseif ($diff < 604800) {
        $days = floor($diff / 86400);
        return $days . ' hari yang lalu';
    } else {
        return date('d M Y', $time);
    }
}
?>