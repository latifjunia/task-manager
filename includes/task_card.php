<?php
if (!isset($task)) return;

$is_overdue = false;
$is_due_soon = false;
if ($task['due_date'] && $task['column_status'] != 'done') {
    $due = strtotime($task['due_date']);
    $now = time();
    $diff = $due - $now;
    $days = floor($diff / 86400);
    
    if ($diff < 0) $is_overdue = true;
    elseif ($days <= 2) $is_due_soon = true;
}
?>

<div class="task-card priority-<?= $task['priority'] ?>" 
     data-task-id="<?= $task['id'] ?>"
     data-due-date="<?= $task['due_date'] ?>"
     onclick="showTaskDetail(<?= $task['id'] ?>)">
    
    <div class="d-flex justify-content-between align-items-start mb-2">
        <h6 class="fw-bold mb-0" style="font-size: 0.95rem;">
            <?= htmlspecialchars($task['title']) ?>
        </h6>
        <?php if ($task['priority'] == 'urgent'): ?>
            <span class="badge bg-danger ms-2">!</span>
        <?php endif; ?>
    </div>
    
    <?php if (!empty($task['description'])): ?>
        <p class="small text-muted mb-2" style="display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden;">
            <?= htmlspecialchars(substr($task['description'], 0, 80)) ?>...
        </p>
    <?php endif; ?>
    
    <div class="d-flex justify-content-between align-items-center">
        <div class="d-flex align-items-center">
            <div class="bg-light rounded-circle d-flex align-items-center justify-content-center me-1" 
                 style="width: 24px; height: 24px;">
                <i class="bi bi-person text-muted small"></i>
            </div>
            <small class="text-muted">
                <?= $task['assignee_name'] ? htmlspecialchars(explode(' ', $task['assignee_name'])[0]) : 'Unassigned' ?>
            </small>
        </div>
        
        <?php if ($task['due_date']): ?>
            <small class="<?= $is_overdue ? 'text-danger fw-bold' : ($is_due_soon ? 'text-warning' : 'text-muted') ?>">
                <i class="bi bi-calendar3"></i>
                <?= date('d/m', strtotime($task['due_date'])) ?>
            </small>
        <?php endif; ?>
    </div>
    
    <?php if ($is_overdue): ?>
        <span class="position-absolute top-0 end-0 badge bg-danger m-2" style="font-size: 0.7rem;">Terlambat</span>
    <?php elseif ($is_due_soon): ?>
        <span class="position-absolute top-0 end-0 badge bg-warning m-2" style="font-size: 0.7rem;">Segera</span>
    <?php endif; ?>
</div>