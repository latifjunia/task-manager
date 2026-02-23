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

function getInitials($name) {
    $words = explode(" ", $name);
    $initials = "";
    foreach ($words as $w) {
        $initials .= mb_substr($w, 0, 1);
    }
    return strtoupper(substr($initials, 0, 2));
}

function getAvatarColor($name) {
    $colors = ['#4f46e5', '#ec4899', '#14b8a6', '#f59e0b', '#8b5cf6', '#10b981'];
    return $colors[strlen($name) % count($colors)];
}
?>

<div class="task-card priority-<?= $task['priority'] ?>" 
     data-task-id="<?= $task['id'] ?>"
     data-due-date="<?= $task['due_date'] ?>"
     onclick="showTaskDetail(<?= $task['id'] ?>)">
    
    <div class="d-flex justify-content-between align-items-start mb-2">
        <span class="priority-badge priority-<?= $task['priority'] ?>">
            <?php 
            $priorityLabels = [
                'low' => 'Low',
                'medium' => 'Medium',
                'high' => 'High',
                'urgent' => 'Urgent!'
            ];
            echo $priorityLabels[$task['priority']] ?? 'Medium';
            ?>
        </span>
        
        <?php if ($task['priority'] == 'urgent'): ?>
            <i class="bi bi-exclamation-triangle-fill text-danger"></i>
        <?php endif; ?>
    </div>
    
    <div class="task-title">
        <?= htmlspecialchars($task['title']) ?>
    </div>
    
    <?php if (!empty($task['description'])): ?>
        <div class="task-description">
            <?= strip_tags(htmlspecialchars_decode($task['description'])) ?>
        </div>
    <?php endif; ?>
    
    <div class="task-meta">
        <div class="d-flex align-items-center gap-2">
            <?php if ($task['assignee_name']): ?>
                <div class="avatar avatar-sm" 
                     style="background: <?= getAvatarColor($task['assignee_name']) ?>;"
                     title="<?= htmlspecialchars($task['assignee_name']) ?>">
                    <?= getInitials($task['assignee_name']) ?>
                </div>
                <small class="text-gray">
                    <?= explode(' ', $task['assignee_name'])[0] ?>
                </small>
            <?php else: ?>
                <div class="avatar avatar-sm bg-gray-soft text-gray" title="Belum ditugaskan">
                    <i class="bi bi-person"></i>
                </div>
                <small class="text-gray">Unassigned</small>
            <?php endif; ?>
        </div>
        
        <?php if ($task['due_date']): ?>
            <div class="due-date <?= $is_overdue ? 'overdue' : ($is_due_soon ? 'soon' : '') ?>">
                <i class="bi bi-calendar3"></i>
                <?= date('d/m', strtotime($task['due_date'])) ?>
            </div>
        <?php endif; ?>
    </div>
    
    <?php if ($is_overdue): ?>
        <span class="position-absolute top-0 end-0 badge bg-danger m-2" style="font-size: 0.65rem;">
            Terlambat
        </span>
    <?php elseif ($is_due_soon): ?>
        <span class="position-absolute top-0 end-0 badge bg-warning m-2" style="font-size: 0.65rem;">
            Segera
        </span>
    <?php endif; ?>
</div>