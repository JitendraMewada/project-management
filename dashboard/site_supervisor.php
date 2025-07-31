<?php
include '../includes/header.php';
require_once '../config/roles.php';
include '../includes/functions.php'; // Ensure helper functions like getStatusBadge() are here

// Ensure user is authenticated and has 'site_supervisor' role
if (!isset($current_user) || $current_user['role'] !== 'site_supervisor') {
    // Redirect user to their proper dashboard or login
    if (isset($current_user['role'])) {
        header("Location: ../dashboard/{$current_user['role']}.php");
    } else {
        header("Location: ../login.php");
    }
    exit();
}

$userId = $current_user['id'] ?? 0;
if (!$userId) {
    header("Location: ../login.php");
    exit();
}

// $db connection assumed available from header.php or globally configured

// Fetch tasks assigned to this supervisor
$tasksQuery = "
    SELECT t.*, p.name AS project_name, u.name AS assigned_by_name
    FROM tasks t
    LEFT JOIN projects p ON t.project_id = p.id
    LEFT JOIN users u ON t.assigned_by = u.id
    WHERE t.assigned_to = ?
    ORDER BY t.due_date ASC, t.priority DESC
";
$tasksStmt = $db->prepare($tasksQuery);
$tasksStmt->execute([$userId]);
$tasks = $tasksStmt->fetchAll(PDO::FETCH_ASSOC);

// Fetch projects involved
$projectsQuery = "
    SELECT p.*, 
           COUNT(t.id) AS total_tasks, 
           COUNT(CASE WHEN t.status = 'completed' THEN 1 END) AS completed_tasks
    FROM projects p
    LEFT JOIN tasks t ON p.id = t.project_id
    WHERE p.id IN (
        SELECT DISTINCT project_id FROM tasks WHERE assigned_to = ?
    )
    GROUP BY p.id
    ORDER BY p.created_at DESC
";
$projectsStmt = $db->prepare($projectsQuery);
$projectsStmt->execute([$userId]);
$projects = $projectsStmt->fetchAll(PDO::FETCH_ASSOC);

// Fetch today's schedule
$scheduleQuery = "
    SELECT s.*, p.name AS project_name
    FROM project_schedules s
    LEFT JOIN projects p ON s.project_id = p.id
    WHERE s.assigned_to = ? AND s.scheduled_date = CURDATE()
    ORDER BY s.start_time
";
$scheduleStmt = $db->prepare($scheduleQuery);
$scheduleStmt->execute([$userId]);
$todaySchedule = $scheduleStmt->fetchAll(PDO::FETCH_ASSOC);

// Fetch today's attendance record
$attendanceQuery = "SELECT * FROM attendance WHERE user_id = ? AND date = CURDATE()";
$attendanceStmt = $db->prepare($attendanceQuery);
$attendanceStmt->execute([$userId]);
$todayAttendance = $attendanceStmt->fetch(PDO::FETCH_ASSOC);

// Fetch recent inventory items
$inventoryQuery = "
    SELECT * FROM inventory_items
    WHERE project_id IN (
        SELECT DISTINCT project_id FROM tasks WHERE assigned_to = ?
    )
    ORDER BY created_at DESC
    LIMIT 5
";
$inventoryStmt = $db->prepare($inventoryQuery);
$inventoryStmt->execute([$userId]);
$inventoryItems = $inventoryStmt->fetchAll(PDO::FETCH_ASSOC);
?>

