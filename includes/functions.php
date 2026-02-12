<?php
require_once 'config.php';

// ============ FUNGSI CEK LOGIN & ROLE ============

function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

function isAdmin() {
    return isset($_SESSION['role']) && $_SESSION['role'] === 'admin';
}

// ============ FUNGSI GET DATA ============

// GET USER BY ID
function getUserById($user_id) {
    global $pdo;
    try {
        $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
        $stmt->execute([$user_id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        return null;
    }
}

// GET PROJECT BY ID
function getProjectById($project_id) {
    global $pdo;
    try {
        $stmt = $pdo->prepare("
            SELECT p.*, u.full_name as creator_name 
            FROM projects p
            LEFT JOIN users u ON p.created_by = u.id
            WHERE p.id = ?
        ");
        $stmt->execute([$project_id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        return null;
    }
}

// GET ALL PROJECTS (UNTUK ADMIN)
function getAllProjects() {
    global $pdo;
    try {
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
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        return [];
    }
}

// GET USER PROJECTS (UNTUK ANGGOTA)
function getUserProjects($user_id) {
    global $pdo;
    try {
        $stmt = $pdo->prepare("
            SELECT p.*, pm.role 
            FROM projects p
            JOIN project_members pm ON p.id = pm.project_id
            WHERE pm.user_id = ?
            ORDER BY p.created_at DESC
        ");
        $stmt->execute([$user_id]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        return [];
    }
}

// GET PROJECT MEMBERS
function getProjectMembers($project_id) {
    global $pdo;
    try {
        $stmt = $pdo->prepare("
            SELECT u.id, u.username, u.full_name, u.email, u.profile_picture, pm.role, pm.joined_at
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
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        return [];
    }
}

// CEK AKSES PROJECT
function hasProjectAccess($project_id, $user_id) {
    global $pdo;
    try {
        // ADMIN GLOBAL BISA SEMUA
        if (isAdmin()) {
            return true;
        }
        
        // CEK APAKAH ANGGOTA PROJECT
        $stmt = $pdo->prepare("SELECT 1 FROM project_members WHERE project_id = ? AND user_id = ?");
        $stmt->execute([$project_id, $user_id]);
        return $stmt->rowCount() > 0;
    } catch (PDOException $e) {
        return false;
    }
}

// CEK AKSES TASK
function hasTaskAccess($task_id, $user_id) {
    global $pdo;
    try {
        // ADMIN GLOBAL BISA SEMUA
        if (isAdmin()) {
            return true;
        }
        
        // CEK VIA PROJECT MEMBERSHIP
        $stmt = $pdo->prepare("
            SELECT 1 
            FROM tasks t
            JOIN project_members pm ON t.project_id = pm.project_id
            WHERE t.id = ? AND pm.user_id = ?
        ");
        $stmt->execute([$task_id, $user_id]);
        return $stmt->rowCount() > 0;
    } catch (PDOException $e) {
        return false;
    }
}

// GET USER ROLE DI PROJECT
function getUserProjectRole($project_id, $user_id) {
    global $pdo;
    try {
        $stmt = $pdo->prepare("SELECT role FROM project_members WHERE project_id = ? AND user_id = ?");
        $stmt->execute([$project_id, $user_id]);
        $member = $stmt->fetch();
        return $member ? $member['role'] : null;
    } catch (PDOException $e) {
        return null;
    }
}

// CEK APAKAH ADMIN PROYEK
function isProjectAdmin($project_id, $user_id) {
    $role = getUserProjectRole($project_id, $user_id);
    return ($role == 'owner' || $role == 'admin');
}

// CEK APAKAH OWNER PROYEK
function isProjectOwner($project_id, $user_id) {
    $role = getUserProjectRole($project_id, $user_id);
    return ($role == 'owner');
}

// GET TASKS BY STATUS
function getTasksByStatus($project_id, $status) {
    global $pdo;
    try {
        $stmt = $pdo->prepare("
            SELECT t.*, 
                   u.full_name as assignee_fullname,
                   u.username as assignee_username,
                   creator.full_name as creator_fullname
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
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        return [];
    }
}

// GET TASK BY ID
function getTaskById($task_id) {
    global $pdo;
    try {
        $stmt = $pdo->prepare("
            SELECT t.*, 
                   u1.full_name as assignee_name,
                   u1.username as assignee_username,
                   u2.full_name as creator_name,
                   u2.username as creator_username,
                   p.name as project_name,
                   p.id as project_id
            FROM tasks t
            LEFT JOIN users u1 ON t.assignee_id = u1.id
            LEFT JOIN users u2 ON t.created_by = u2.id
            LEFT JOIN projects p ON t.project_id = p.id
            WHERE t.id = ?
        ");
        $stmt->execute([$task_id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        return null;
    }
}

// GET TASK COMMENTS
function getTaskComments($task_id) {
    global $pdo;
    try {
        $stmt = $pdo->prepare("
            SELECT c.*, u.username, u.full_name, u.profile_picture
            FROM comments c
            JOIN users u ON c.user_id = u.id
            WHERE c.task_id = ?
            ORDER BY c.created_at ASC
        ");
        $stmt->execute([$task_id]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        return [];
    }
}

// GET TASK ATTACHMENTS
function getTaskAttachments($task_id) {
    global $pdo;
    try {
        $stmt = $pdo->prepare("
            SELECT a.*, u.username, u.full_name 
            FROM attachments a
            LEFT JOIN users u ON a.uploaded_by = u.id
            WHERE a.task_id = ?
            ORDER BY a.uploaded_at DESC
        ");
        $stmt->execute([$task_id]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        return [];
    }
}

// GET PROJECT STATS
function getProjectStats($project_id) {
    global $pdo;
    $stats = [];
    try {
        // TOTAL TASKS
        $stmt = $pdo->prepare("SELECT COUNT(*) as total FROM tasks WHERE project_id = ?");
        $stmt->execute([$project_id]);
        $stats['total'] = $stmt->fetch()['total'] ?? 0;
        
        // TASKS BY STATUS
        $statuses = ['todo', 'in_progress', 'review', 'done'];
        foreach ($statuses as $status) {
            $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM tasks WHERE project_id = ? AND column_status = ?");
            $stmt->execute([$project_id, $status]);
            $stats[$status] = $stmt->fetch()['count'] ?? 0;
        }
        
        // OVERDUE
        $stmt = $pdo->prepare("
            SELECT COUNT(*) as overdue 
            FROM tasks 
            WHERE project_id = ? 
            AND due_date < CURDATE() 
            AND column_status != 'done'
        ");
        $stmt->execute([$project_id]);
        $stats['overdue'] = $stmt->fetch()['overdue'] ?? 0;
        
        // PROGRESS
        $stats['progress'] = $stats['total'] > 0 ? round(($stats['done'] / $stats['total']) * 100) : 0;
        
        return $stats;
    } catch (PDOException $e) {
        return [
            'total' => 0, 'todo' => 0, 'in_progress' => 0, 
            'review' => 0, 'done' => 0, 'overdue' => 0, 'progress' => 0
        ];
    }
}

// GET USER STATISTICS
function getUserStatistics($user_id) {
    global $pdo;
    $stats = [];
    try {
        // TOTAL PROJECTS
        $stmt = $pdo->prepare("SELECT COUNT(DISTINCT project_id) as total FROM project_members WHERE user_id = ?");
        $stmt->execute([$user_id]);
        $stats['total_projects'] = $stmt->fetch()['total'] ?? 0;
        
        // ACTIVE TASKS
        $stmt = $pdo->prepare("
            SELECT COUNT(*) as total FROM tasks 
            WHERE assignee_id = ? AND column_status != 'done'
        ");
        $stmt->execute([$user_id]);
        $stats['active_tasks'] = $stmt->fetch()['total'] ?? 0;
        
        // COMPLETED TASKS
        $stmt = $pdo->prepare("
            SELECT COUNT(*) as total FROM tasks 
            WHERE assignee_id = ? AND column_status = 'done'
        ");
        $stmt->execute([$user_id]);
        $stats['completed_tasks'] = $stmt->fetch()['total'] ?? 0;
        
        // UPCOMING DEADLINES
        $stmt = $pdo->prepare("
            SELECT COUNT(*) as total FROM tasks 
            WHERE assignee_id = ? 
            AND due_date >= CURDATE() 
            AND due_date <= DATE_ADD(CURDATE(), INTERVAL 7 DAY)
            AND column_status != 'done'
        ");
        $stmt->execute([$user_id]);
        $stats['upcoming_deadlines'] = $stmt->fetch()['total'] ?? 0;
        
        return $stats;
    } catch (PDOException $e) {
        return [
            'total_projects' => 0, 'active_tasks' => 0, 
            'completed_tasks' => 0, 'upcoming_deadlines' => 0
        ];
    }
}

// GET UPCOMING DEADLINES
function getUpcomingDeadlines($user_id, $limit = 5) {
    global $pdo;
    try {
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
        $stmt->bindValue(1, $user_id, PDO::PARAM_INT);
        $stmt->bindValue(2, $limit, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        return [];
    }
}

// GET RECENT ACTIVITIES
function getRecentActivities($user_id, $limit = 10) {
    global $pdo;
    try {
        $stmt = $pdo->prepare("
            SELECT id, title, message, created_at, 'notification' as type
            FROM notifications 
            WHERE user_id = ? 
            ORDER BY created_at DESC
            LIMIT ?
        ");
        $stmt->bindValue(1, $user_id, PDO::PARAM_INT);
        $stmt->bindValue(2, $limit, PDO::PARAM_INT);
        $stmt->execute();
        
        $activities = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        foreach ($activities as &$activity) {
            $activity['time_ago'] = timeAgo($activity['created_at']);
        }
        
        return $activities;
    } catch (PDOException $e) {
        return [];
    }
}

// GET NOTIFICATIONS
function getNotifications($user_id, $limit = 5) {
    global $pdo;
    try {
        $stmt = $pdo->prepare("
            SELECT * FROM notifications 
            WHERE user_id = ? AND is_read = 0 
            ORDER BY created_at DESC 
            LIMIT ?
        ");
        $stmt->bindValue(1, $user_id, PDO::PARAM_INT);
        $stmt->bindValue(2, $limit, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        return [];
    }
}

// GET NOTIFICATION COUNT
function getNotificationCount($user_id) {
    global $pdo;
    try {
        $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM notifications WHERE user_id = ? AND is_read = 0");
        $stmt->execute([$user_id]);
        $result = $stmt->fetch();
        return $result['count'] ?? 0;
    } catch (PDOException $e) {
        return 0;
    }
}

// CREATE NOTIFICATION
function createNotification($user_id, $title, $message, $type = 'system') {
    global $pdo;
    try {
        $stmt = $pdo->prepare("INSERT INTO notifications (user_id, title, message, type) VALUES (?, ?, ?, ?)");
        return $stmt->execute([$user_id, $title, $message, $type]);
    } catch (PDOException $e) {
        error_log("Error createNotification: " . $e->getMessage());
        return false;
    }
}

// GET ALL USERS (UNTUK DITAMBAH KE PROYEK)
function getAllUsers($exclude_id = null) {
    global $pdo;
    try {
        if ($exclude_id) {
            $stmt = $pdo->prepare("
                SELECT id, username, email, full_name, profile_picture 
                FROM users 
                WHERE id != ?
                ORDER BY full_name
            ");
            $stmt->execute([$exclude_id]);
        } else {
            $stmt = $pdo->prepare("
                SELECT id, username, email, full_name, profile_picture 
                FROM users 
                ORDER BY full_name
            ");
            $stmt->execute();
        }
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        return [];
    }
}

// DELETE TASK
function deleteTask($task_id) {
    global $pdo;
    try {
        $pdo->beginTransaction();
        
        // HAPUS ATTACHMENTS
        $stmt = $pdo->prepare("SELECT filepath FROM attachments WHERE task_id = ?");
        $stmt->execute([$task_id]);
        $attachments = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        foreach ($attachments as $attachment) {
            $filepath = '../' . $attachment['filepath'];
            if (file_exists($filepath)) {
                unlink($filepath);
            }
        }
        
        // HAPUS TASK
        $stmt = $pdo->prepare("DELETE FROM tasks WHERE id = ?");
        $success = $stmt->execute([$task_id]);
        
        if ($success) {
            $pdo->commit();
            return true;
        } else {
            $pdo->rollBack();
            return false;
        }
    } catch (PDOException $e) {
        $pdo->rollBack();
        error_log("Error deleteTask: " . $e->getMessage());
        return false;
    }
}

// TIME AGO
function timeAgo($datetime) {
    if (empty($datetime)) return 'Tidak diketahui';
    $time = strtotime($datetime);
    if ($time === false) return 'Format waktu salah';
    $now = time();
    $diff = $now - $time;
    
    if ($diff < 60) return 'baru saja';
    if ($diff < 3600) return floor($diff / 60) . ' menit yang lalu';
    if ($diff < 86400) return floor($diff / 3600) . ' jam yang lalu';
    if ($diff < 604800) return floor($diff / 86400) . ' hari yang lalu';
    return date('d M Y', $time);
}

// GET PRIORITY COLOR
function getPriorityColor($priority) {
    $colors = ['low' => 'success', 'medium' => 'warning', 'high' => 'danger', 'urgent' => 'danger'];
    return $colors[$priority] ?? 'secondary';
}

// GET STATUS COLOR
function getStatusColor($status) {
    $colors = ['todo' => 'warning', 'in_progress' => 'info', 'review' => 'primary', 'done' => 'success'];
    return $colors[$status] ?? 'secondary';
}

// GET STATUS TEXT
function getStatusText($status) {
    $texts = ['todo' => 'To Do', 'in_progress' => 'In Progress', 'review' => 'Review', 'done' => 'Done'];
    return $texts[$status] ?? $status;
}

// GET PRIORITY TEXT
function getPriorityText($priority) {
    $texts = ['low' => 'Rendah', 'medium' => 'Sedang', 'high' => 'Tinggi', 'urgent' => 'Mendesak'];
    return $texts[$priority] ?? $priority;
}
?>