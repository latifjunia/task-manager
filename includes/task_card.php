<?php
if (!isset($task)) return;

// CEK OVERDUE
$is_overdue = false;
$is_due_soon = false;
if ($task['due_date'] && $task['column_status'] != 'done') {
    $today = new DateTime();
    $due_date = new DateTime($task['due_date']);
    $diff = $today->diff($due_date);
    
    if ($today > $due_date) {
        $is_overdue = true;
    } elseif ($diff->days <= 2) {
        $is_due_soon = true;
    }
}

// PRIORITY BADGE
$priority_colors = [
    'low' => 'success',
    'medium' => 'warning',
    'high' => 'danger',
    'urgent' => 'danger'
];

$priority_texts = [
    'low' => 'R',
    'medium' => 'S',
    'high' => 'T',
    'urgent' => '!'
];

// STATUS BADGE
$status_colors = [
    'todo' => 'warning',
    'in_progress' => 'info',
    'review' => 'primary',
    'done' => 'success'
];

$status_texts = [
    'todo' => 'To Do',
    'in_progress' => 'In Progress',
    'review' => 'Review',
    'done' => 'Done'
];
?>

<div class="task-card priority-<?php echo $task['priority']; ?>" 
     data-task-id="<?php echo $task['id']; ?>"
     data-due-date="<?php echo $task['due_date']; ?>"
     onclick="showTaskDetail(<?php echo $task['id']; ?>)">
    
    <!-- JUDUL TUGAS -->
    <div class="d-flex justify-content-between align-items-start mb-2">
        <h6 class="task-title">
            <?php echo htmlspecialchars($task['title']); ?>
        </h6>
    </div>
    
    <!-- DESKRIPSI SINGKAT -->
    <?php if (!empty($task['description'])): ?>
        <p class="task-description">
            <?php echo htmlspecialchars(substr($task['description'], 0, 80)); ?>
            <?php if (strlen($task['description']) > 80): ?>...<?php endif; ?>
        </p>
    <?php endif; ?>
    
    <!-- ASSIGNEE & DUE DATE -->
    <div class="d-flex justify-content-between align-items-center mb-2">
        <div class="d-flex align-items-center">
            <div class="bg-light rounded-circle d-flex align-items-center justify-content-center me-1" 
                 style="width: 22px; height: 22px;">
                <i class="bi bi-person text-muted" style="font-size: 0.8rem;"></i>
            </div>
            <small class="text-muted">
                <?php echo $task['assignee_fullname'] ? htmlspecialchars(explode(' ', $task['assignee_fullname'])[0]) : 'Belum ditugaskan'; ?>
            </small>
        </div>
        
        <?php if ($task['due_date']): ?>
            <small class="<?php echo $is_overdue ? 'text-danger fw-bold' : ($is_due_soon ? 'text-warning' : 'text-muted'); ?>">
                <i class="bi bi-calendar3"></i>
                <?php echo date('d/m', strtotime($task['due_date'])); ?>
            </small>
        <?php endif; ?>
    </div>
    
    <!-- BADGE STATUS & PRIORITAS -->
    <div class="d-flex justify-content-between align-items-center">
        <span class="badge bg-<?php echo $status_colors[$task['column_status']] ?? 'secondary'; ?>">
            <?php echo $status_texts[$task['column_status']] ?? $task['column_status']; ?>
        </span>
        
        <span class="badge bg-<?php echo $priority_colors[$task['priority']] ?? 'secondary'; ?>">
            <?php echo $priority_texts[$task['priority']] ?? $task['priority']; ?>
        </span>
    </div>
</div>