<div class="container-fluid">
    <div class="row">
        <div class="col-md-3">
            <?php include '../includes/sidebar.php'; ?>
        </div>
        <div class="col-md-9">
            <div class="main-content p-4">
                <!-- Welcome Header -->
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <div>
                        <h2>Welcome, <?= htmlspecialchars($current_user['name']) ?>! 👷‍♂️</h2>
                        <p class="text-muted mb-0">Supervising site operations and quality control</p>
                    </div>
                    <div class="btn-group">
                        <?php if (!$todayAttendance): ?>
                        <button class="btn btn-success" onclick="quickCheckIn()">
                            <i class="fas fa-sign-in-alt"></i> Check In
                        </button>
                        <?php elseif (!$todayAttendance['check_out_time']): ?>
                        <button class="btn btn-warning" onclick="quickCheckOut()">
                            <i class="fas fa-sign-out-alt"></i> Check Out
                        </button>
                        <?php endif; ?>
                        <a href="../modules/reports/create.php" class="btn btn-outline-info">
                            <i class="fas fa-file-alt"></i> Site Report
                        </a>
                    </div>
                </div>

                <!-- Statistics Cards -->
                <div class="row mb-4">
                    <!-- Active Tasks -->
                    <div class="col-xl-3 col-md-6">
                        <div class="card border-left-primary h-100">
                            <div class="card-body d-flex align-items-center justify-content-between">
                                <div>
                                    <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">Active Tasks
                                    </div>
                                    <div class="h5 mb-0 font-weight-bold text-gray-800">
                                        <?= count(array_filter($tasks, fn($t) => $t['status'] !== 'completed')) ?>
                                    </div>
                                </div>
                                <i class="fas fa-tasks fa-2x text-gray-300"></i>
                            </div>
                        </div>
                    </div>

                    <!-- Projects Involved -->
                    <div class="col-xl-3 col-md-6">
                        <div class="card border-left-success h-100">
                            <div class="card-body d-flex align-items-center justify-content-between">
                                <div>
                                    <div class="text-xs font-weight-bold text-success text-uppercase mb-1">Projects
                                        Involved</div>
                                    <div class="h5 mb-0 font-weight-bold text-gray-800"><?= count($projects) ?></div>
                                </div>
                                <i class="fas fa-project-diagram fa-2x text-gray-300"></i>
                            </div>
                        </div>
                    </div>

                    <!-- Today's Activities -->
                    <div class="col-xl-3 col-md-6">
                        <div class="card border-left-warning h-100">
                            <div class="card-body d-flex align-items-center justify-content-between">
                                <div>
                                    <div class="text-xs font-weight-bold text-warning text-uppercase mb-1">Today's
                                        Activities</div>
                                    <div class="h5 mb-0 font-weight-bold text-gray-800"><?= count($todaySchedule) ?>
                                    </div>
                                </div>
                                <i class="fas fa-calendar-day fa-2x text-gray-300"></i>
                            </div>
                        </div>
                    </div>

                    <!-- Hours Today -->
                    <div class="col-xl-3 col-md-6">
                        <div class="card border-left-info h-100">
                            <div class="card-body d-flex align-items-center justify-content-between">
                                <div>
                                    <div class="text-xs font-weight-bold text-info text-uppercase mb-1">Hours Today
                                    </div>
                                    <div class="h5 mb-0 font-weight-bold text-gray-800">
                                        <?php
                    if ($todayAttendance && $todayAttendance['total_hours']) {
                        echo htmlspecialchars($todayAttendance['total_hours']) . 'h';
                    } elseif ($todayAttendance && $todayAttendance['check_in_time'] && !$todayAttendance['check_out_time']) {
                        $hours = (time() - strtotime($todayAttendance['check_in_time'])) / 3600;
                        echo round($hours, 1) . 'h';
                    } else {
                        echo '0h';
                    }
                    ?>
                                    </div>
                                </div>
                                <i class="fas fa-clock fa-2x text-gray-300"></i>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Attendance Status -->
                <div class="card mb-4">
                    <div class="card-header">
                        <h6 class="m-0 font-weight-bold text-primary"><i class="fas fa-user-clock"></i> Today's
                            Attendance - <?= date('F d, Y') ?></h6>
                    </div>
                    <div class="card-body d-flex justify-content-between align-items-center">
                        <?php if ($todayAttendance): ?>
                        <div class="d-flex align-items-center">
                            <span
                                class="badge bg-<?= ($todayAttendance['status'] === 'present') ? 'success' : (($todayAttendance['status'] === 'late') ? 'warning' : 'danger') ?> fs-6 me-3">
                                <?= ucfirst($todayAttendance['status']) ?>
                            </span>
                            <div>
                                <p class="mb-1"><strong>Check In:</strong>
                                    <?= $todayAttendance['check_in_time'] ? date('H:i', strtotime($todayAttendance['check_in_time'])) : '-' ?>
                                </p>
                                <p class="mb-0"><strong>Check Out:</strong>
                                    <?= $todayAttendance['check_out_time'] ? date('H:i', strtotime($todayAttendance['check_out_time'])) : 'Active' ?>
                                </p>
                            </div>
                        </div>
                        <?php else: ?>
                        <div class="text-center w-100">
                            <p class="text-muted mb-2">You haven't checked in today</p>
                            <button class="btn btn-success" onclick="quickCheckIn()">
                                <i class="fas fa-sign-in-alt"></i> Check In Now
                            </button>
                        </div>
                        <?php endif; ?>
                        <div class="text-center">
                            <div class="h4 text-primary" id="currentTime"></div>
                            <p class="text-muted mb-0">Current Time</p>
                        </div>
                    </div>
                </div>

                <!-- My Supervision Tasks -->
                <div class="card mb-4">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h6 class="m-0 font-weight-bold text-primary"><i class="fas fa-tasks"></i> My Supervision Tasks
                        </h6>
                        <a href="../modules/tasks/list.php" class="btn btn-sm btn-primary">View All</a>
                    </div>
                    <div class="card-body">
                        <?php if (empty($tasks)): ?>
                        <div class="text-center py-4 text-muted">
                            <i class="fas fa-tasks fa-3x mb-3"></i>
                            <h6>No supervision tasks assigned</h6>
                            <p>You'll see your supervision tasks here once they're assigned.</p>
                        </div>
                        <?php else: ?>
                        <div class="task-list">
                            <?php foreach (array_slice($tasks, 0, 8) as $task): ?>
                            <div class="task-item d-flex align-items-center py-3 border-bottom">
                                <div class="task-priority me-3">
                                    <div class="priority-indicator priority-<?= htmlspecialchars($task['priority']) ?>">
                                    </div>
                                </div>
                                <div class="task-content flex-grow-1">
                                    <h6 class="mb-1"><?= htmlspecialchars($task['title']) ?></h6>
                                    <p class="mb-1 text-muted"><i class="fas fa-project-diagram"></i>
                                        <?= htmlspecialchars($task['project_name']) ?></p>
                                    <?php if ($task['due_date']): ?>
                                    <small
                                        class="text-<?= (strtotime($task['due_date']) < time() && $task['status'] !== 'completed') ? 'danger' : 'muted' ?>">
                                        <i class="fas fa-calendar"></i> Due:
                                        <?= date('M d, Y', strtotime($task['due_date'])) ?>
                                    </small>
                                    <?php endif; ?>
                                </div>
                                <div class="task-status me-3"><?= getStatusBadge($task['status'], 'task') ?></div>
                                <div class="task-actions">
                                    <div class="btn-group btn-group-sm">
                                        <a href="../modules/tasks/view.php?id=<?= (int)$task['id'] ?>"
                                            class="btn btn-outline-primary">View</a>
                                        <?php if ($task['status'] !== 'completed'): ?>
                                        <button onclick="updateTaskStatus(<?= (int)$task['id'] ?>, 'completed')"
                                            class="btn btn-outline-success" title="Mark Complete">
                                            <i class="fas fa-check"></i>
                                        </button>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Today's Schedule -->
                <div class="card mb-4">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h6 class="m-0 font-weight-bold text-primary"><i class="fas fa-calendar-day"></i> Today's
                            Schedule</h6>
                        <a href="../modules/schedule/index.php" class="btn btn-sm btn-primary">View All</a>
                    </div>
                    <div class="card-body">
                        <?php if (empty($todaySchedule)): ?>
                        <div class="text-center py-3 text-muted">
                            <i class="fas fa-calendar-day fa-2x mb-2"></i>
                            <p>No activities scheduled for today</p>
                        </div>
                        <?php else: ?>
                        <div class="schedule-timeline" style="max-height: 300px; overflow-y:auto;">
                            <?php foreach ($todaySchedule as $schedule): ?>
                            <div class="schedule-item d-flex align-items-center py-2 border-bottom">
                                <div class="schedule-time me-3">
                                    <strong><?= date('H:i', strtotime($schedule['start_time'])) ?></strong></div>
                                <div class="schedule-content flex-grow-1">
                                    <h6 class="mb-1"><?= htmlspecialchars($schedule['activity_title']) ?></h6>
                                    <small class="text-muted"><i class="fas fa-project-diagram"></i>
                                        <?= htmlspecialchars($schedule['project_name']) ?></small>
                                </div>
                                <div>
                                    <span class="badge bg-<?= match($schedule['status']) {
                        'completed' => 'success',
                        'in_progress' => 'warning',
                        'scheduled' => 'info',
                        default => 'secondary',
                      } ?>"><?= ucfirst(str_replace('_', ' ', $schedule['status'])) ?></span>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Recent Inventory -->
                <?php if (!empty($inventoryItems)): ?>
                <div class="card mb-4">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h6 class="m-0 font-weight-bold text-primary"><i class="fas fa-boxes"></i> Project Inventory
                        </h6>
                        <a href="../modules/inventory/list.php" class="btn btn-sm btn-primary">View All</a>
                    </div>
                    <div class="card-body">
                        <?php foreach (array_slice($inventoryItems, 0, 5) as $item): ?>
                        <div class="d-flex align-items-center py-2 border-bottom">
                            <div class="flex-grow-1">
                                <h6 class="mb-1"><?= htmlspecialchars($item['name']) ?></h6>
                                <small class="text-muted">Stock: <?= (int)$item['stock_quantity'] ?>
                                    <?= htmlspecialchars($item['unit']) ?></small>
                            </div>
                            <div>
                                <?php if ($item['stock_quantity'] <= $item['minimum_stock']): ?>
                                <span class="badge bg-danger">Low Stock</span>
                                <?php else: ?>
                                <span class="badge bg-success">Available</span>
                                <?php endif; ?>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
                <?php endif; ?>

                <!-- Quick Actions -->
                <div class="card">
                    <div class="card-header">
                        <h6 class="m-0 font-weight-bold text-primary"><i class="fas fa-bolt"></i> Quick Actions</h6>
                    </div>
                    <div class="card-body d-grid gap-2">
                        <a href="../modules/reports/create.php" class="btn btn-outline-primary">
                            <i class="fas fa-file-alt"></i> Site Report
                        </a>
                        <a href="../modules/documents/upload.php" class="btn btn-outline-success">
                            <i class="fas fa-camera"></i> Upload Photos
                        </a>
                        <a href="../modules/inventory/create.php" class="btn btn-outline-info">
                            <i class="fas fa-plus"></i> Add Inventory
                        </a>
                        <a href="../modules/calendar/index.php" class="btn btn-outline-warning">
                            <i class="fas fa-calendar"></i> View Calendar
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
.priority-indicator {
    width: 8px;
    height: 40px;
    border-radius: 4px;
}

