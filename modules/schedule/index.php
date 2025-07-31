<?php 
include '../../includes/header.php';
require_once '../../config/roles.php';

// Check permission
if (!hasPermission($current_user['role'], 'schedule', 'read')) {
    header("Location: ../../dashboard/" . $current_user['role'] . ".php");
    exit();
}

// Fetch scheduled activities
$schedule_query = "SELECT s.*, p.name as project_name, t.title as task_title, u.name as assigned_to_name
                   FROM project_schedules s
                   LEFT JOIN projects p ON s.project_id = p.id
                   LEFT JOIN tasks t ON s.task_id = t.id
                   LEFT JOIN users u ON s.assigned_to = u.id
                   WHERE s.scheduled_date >= CURDATE()
                   ORDER BY s.scheduled_date, s.start_time";
$schedule_stmt = $db->prepare($schedule_query);
$schedule_stmt->execute();
$schedules = $schedule_stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<div class="container-fluid">
    <div class="row">
        <div class="col-md-3">
            <?php include '../../includes/sidebar.php'; ?>
        </div>
        <div class="col-md-9">
            <div class="main-content p-4">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h2><i class="fas fa-calendar-check"></i> Project Schedule</h2>
                    <div class="btn-group">
                        <?php if (hasPermission($current_user['role'], 'schedule', 'create')): ?>
                        <a href="create.php" class="btn btn-primary">
                            <i class="fas fa-plus"></i> Add Schedule
                        </a>
                        <?php endif; ?>
                        <a href="calendar.php" class="btn btn-outline-info">
                            <i class="fas fa-calendar-alt"></i> Calendar View
                        </a>
                        <a href="resources.php" class="btn btn-outline-success">
                            <i class="fas fa-users"></i> Resource Planning
                        </a>
                    </div>
                </div>

                <!-- Today's Schedule -->
                <div class="card mb-4">
                    <div class="card-header">
                        <h6 class="mb-0"><i class="fas fa-clock"></i> Today's Schedule - <?php echo date('F d, Y'); ?>
                        </h6>
                    </div>
                    <div class="card-body">
                        <?php 
                        $today_schedules = array_filter($schedules, function($s) {
                            return date('Y-m-d', strtotime($s['scheduled_date'])) === date('Y-m-d');
                        });
                        ?>

                        <?php if (empty($today_schedules)): ?>
                        <p class="text-muted text-center">No activities scheduled for today</p>
                        <?php else: ?>
                        <div class="timeline">
                            <?php foreach ($today_schedules as $schedule): ?>
                            <div class="timeline-item">
                                <div class="timeline-time">
                                    <?php echo date('H:i', strtotime($schedule['start_time'])); ?>
                                </div>
                                <div class="timeline-content">
                                    <h6><?php echo htmlspecialchars($schedule['activity_title']); ?></h6>
                                    <p class="mb-1"><?php echo htmlspecialchars($schedule['project_name']); ?></p>
                                    <small class="text-muted">
                                        <i class="fas fa-user"></i>
                                        <?php echo htmlspecialchars($schedule['assigned_to_name']); ?>
                                        <i class="fas fa-map-marker-alt ms-2"></i>
                                        <?php echo htmlspecialchars($schedule['location']); ?>
                                    </small>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Weekly Schedule -->
                <div class="card">
                    <div class="card-header">
                        <h6 class="mb-0"><i class="fas fa-calendar-week"></i> This Week's Schedule</h6>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>Date</th>
                                        <th>Time</th>
                                        <th>Activity</th>
                                        <th>Project</th>
                                        <th>Assigned To</th>
                                        <th>Location</th>
                                        <th>Status</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($schedules as $schedule): ?>
                                    <tr>
                                        <td><?php echo date('M d', strtotime($schedule['scheduled_date'])); ?></td>
                                        <td>
                                            <?php echo date('H:i', strtotime($schedule['start_time'])); ?> -
                                            <?php echo date('H:i', strtotime($schedule['end_time'])); ?>
                                        </td>
                                        <td>
                                            <strong><?php echo htmlspecialchars($schedule['activity_title']); ?></strong>
                                            <?php if ($schedule['task_title']): ?>
                                            <br><small class="text-muted">Task:
                                                <?php echo htmlspecialchars($schedule['task_title']); ?></small>
                                            <?php endif; ?>
                                        </td>
                                        <td><?php echo htmlspecialchars($schedule['project_name']); ?></td>
                                        <td><?php echo htmlspecialchars($schedule['assigned_to_name']); ?></td>
                                        <td><?php echo htmlspecialchars($schedule['location']); ?></td>
                                        <td>
                                            <span
                                                class="badge bg-<?php 
                                                echo match($schedule['status']) {
                                                    'completed' => 'success',
                                                    'in_progress' => 'warning',
                                                    'scheduled' => 'info',
                                                    'cancelled' => 'danger',
                                                    default => 'secondary'
                                                };
                                            ?>"><?php echo ucfirst(str_replace('_', ' ', $schedule['status'])); ?></span>
                                        </td>
                                        <td>
                                            <div class="btn-group btn-group-sm">
                                                <a href="view.php?id=<?php echo $schedule['id']; ?>"
                                                    class="btn btn-outline-primary">
                                                    <i class="fas fa-eye"></i>
                                                </a>
                                                <?php if (hasPermission($current_user['role'], 'schedule', 'update')): ?>
                                                <a href="edit.php?id=<?php echo $schedule['id']; ?>"
                                                    class="btn btn-outline-success">
                                                    <i class="fas fa-edit"></i>
                                                </a>
                                                <?php endif; ?>
                                            </div>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
.timeline {
    position: relative;
    padding-left: 30px;
}

.timeline::before {
    content: '';
    position: absolute;
    left: 15px;
    top: 0;
    bottom: 0;
    width: 2px;
    background: #dee2e6;
}

.timeline-item {
    position: relative;
    margin-bottom: 20px;
}

.timeline-item::before {
    content: '';
    position: absolute;
    left: -19px;
    top: 5px;
    width: 10px;
    height: 10px;
    border-radius: 50%;
    background: #007bff;
}

.timeline-time {
    font-weight: bold;
    color: #007bff;
    margin-bottom: 5px;
}

.timeline-content {
    background: #f8f9fa;
    padding: 15px;
    border-radius: 8px;
    border-left: 3px solid #007bff;
}
</style>

<?php include '../../includes/footer.php'; ?>