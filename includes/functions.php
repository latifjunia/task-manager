<?php
require_once 'config.php';

// ============ AUTH FUNCTIONS ============
function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

function isAdmin() {
    return isset($_SESSION['role']) && $_SESSION['role'] === 'admin';
}

// ============ USER FUNCTIONS ============
function getUserById($id) {
    global $pdo;  // ✅ WAJIB ADA DI DALAM FUNGSI
    $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
    $stmt->execute([$id]);
    return $stmt->fetch();
}

function getAllUsers($exclude_id = null) {
    global $pdo;  // ✅ WAJIB ADA DI DALAM FUNGSI
    if ($exclude_id) {
        $stmt = $pdo->prepare("SELECT id, username, full_name FROM users WHERE id != ? ORDER BY full_name");
        $stmt->execute([$exclude_id]);
    } else {
        $stmt = $pdo->prepare("SELECT id, username, full_name FROM users ORDER BY full_name");
        $stmt->execute();
    }
    return $stmt->fetchAll();
}

// ============ PROJECT FUNCTIONS ============
function getProjectById($id) {
    global $pdo;  // ✅ WAJIB ADA DI DALAM FUNGSI
    $stmt = $pdo->prepare("
        SELECT p.*, u.full_name as creator_name 
        FROM projects p
        LEFT JOIN users u ON p.created_by = u.id
        WHERE p.id = ?
    ");
    $stmt->execute([$id]);
    return $stmt->fetch();
}

function getAllProjects() {
    global $pdo;  // ✅ WAJIB ADA DI DALAM FUNGSI
    $stmt = $pdo->prepare("
        SELECT p.*, 
               u.full_name as creator_name,
               (SELECT COUNT(*) FROM project_members WHERE project_id = p.id) as total_members,
               (SELECT COUNT(*) FROM tasks WHERE project_id = p.id) as total_tasks
        FROM projects p
        LEFT JOIN users u ON p.created_by = u.id
        ORDER BY p.created_at DESC
    ");
    $stmt->execute();
    return $stmt->fetchAll();
}

function getUserProjects($user_id) {
    global $pdo;  // ✅ WAJIB ADA DI DALAM FUNGSI
    $stmt = $pdo->prepare("
        SELECT p.*, pm.role 
        FROM projects p
        JOIN project_members pm ON p.id = pm.project_id
        WHERE pm.user_id = ?
        ORDER BY p.created_at DESC
    ");
    $stmt->execute([$user_id]);
    return $stmt->fetchAll();
}

// ============ PROJECT MEMBERS FUNCTIONS ============
function getProjectMembers($project_id) {
    global $pdo;  // ✅ WAJIB ADA DI DALAM FUNGSI
    $stmt = $pdo->prepare("
        SELECT u.id, u.username, u.full_name, pm.role, pm.joined_at
        FROM users u
        JOIN project_members pm ON u.id = pm.user_id
        WHERE pm.project_id = ?
        ORDER BY 
            CASE pm.role 
                WHEN 'owner' THEN 1
                WHEN 'admin' THEN 2
                ELSE 3
            END,
            u.full_name
    ");
    $stmt->execute([$project_id]);
    return $stmt->fetchAll();
}

function getUserProjectRole($project_id, $user_id) {
    global $pdo;  // ✅ WAJIB ADA DI DALAM FUNGSI
    $stmt = $pdo->prepare("SELECT role FROM project_members WHERE project_id = ? AND user_id = ?");
    $stmt->execute([$project_id, $user_id]);
    $member = $stmt->fetch();
    return $member ? $member['role'] : null;
}

function isProjectAdmin($project_id, $user_id) {
    $role = getUserProjectRole($project_id, $user_id);
    return ($role == 'owner' || $role == 'admin' || isAdmin());
}

function isProjectOwner($project_id, $user_id) {
    $role = getUserProjectRole($project_id, $user_id);
    return ($role == 'owner' || isAdmin());
}

function hasProjectAccess($project_id, $user_id) {
    global $pdo;  // ✅ WAJIB ADA DI DALAM FUNGSI
    if (isAdmin()) return true;
    $stmt = $pdo->prepare("SELECT 1 FROM project_members WHERE project_id = ? AND user_id = ?");
    $stmt->execute([$project_id, $user_id]);
    return $stmt->rowCount() > 0;
}

// ============ TASK FUNCTIONS ============
function getTasksByStatus($project_id, $status) {
    global $pdo;  // ✅ WAJIB ADA DI DALAM FUNGSI
    $stmt = $pdo->prepare("
        SELECT t.*, 
               u.full_name as assignee_name,
               creator.full_name as creator_name
        FROM tasks t
        LEFT JOIN users u ON t.assignee_id = u.id
        LEFT JOIN users creator ON t.created_by = creator.id
        WHERE t.project_id = ? AND t.column_status = ?
        ORDER BY 
            CASE t.priority
                WHEN 'urgent' THEN 1
                WHEN 'high' THEN 2
                WHEN 'medium' THEN 3
                WHEN 'low' THEN 4
            END,
            t.due_date ASC,
            t.created_at DESC
    ");
    $stmt->execute([$project_id, $status]);
    return $stmt->fetchAll();
}

function getTaskById($id) {
    global $pdo;  // ✅ WAJIB ADA DI DALAM FUNGSI
    $stmt = $pdo->prepare("
        SELECT t.*, 
               u1.full_name as assignee_name,
               u2.full_name as creator_name,
               p.name as project_name
        FROM tasks t
        LEFT JOIN users u1 ON t.assignee_id = u1.id
        LEFT JOIN users u2 ON t.created_by = u2.id
        LEFT JOIN projects p ON t.project_id = p.id
        WHERE t.id = ?
    ");
    $stmt->execute([$id]);
    return $stmt->fetch();
}

function getTaskComments($task_id) {
    global $pdo;  // ✅ WAJIB ADA DI DALAM FUNGSI
    $stmt = $pdo->prepare("
        SELECT c.*, u.full_name, u.username
        FROM comments c
        JOIN users u ON c.user_id = u.id
        WHERE c.task_id = ?
        ORDER BY c.created_at ASC
    ");
    $stmt->execute([$task_id]);
    return $stmt->fetchAll();
}

function addComment($task_id, $user_id, $content) {
    global $pdo;  // ✅ WAJIB ADA DI DALAM FUNGSI
    $stmt = $pdo->prepare("INSERT INTO comments (task_id, user_id, content) VALUES (?, ?, ?)");
    return $stmt->execute([$task_id, $user_id, $content]);
}

function deleteComment($id, $user_id) {
    global $pdo;  // ✅ WAJIB ADA DI DALAM FUNGSI
    $stmt = $pdo->prepare("DELETE FROM comments WHERE id = ? AND user_id = ?");
    return $stmt->execute([$id, $user_id]);
}

// ============ PROJECT STATS ============
function getProjectStats($project_id) {
    global $pdo;  // ✅ WAJIB ADA DI DALAM FUNGSI
    $stats = [];
    
    $stmt = $pdo->prepare("SELECT COUNT(*) as total FROM tasks WHERE project_id = ?");
    $stmt->execute([$project_id]);
    $stats['total'] = $stmt->fetch()['total'];
    
    $statuses = ['todo', 'in_progress', 'review', 'done'];
    foreach ($statuses as $status) {
        $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM tasks WHERE project_id = ? AND column_status = ?");
        $stmt->execute([$project_id, $status]);
        $stats[$status] = $stmt->fetch()['count'];
    }
    
    $stmt = $pdo->prepare("
        SELECT COUNT(*) as overdue 
        FROM tasks 
        WHERE project_id = ? 
        AND due_date < CURDATE() 
        AND column_status != 'done'
    ");
    $stmt->execute([$project_id]);
    $stats['overdue'] = $stmt->fetch()['overdue'];
    
    $stats['progress'] = $stats['total'] > 0 ? round(($stats['done'] / $stats['total']) * 100) : 0;
    
    return $stats;
}

function getUserStatistics($user_id) {
    global $pdo;  // ✅ WAJIB ADA DI DALAM FUNGSI
    $stats = [];
    
    $stmt = $pdo->prepare("SELECT COUNT(DISTINCT project_id) as total FROM project_members WHERE user_id = ?");
    $stmt->execute([$user_id]);
    $stats['total_projects'] = $stmt->fetch()['total'];
    
    $stmt = $pdo->prepare("SELECT COUNT(*) as total FROM tasks WHERE assignee_id = ? AND column_status != 'done'");
    $stmt->execute([$user_id]);
    $stats['active_tasks'] = $stmt->fetch()['total'];
    
    $stmt = $pdo->prepare("SELECT COUNT(*) as total FROM tasks WHERE assignee_id = ? AND column_status = 'done'");
    $stmt->execute([$user_id]);
    $stats['completed_tasks'] = $stmt->fetch()['total'];
    
    $stmt = $pdo->prepare("
        SELECT COUNT(*) as total FROM tasks 
        WHERE assignee_id = ? 
        AND due_date BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 7 DAY)
        AND column_status != 'done'
    ");
    $stmt->execute([$user_id]);
    $stats['upcoming_deadlines'] = $stmt->fetch()['total'];
    
    return $stats;
}

function getUpcomingDeadlines($user_id, $limit = 5) {
    global $pdo;  // ✅ WAJIB ADA DI DALAM FUNGSI
    $stmt = $pdo->prepare("
        SELECT t.*, p.name as project_name 
        FROM tasks t
        JOIN projects p ON t.project_id = p.id
        WHERE t.assignee_id = ? 
        AND t.due_date >= CURDATE() 
        AND t.column_status != 'done'
        ORDER BY t.due_date ASC
        LIMIT ?
    ");
    $stmt->execute([$user_id, $limit]);
    return $stmt->fetchAll();
}

// ============ NOTIFICATION FUNCTIONS ============
function getNotifications($user_id, $limit = 5) {
    global $pdo;  // ✅ WAJIB ADA DI DALAM FUNGSI
    $stmt = $pdo->prepare("
        SELECT * FROM notifications 
        WHERE user_id = ? AND is_read = 0 
        ORDER BY created_at DESC 
        LIMIT ?
    ");
    $stmt->execute([$user_id, $limit]);
    return $stmt->fetchAll();
}

function getNotificationCount($user_id) {
    global $pdo;  // ✅ WAJIB ADA DI DALAM FUNGSI
    $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM notifications WHERE user_id = ? AND is_read = 0");
    $stmt->execute([$user_id]);
    return $stmt->fetch()['count'];
}

function createNotification($user_id, $title, $message, $type = 'system') {
    global $pdo;  // ✅ WAJIB ADA DI DALAM FUNGSI
    $stmt = $pdo->prepare("INSERT INTO notifications (user_id, title, message, type) VALUES (?, ?, ?, ?)");
    return $stmt->execute([$user_id, $title, $message, $type]);
}

function markNotificationAsRead($id, $user_id) {
    global $pdo;  // ✅ WAJIB ADA DI DALAM FUNGSI
    $stmt = $pdo->prepare("UPDATE notifications SET is_read = 1 WHERE id = ? AND user_id = ?");
    return $stmt->execute([$id, $user_id]);
}

// ============ UTILITY FUNCTIONS ============
function timeAgo($datetime) {
    $time = strtotime($datetime);
    $now = time();
    $diff = $now - $time;
    
    if ($diff < 60) return 'baru saja';
    if ($diff < 3600) return floor($diff / 60) . ' menit lalu';
    if ($diff < 86400) return floor($diff / 3600) . ' jam lalu';
    if ($diff < 604800) return floor($diff / 86400) . ' hari lalu';
    return date('d M Y', $time);
}

function getPriorityBadge($priority) {
    $badges = [
        'low' => '<span class="badge bg-success">Rendah</span>',
        'medium' => '<span class="badge bg-warning text-dark">Sedang</span>',
        'high' => '<span class="badge bg-danger">Tinggi</span>',
        'urgent' => '<span class="badge bg-danger">Mendesak</span>'
    ];
    return $badges[$priority] ?? '<span class="badge bg-secondary">Unknown</span>';
}

function getPriorityColor($priority) {
    $colors = ['low' => 'success', 'medium' => 'warning', 'high' => 'danger', 'urgent' => 'danger'];
    return $colors[$priority] ?? 'secondary';
}

function getStatusBadge($status) {
    $badges = [
        'todo' => '<span class="badge bg-secondary">To Do</span>',
        'in_progress' => '<span class="badge bg-primary">In Progress</span>',
        'review' => '<span class="badge bg-info">Review</span>',
        'done' => '<span class="badge bg-success">Done</span>'
    ];
    return $badges[$status] ?? '<span class="badge bg-secondary">Unknown</span>';
}

function getStatusColor($status) {
    $colors = ['todo' => 'secondary', 'in_progress' => 'primary', 'review' => 'info', 'done' => 'success'];
    return $colors[$status] ?? 'secondary';
}

function deleteTask($id) {
    global $pdo;  // ✅ WAJIB ADA DI DALAM FUNGSI
    $stmt = $pdo->prepare("DELETE FROM tasks WHERE id = ?");
    return $stmt->execute([$id]);
}
?>