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
    global $pdo;
    $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
    $stmt->execute([$id]);
    return $stmt->fetch();
}

function getAllUsers($exclude_id = null) {
    global $pdo;
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
    global $pdo;
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
    global $pdo;
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
    global $pdo;
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
    global $pdo;
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
    global $pdo;
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
    global $pdo;
    if (isAdmin()) return true;
    $stmt = $pdo->prepare("SELECT 1 FROM project_members WHERE project_id = ? AND user_id = ?");
    $stmt->execute([$project_id, $user_id]);
    return $stmt->rowCount() > 0;
}

// ============ TASK FUNCTIONS ============
function getTasksByStatus($project_id, $status) {
    global $pdo;
    $stmt = $pdo->prepare("
        SELECT t.*, 
               u.full_name as assignee_name,
               creator.full_name as creator_name
        FROM tasks t
        LEFT JOIN users u ON t.assignee_id = u.id
        LEFT JOIN users creator ON t.created_by = creator.id
        WHERE t.project_id = ? AND t.column_status = ? AND t.column_id IS NULL
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
    global $pdo;
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
    global $pdo;
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

function getTaskAttachments($task_id) {
    global $pdo;
    $stmt = $pdo->prepare("
        SELECT a.*, u.full_name as uploaded_by_name 
        FROM attachments a
        LEFT JOIN users u ON a.uploaded_by = u.id
        WHERE a.task_id = ?
        ORDER BY a.uploaded_at DESC
    ");
    $stmt->execute([$task_id]);
    return $stmt->fetchAll();
}

// ============ PROJECT STATS ============
function getProjectStats($project_id) {
    global $pdo;
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
    global $pdo;
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
    global $pdo;
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
    global $pdo;
    $stmt = $pdo->prepare("
        SELECT * FROM notifications 
        WHERE user_id = ? 
        ORDER BY created_at DESC 
        LIMIT ?
    ");
    $stmt->execute([$user_id, $limit]);
    return $stmt->fetchAll();
}

function getUnreadNotifications($user_id, $limit = 5) {
    global $pdo;
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
    global $pdo;
    $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM notifications WHERE user_id = ? AND is_read = 0");
    $stmt->execute([$user_id]);
    return $stmt->fetch()['count'];
}

function createNotification($user_id, $title, $message, $type = 'system') {
    global $pdo;
    $stmt = $pdo->prepare("INSERT INTO notifications (user_id, title, message, type) VALUES (?, ?, ?, ?)");
    return $stmt->execute([$user_id, $title, $message, $type]);
}

function markNotificationAsRead($id, $user_id) {
    global $pdo;
    $stmt = $pdo->prepare("UPDATE notifications SET is_read = 1 WHERE id = ? AND user_id = ?");
    return $stmt->execute([$id, $user_id]);
}

function markAllNotificationsAsRead($user_id) {
    global $pdo;
    $stmt = $pdo->prepare("UPDATE notifications SET is_read = 1 WHERE user_id = ? AND is_read = 0");
    return $stmt->execute([$user_id]);
}

// ============ OVERDUE FUNCTIONS ============
function getOverdueTasks($user_id = null, $project_id = null, $limit = null) {
    global $pdo;
    
    $sql = "
        SELECT t.*, 
               p.name as project_name,
               p.id as project_id,
               u.full_name as assignee_name,
               creator.full_name as creator_name,
               DATEDIFF(CURDATE(), t.due_date) as days_overdue
        FROM tasks t
        JOIN projects p ON t.project_id = p.id
        LEFT JOIN users u ON t.assignee_id = u.id
        LEFT JOIN users creator ON t.created_by = creator.id
        WHERE t.due_date < CURDATE() 
        AND t.column_status != 'done'
    ";
    
    $params = [];
    
    if ($user_id !== null && !isAdmin()) {
        $sql .= " AND (
            t.assignee_id = ? 
            OR t.created_by = ? 
            OR EXISTS (
                SELECT 1 FROM project_members pm 
                WHERE pm.project_id = t.project_id 
                AND pm.user_id = ?
            )
        )";
        $params[] = $user_id;
        $params[] = $user_id;
        $params[] = $user_id;
    }
    
    if ($project_id !== null) {
        $sql .= " AND t.project_id = ?";
        $params[] = $project_id;
    }
    
    $sql .= " ORDER BY 
                CASE t.priority
                    WHEN 'urgent' THEN 1
                    WHEN 'high' THEN 2
                    WHEN 'medium' THEN 3
                    WHEN 'low' THEN 4
                END,
                t.due_date ASC";
    
    if ($limit !== null) {
        $sql .= " LIMIT ?";
        $params[] = $limit;
    }
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    return $stmt->fetchAll();
}

function getOverdueTasksCount($user_id = null, $project_id = null) {
    global $pdo;
    
    $sql = "
        SELECT COUNT(*) as total
        FROM tasks t
        WHERE t.due_date < CURDATE() 
        AND t.column_status != 'done'
    ";
    
    $params = [];
    
    if ($user_id !== null && !isAdmin()) {
        $sql .= " AND (
            t.assignee_id = ? 
            OR t.created_by = ? 
            OR EXISTS (
                SELECT 1 FROM project_members pm 
                WHERE pm.project_id = t.project_id 
                AND pm.user_id = ?
            )
        )";
        $params[] = $user_id;
        $params[] = $user_id;
        $params[] = $user_id;
    }
    
    if ($project_id !== null) {
        $sql .= " AND t.project_id = ?";
        $params[] = $project_id;
    }
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $result = $stmt->fetch();
    return $result['total'];
}

function getOverdueStatistics($user_id = null) {
    global $pdo;
    
    $stats = [
        'total_overdue' => 0,
        'by_priority' => ['low' => 0, 'medium' => 0, 'high' => 0, 'urgent' => 0],
        'by_project' => [],
        'oldest_overdue' => null,
        'most_overdue' => null
    ];
    
    $overdue_tasks = getOverdueTasks($user_id);
    $stats['total_overdue'] = count($overdue_tasks);
    
    foreach ($overdue_tasks as $task) {
        $stats['by_priority'][$task['priority']]++;
        
        if (!isset($stats['by_project'][$task['project_id']])) {
            $stats['by_project'][$task['project_id']] = [
                'project_name' => $task['project_name'],
                'count' => 0
            ];
        }
        $stats['by_project'][$task['project_id']]['count']++;
        
        if ($stats['oldest_overdue'] === null || $task['days_overdue'] > $stats['oldest_overdue']['days']) {
            $stats['oldest_overdue'] = [
                'title' => $task['title'],
                'days' => $task['days_overdue'],
                'project' => $task['project_name']
            ];
        }
    }
    
    usort($stats['by_project'], function($a, $b) {
        return $b['count'] - $a['count'];
    });
    
    return $stats;
}

function getRecentActivities($user_id, $limit = 10) {
    global $pdo;
    
    $sql = "
        (SELECT 
            'notification' as type,
            title,
            message,
            created_at,
            NULL as task_id,
            NULL as project_id
        FROM notifications 
        WHERE user_id = ?
        ORDER BY created_at DESC
        LIMIT ?)
        
        UNION ALL
        
        (SELECT 
            'task' as type,
            CONCAT('Tugas: ', title) as title,
            CASE 
                WHEN column_status = 'done' THEN 'Tugas selesai'
                WHEN column_status = 'in_progress' THEN 'Tugas dimulai'
                ELSE 'Tugas diperbarui'
            END as message,
            updated_at as created_at,
            id as task_id,
            project_id
        FROM tasks 
        WHERE assignee_id = ? OR created_by = ?
        ORDER BY updated_at DESC
        LIMIT ?)
        
        ORDER BY created_at DESC
        LIMIT ?
    ";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$user_id, $limit, $user_id, $user_id, $limit, $limit]);
    return $stmt->fetchAll();
}

// ============ DEFAULT COLUMN SETTINGS FUNCTIONS ============

function getDefaultColumnSettings($project_id) {
    global $pdo;
    
    $stmt = $pdo->prepare("SELECT * FROM default_column_settings WHERE project_id = ?");
    $stmt->execute([$project_id]);
    $settings = [];
    while ($row = $stmt->fetch()) {
        $settings[$row['column_name']] = $row;
    }
    return $settings;
}

// ============ CUSTOM COLUMN FUNCTIONS ============

function getProjectColumns($project_id) {
    global $pdo;
    
    // Default columns dengan data awal
    $default_columns = [
        ['id' => 'todo', 'title' => 'To Do', 'icon' => 'bi-circle', 'color' => '#64748b', 'is_default' => true, 'position' => 0],
        ['id' => 'in_progress', 'title' => 'In Progress', 'icon' => 'bi-arrow-repeat', 'color' => '#6366f1', 'is_default' => true, 'position' => 1],
        ['id' => 'review', 'title' => 'Review', 'icon' => 'bi-eye', 'color' => '#f59e0b', 'is_default' => true, 'position' => 2],
        ['id' => 'done', 'title' => 'Done', 'icon' => 'bi-check2-circle', 'color' => '#10b981', 'is_default' => true, 'position' => 3]
    ];
    
    // Ambil custom settings untuk default columns
    $default_settings = getDefaultColumnSettings($project_id);
    
    // Apply custom settings
    foreach ($default_columns as &$col) {
        if (isset($default_settings[$col['id']])) {
            $setting = $default_settings[$col['id']];
            $col['title'] = $setting['custom_title'] ?? $col['title'];
            $col['color'] = $setting['custom_color'] ?? $col['color'];
            $col['icon'] = $setting['custom_icon'] ?? $col['icon'];
            $col['is_customized'] = true;
        } else {
            $col['is_customized'] = false;
        }
    }
    
    // Kolom kustom dari database
    $stmt = $pdo->prepare("
        SELECT * FROM project_columns 
        WHERE project_id = ? 
        ORDER BY position ASC, created_at ASC
    ");
    $stmt->execute([$project_id]);
    $custom_columns = $stmt->fetchAll();
    
    $formatted_custom = [];
    foreach ($custom_columns as $col) {
        $formatted_custom[] = [
            'id' => 'custom_' . $col['id'],
            'original_id' => $col['id'],
            'title' => $col['title'],
            'icon' => $col['icon'],
            'color' => $col['color'],
            'is_default' => false,
            'is_customized' => true,
            'position' => $col['position'],
            'db_id' => $col['id']
        ];
    }
    
    $all_columns = array_merge($default_columns, $formatted_custom);
    usort($all_columns, function($a, $b) {
        return $a['position'] - $b['position'];
    });
    
    return $all_columns;
}

function getTasksByColumn($project_id, $column) {
    global $pdo;
    
    if (in_array($column, ['todo', 'in_progress', 'review', 'done'])) {
        $stmt = $pdo->prepare("
            SELECT t.*, 
                   u.full_name as assignee_name,
                   creator.full_name as creator_name
            FROM tasks t
            LEFT JOIN users u ON t.assignee_id = u.id
            LEFT JOIN users creator ON t.created_by = creator.id
            WHERE t.project_id = ? AND t.column_status = ? AND t.column_id IS NULL
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
        $stmt->execute([$project_id, $column]);
    } else {
        $column_id = str_replace('custom_', '', $column);
        $stmt = $pdo->prepare("
            SELECT t.*, 
                   u.full_name as assignee_name,
                   creator.full_name as creator_name
            FROM tasks t
            LEFT JOIN users u ON t.assignee_id = u.id
            LEFT JOIN users creator ON t.created_by = creator.id
            WHERE t.project_id = ? AND t.column_id = ?
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
        $stmt->execute([$project_id, $column_id]);
    }
    
    return $stmt->fetchAll();
}

function createCustomColumn($project_id, $title, $color = '#64748b', $icon = 'bi-circle', $position = null) {
    global $pdo;
    
    if ($position === null) {
        $stmt = $pdo->prepare("SELECT MAX(position) as max_pos FROM project_columns WHERE project_id = ?");
        $stmt->execute([$project_id]);
        $result = $stmt->fetch();
        $position = ($result['max_pos'] ?? 3) + 1;
    }
    
    $stmt = $pdo->prepare("
        INSERT INTO project_columns (project_id, title, color, icon, position, created_by) 
        VALUES (?, ?, ?, ?, ?, ?)
    ");
    
    $success = $stmt->execute([$project_id, $title, $color, $icon, $position, $_SESSION['user_id']]);
    
    if ($success) {
        return $pdo->lastInsertId();
    }
    
    return false;
}

function updateCustomColumn($column_id, $data) {
    global $pdo;
    
    $sets = [];
    $params = [];
    
    if (isset($data['title'])) {
        $sets[] = "title = ?";
        $params[] = $data['title'];
    }
    
    if (isset($data['color'])) {
        $sets[] = "color = ?";
        $params[] = $data['color'];
    }
    
    if (isset($data['icon'])) {
        $sets[] = "icon = ?";
        $params[] = $data['icon'];
    }
    
    if (isset($data['position'])) {
        $sets[] = "position = ?";
        $params[] = $data['position'];
    }
    
    if (empty($sets)) {
        return false;
    }
    
    $params[] = $column_id;
    $sql = "UPDATE project_columns SET " . implode(', ', $sets) . " WHERE id = ?";
    
    $stmt = $pdo->prepare($sql);
    return $stmt->execute($params);
}

function deleteCustomColumn($column_id, $project_id) {
    global $pdo;
    
    try {
        $pdo->beginTransaction();
        
        $stmt = $pdo->prepare("
            UPDATE tasks 
            SET column_id = NULL, column_status = 'todo' 
            WHERE column_id = ?
        ");
        $stmt->execute([$column_id]);
        
        $stmt = $pdo->prepare("DELETE FROM project_columns WHERE id = ?");
        $success = $stmt->execute([$column_id]);
        
        $pdo->commit();
        return $success;
        
    } catch (Exception $e) {
        $pdo->rollBack();
        error_log("Error deleting column: " . $e->getMessage());
        return false;
    }
}

function updateColumnPositions($project_id, $positions) {
    global $pdo;
    
    try {
        $pdo->beginTransaction();
        
        foreach ($positions as $item) {
            if (strpos($item['id'], 'custom_') === 0) {
                $column_id = str_replace('custom_', '', $item['id']);
                $stmt = $pdo->prepare("UPDATE project_columns SET position = ? WHERE id = ? AND project_id = ?");
                $stmt->execute([$item['position'], $column_id, $project_id]);
            }
        }
        
        $pdo->commit();
        return true;
        
    } catch (Exception $e) {
        $pdo->rollBack();
        error_log("Error updating column positions: " . $e->getMessage());
        return false;
    }
}

function moveTaskToColumn($task_id, $target_column, $project_id) {
    global $pdo;
    
    try {
        if (in_array($target_column, ['todo', 'in_progress', 'review', 'done'])) {
            $stmt = $pdo->prepare("
                UPDATE tasks 
                SET column_status = ?, column_id = NULL 
                WHERE id = ? AND project_id = ?
            ");
            return $stmt->execute([$target_column, $task_id, $project_id]);
        } else {
            $column_id = str_replace('custom_', '', $target_column);
            $stmt = $pdo->prepare("
                UPDATE tasks 
                SET column_id = ?, column_status = NULL 
                WHERE id = ? AND project_id = ?
            ");
            return $stmt->execute([$column_id, $task_id, $project_id]);
        }
    } catch (Exception $e) {
        error_log("Error moving task: " . $e->getMessage());
        return false;
    }
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

function getPriorityText($priority) {
    $texts = ['low' => 'Rendah', 'medium' => 'Sedang', 'high' => 'Tinggi', 'urgent' => 'Mendesak'];
    return $texts[$priority] ?? 'Sedang';
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
    global $pdo;
    
    $attachments = getTaskAttachments($id);
    foreach ($attachments as $attachment) {
        $filepath = __DIR__ . '/../uploads/tasks/' . $attachment['filepath'];
        if (file_exists($filepath)) {
            unlink($filepath);
        }
    }
    
    $stmt = $pdo->prepare("DELETE FROM tasks WHERE id = ?");
    return $stmt->execute([$id]);
}

function formatFileSize($bytes) {
    if ($bytes == 0) return '0 Bytes';
    $k = 1024;
    $sizes = ['Bytes', 'KB', 'MB', 'GB'];
    $i = floor(log($bytes) / log($k));
    return round($bytes / pow($k, $i), 2) . ' ' . $sizes[$i];
}

function getInitials($name) {
    if (empty($name)) return '?';
    $words = explode(' ', $name);
    $initials = '';
    foreach ($words as $word) {
        if (!empty($word)) {
            $initials .= strtoupper($word[0]);
        }
    }
    return substr($initials, 0, 2);
}

function getAvatarColor($name) {
    $colors = ['#6366f1', '#ec4899', '#14b8a6', '#f59e0b', '#8b5cf6', '#10b981'];
    return $colors[strlen($name) % count($colors)];
}

function getPriorityClass($priority) {
    $classes = [
        'low' => 'priority-low',
        'medium' => 'priority-medium',
        'high' => 'priority-high',
        'urgent' => 'priority-urgent'
    ];
    return $classes[$priority] ?? 'priority-medium';
}

function getPriorityLabel($priority) {
    $labels = [
        'low' => 'Low',
        'medium' => 'Medium',
        'high' => 'High',
        'urgent' => 'Urgent!'
    ];
    return $labels[$priority] ?? 'Medium';
}
?>