<?php
// ==========================================
// API OVERDUE - Daftar Tugas Terlambat
// ==========================================
ini_set('display_errors', 0);
header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/functions.php';

if (!isLoggedIn()) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$action = $_GET['action'] ?? '';

switch ($action) {
    case 'list':
        handleGetOverdueList();
        break;
    case 'count':
        handleGetOverdueCount();
        break;
    default:
        echo json_encode(['success' => false, 'message' => 'Invalid action']);
}

function handleGetOverdueList() {
    global $pdo;
    
    try {
        $user_id = $_SESSION['user_id'];
        $project_id = isset($_GET['project_id']) ? (int)$_GET['project_id'] : null;
        $limit = isset($_GET['limit']) ? (int)$_GET['limit'] : null;
        
        // Query untuk mengambil tugas terlambat
        $sql = "
            SELECT 
                t.id,
                t.title,
                t.description,
                t.due_date,
                t.priority,
                t.column_status,
                p.id as project_id,
                p.name as project_name,
                u.full_name as assignee_name,
                DATEDIFF(CURDATE(), t.due_date) as days_overdue
            FROM tasks t
            JOIN projects p ON t.project_id = p.id
            LEFT JOIN users u ON t.assignee_id = u.id
            WHERE t.due_date < CURDATE() 
            AND t.column_status != 'done'
        ";
        
        $params = [];
        
        // Filter berdasarkan akses user (bukan admin)
        if (!isAdmin()) {
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
        
        // Filter berdasarkan proyek
        if ($project_id) {
            $sql .= " AND t.project_id = ?";
            $params[] = $project_id;
        }
        
        // Urutkan berdasarkan prioritas dan lama keterlambatan
        $sql .= " ORDER BY 
                    CASE t.priority
                        WHEN 'urgent' THEN 1
                        WHEN 'high' THEN 2
                        WHEN 'medium' THEN 3
                        WHEN 'low' THEN 4
                    END,
                    days_overdue DESC";
        
        if ($limit) {
            $sql .= " LIMIT ?";
            $params[] = $limit;
        }
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        $tasks = $stmt->fetchAll();
        
        // Format data untuk frontend
        foreach ($tasks as &$task) {
            $task['due_date_formatted'] = date('d M Y', strtotime($task['due_date']));
            $task['priority_label'] = ucfirst($task['priority']);
            $task['priority_class'] = 'priority-' . $task['priority'];
            $task['can_edit'] = ($task['assignee_id'] == $user_id || isProjectAdmin($task['project_id'], $user_id));
        }
        
        echo json_encode([
            'success' => true,
            'data' => $tasks,
            'total' => count($tasks)
        ]);
        
    } catch (Exception $e) {
        error_log("Error handleGetOverdueList: " . $e->getMessage());
        echo json_encode(['success' => false, 'message' => 'Terjadi kesalahan']);
    }
}

function handleGetOverdueCount() {
    global $pdo;
    
    try {
        $user_id = $_SESSION['user_id'];
        
        $sql = "
            SELECT COUNT(*) as total
            FROM tasks t
            WHERE t.due_date < CURDATE() 
            AND t.column_status != 'done'
        ";
        
        $params = [];
        
        if (!isAdmin()) {
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
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        $result = $stmt->fetch();
        
        echo json_encode([
            'success' => true,
            'count' => (int)$result['total']
        ]);
        
    } catch (Exception $e) {
        error_log("Error handleGetOverdueCount: " . $e->getMessage());
        echo json_encode(['success' => false, 'count' => 0]);
    }
}
?>