.priority-critical {
    background-color: #dc3545;
}

.priority-high {
    background-color: #fd7e14;
}

.priority-medium {
    background-color: #ffc107;
}

.priority-low {
    background-color: #6c757d;
}

.schedule-timeline {
    max-height: 300px;
    overflow-y: auto;
}

.task-item:hover {
    background-color: #f8f9fa;
}
</style>

<script>
// Update current time display every second
function updateTime() {
    const now = new Date();
    document.getElementById('currentTime').textContent = now.toLocaleTimeString();
}

setInterval(updateTime, 1000);
updateTime();

function quickCheckIn() {
    fetch('../modules/attendance/quick_attendance.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({
                action: 'check_in'
            })
        })
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                alert('Checked in successfully!');
                location.reload();
            } else {
                alert('Error: ' + data.message);
            }
        });
}

function quickCheckOut() {
    fetch('../modules/attendance/quick_attendance.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({
                action: 'check_out'
            })
        })
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                alert('Checked out successfully!');
                location.reload();
            } else {
                alert('Error: ' + data.message);
            }
        });
}

function updateTaskStatus(taskId, status) {
    if (confirm(`Mark this task as ${status}?`)) {
        fetch('../modules/tasks/update_status.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({
                    task_id: taskId,
                    status: status
                }),
            })
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    location.reload();
                } else {
                    alert('Error updating task: ' + data.message);
                }
            });
    }
}
</script>

<?php include '../includes/footer.php'; ?>