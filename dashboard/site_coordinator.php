<?php 
include '../includes/header.php';
require_once '../config/roles.php';

// Check if user has site-coordinator role
if ($current_user['role'] !== 'site-coordinator') {
    header("Location: " . $current_user['role'] . ".php");
    exit();
}

// Fetch coordinator's tasks and activities
$tasks_query = "SELECT t.*, p.name as project_name, u.name as assigned_by_name
                FROM tasks t 
                LEFT JOIN projects p ON t.project_id = p.id 
                LEFT JOIN users u ON t.assigned_by = u.id
                WHERE t.assigned_to = ? OR t.project_id IN (
                    SELECT id FROM projects WHERE site_coordinator_id = ?
                )
                ORDER BY t.due_date ASC, t.priority DESC";
$tasks_stmt = $db->prepare($tasks_query);
$tasks_stmt->execute([$current_user['id'], $current_user['id']]);
$tasks = $tasks_stmt->fetchAll(PDO::FETCH_ASSOC);

// Fetch projects where user is coordinator
$projects_query = "SELECT p.*, 
                   COUNT(t.id) as total_tasks,
                   COUNT(CASE WHEN t.status = 'completed' THEN 1 END) as completed_tasks
                   FROM projects p 
                   LEFT JOIN tasks t ON p.id = t.project_id 
                   WHERE p.site_coordinator_id = ? 
                   GROUP BY p.id 
                   ORDER BY p.created_at DESC";
$projects_stmt = $db->prepare($projects_query);
$projects_stmt->execute([$current_user['id']]);
$projects = $projects_stmt->fetchAll(PDO::FETCH_ASSOC);

// Fetch recent communications/notifications
$notifications_query = "SELECT * FROM notifications 
                        WHERE user_id = ? 
                        ORDER BY created_at DESC LIMIT 5";
$notifications_stmt = $db->prepare($notifications_query);
$notifications_stmt->execute([$current_user['id']]);
$notifications = $notifications_stmt->fetchAll(PDO::FETCH_ASSOC);

// Fetch team members to coordinate
$team_query = "SELECT DISTINCT u.id, u.name, u.role, u.last_activity
               FROM users u
               JOIN tasks t ON u.id = t.assigned_to
               JOIN projects p ON t.project_id = p.id
               WHERE p.site_coordinator_id = ?
               ORDER BY u.name";
$team_stmt = $db->prepare($team_query);
$team_stmt->execute([$current_user['id']]);
$team_members = $team_stmt->fetchAll(PDO::FETCH_ASSOC);
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
                        <h2>Welcome, <?php echo htmlspecialchars($current_user['name']); ?>! 🤝</h2>
                        <p class="text-muted mb-0">Coordinating teams and project communications</p>
                    </div>
                    <div class="btn-group">
                        <a href="../modules/tasks/create.php" class="btn btn-primary">
                            <i class="fas fa-plus"></i> New Task
                        </a>
                        <a href="../modules/reports/create.php" class="btn btn-outline-info">
                            <i class="fas fa-file-alt"></i> Create Report
                        </a>
                        <a href="../modules/calendar/index.php" class="btn btn-outline-success">
                            <i class="fas fa-calendar"></i> Calendar
                        </a>
                    </div>
                </div>

                <!-- Statistics Cards -->
                <div class="row mb-4">
                    <div class="col-xl-3 col-md-6">
                        <div class="card border-left-primary h-100">
                            <div class="card-body">
                                <div class="row align-items-center">
                                    <div class="col">
                                        <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">
                                            Coordinating Projects
                                        </div>
                                        <div class="h5 mb-0 font-weight-bold text-gray-800">
                                            <?php echo count($projects); ?>
                                        </div>
                                    </div>
                                    <div class="col-auto">
                                        <i class="fas fa-project-diagram fa-2x text-gray-300"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="col-xl-3 col-md-6">
                        <div class="card border-left-success h-100">
                            <div class="card-body">
                                <div class="row align-items-center">
                                    <div class="col">
                                        <div class="text-xs font-weight-bold text-success text-uppercase mb-1">
                                            My Tasks
                                        </div>
                                        <div class="h5 mb-0 font-weight-bold text-gray-800">
                                            <?php echo count(array_filter($tasks, fn($t) => $t['status'] != 'completed')); ?>
                                        </div>
                                    </div>
                                    <div class="col-auto">
                                        <i class="fas fa-tasks fa-2x text-gray-300"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="col-xl-3 col-md-6">
                        <div class="card border-left-warning h-100">
                            <div class="card-body">
                                <div class="row align-items-center">
                                    <div class="col">
                                        <div class="text-xs font-weight-bold text-warning text-uppercase mb-1">
                                            Team Members
                                        </div>
                                        <div class="h5 mb-0 font-weight-bold text-gray-800">
                                            <?php echo count($team_members); ?>
                                        </div>
                                    </div>
                                    <div class="col-auto">
                                        <i class="fas fa-users fa-2x text-gray-300"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="col-xl-3 col-md-6">
                        <div class="card border-left-info h-100">
                            <div class="card-body">
                                <div class="row align-items-center">
                                    <div class="col">
                                        <div class="text-xs font-weight-bold text-info text-uppercase mb-1">
                                            Notifications
                                        </div>
                                        <div class="h5 mb-0 font-weight-bold text-gray-800">
                                            <?php echo count(array_filter($notifications, fn($n) => !$n['is_read'])); ?>
                                        </div>
                                    </div>
                                    <div class="col-auto">
                                        <i class="fas fa-bell fa-2x text-gray-300"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Main Content Row -->
                <div class="row">
                    <!-- My Tasks -->
                    <div class="col-lg-8">
                        <div class="card mb-4">
                            <div class="card-header d-flex justify-content-between align-items-center">
                                <h6 class="m-0 font-weight-bold text-primary">
                                    <i class="fas fa-tasks"></i> My Tasks & Coordination Activities
                                </h6>
                                <a href="../modules/tasks/list.php" class="btn btn-sm btn-primary">View All</a>
                            </div>
                            <div class="card-body">
                                <?php if (empty($tasks)): ?>
                                <div class="text-center py-4">
                                    <i class="fas fa-tasks fa-3x text-muted mb-3"></i>
                                    <h6 class="text-muted">No tasks assigned yet</h6>
                                    <p class="text-muted">You'll see your coordination tasks here once they're assigned.
                                    </p>
                                </div>
                                <?php else: ?>
                                <div class="table-responsive">
                                    <table class="table table-hover">
                                        <thead>
                                            <tr>
                                                <th>Task</th>
                                                <th>Project</th>
                                                <th>Priority</th>
                                                <th>Due Date</th>
                                                <th>Status</th>
                                                <th>Actions</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach (array_slice($tasks, 0, 10) as $task): ?>
                                            <tr
                                                class="<?php echo $task['due_date'] && strtotime($task['due_date']) < time() && $task['status'] != 'completed' ? 'table-warning' : ''; ?>">
                                                <td>
                                                    <strong><?php echo htmlspecialchars($task['title']); ?></strong>
                                                    <?php if ($task['description']): ?>
                                                    <br><small
                                                        class="text-muted"><?php echo htmlspecialchars(substr($task['description'], 0, 50)) . '...'; ?></small>
                                                    <?php endif; ?>
                                                </td>
                                                <td><?php echo htmlspecialchars($task['project_name']); ?></td>
                                                <td><?php echo getPriorityBadge($task['priority']); ?></td>
                                                <td>
                                                    <?php if ($task['due_date']): ?>
                                                    <span
                                                        class="<?php echo strtotime($task['due_date']) < time() && $task['status'] != 'completed' ? 'text-danger fw-bold' : ''; ?>">
                                                        <?php echo date('M d', strtotime($task['due_date'])); ?>
                                                    </span>
                                                    <?php else: ?>
                                                    <span class="text-muted">No deadline</span>
                                                    <?php endif; ?>
                                                </td>
                                                <td><?php echo getStatusBadge($task['status'], 'task'); ?></td>
                                                <td>
                                                    <div class="btn-group btn-group-sm">
                                                        <a href="../modules/tasks/view.php?id=<?php echo $task['id']; ?>"
                                                            class="btn btn-outline-primary">View</a>
                                                        <?php if ($task['status'] != 'completed'): ?>
                                                        <a href="../modules/tasks/edit.php?id=<?php echo $task['id']; ?>"
                                                            class="btn btn-outline-success">Edit</a>
                                                        <?php endif; ?>
                                                    </div>
                                                </td>
                                            </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>

                    <!-- Team & Communications -->
                    <div class="col-lg-4">
                        <!-- Team Status -->
                        <div class="card mb-4">
                            <div class="card-header d-flex justify-content-between align-items-center">
                                <h6 class="m-0 font-weight-bold text-primary">
                                    <i class="fas fa-users"></i> Team Status
                                </h6>
                                <a href="../modules/users/list.php" class="btn btn-sm btn-primary">View All</a>
                            </div>
                            <div class="card-body">
                                <?php if (empty($team_members)): ?>
                                <div class="text-center py-3">
                                    <i class="fas fa-users fa-2x text-muted mb-2"></i>
                                    <p class="text-muted mb-0">No team members assigned</p>
                                </div>
                                <?php else: ?>
                                <?php foreach (array_slice($team_members, 0, 6) as $member): ?>
                                <div class="d-flex align-items-center py-2 border-bottom">
                                    <div class="flex-grow-1">
                                        <h6 class="mb-1"><?php echo htmlspecialchars($member['name']); ?></h6>
                                        <small class="text-muted">
                                            <i class="fas fa-user"></i>
                                            <?php echo ucfirst(str_replace('_', ' ', $member['role'])); ?>
                                        </small>
                                    </div>
                                    <div>
                                        <?php if (isUserOnline($member['id'], $db)): ?>
                                        <span class="badge bg-success">Online</span>
                                        <?php else: ?>
                                        <span class="badge bg-secondary">Offline</span>
                                        <?php endif; ?>
                                    </div>
                                </div>
                                <?php endforeach; ?>
                                <?php endif; ?>
                            </div>
                        </div>

                        <!-- Recent Notifications -->
                        <div class="card mb-4">
                            <div class="card-header d-flex justify-content-between align-items-center">
                                <h6 class="m-0 font-weight-bold text-primary">
                                    <i class="fas fa-bell"></i> Recent Notifications
                                </h6>
                                <a href="../modules/notifications/list.php" class="btn btn-sm btn-primary">View All</a>
                            </div>
                            <div class="card-body">
                                <?php if (empty($notifications)): ?>
                                <div class="text-center py-3">
                                    <i class="fas fa-bell fa-2x text-muted mb-2"></i>
                                    <p class="text-muted mb-0">No notifications</p>
                                </div>
                                <?php else: ?>
                                <?php foreach ($notifications as $notification): ?>
                                <div
                                    class="notification-item py-2 border-bottom <?php echo !$notification['is_read'] ? 'unread' : ''; ?>">
                                    <h6 class="mb-1 <?php echo !$notification['is_read'] ? 'fw-bold' : ''; ?>">
                                        <?php echo htmlspecialchars($notification['title']); ?>
                                        <?php if (!$notification['is_read']): ?>
                                        <span class="badge bg-primary ms-1">New</span>
                                        <?php endif; ?>
                                    </h6>
                                    <p class="mb-1 small text-muted">
                                        <?php echo htmlspecialchars(substr($notification['message'], 0, 60)) . '...'; ?>
                                    </p>
                                    <small class="text-muted">
                                        <?php echo timeAgo($notification['created_at']); ?>
                                    </small>
                                </div>
                                <?php endforeach; ?>
                                <?php endif; ?>
                            </div>
                        </div>

                        <!-- Quick Actions -->
                        <div class="card">
                            <div class="card-header">
                                <h6 class="m-0 font-weight-bold text-primary">
                                    <i class="fas fa-bolt"></i> Quick Actions
                                </h6>
                            </div>
                            <div class="card-body">
                                <div class="d-grid gap-2">
                                    <a href="../modules/tasks/create.php" class="btn btn-outline-primary">
                                        <i class="fas fa-plus"></i> Assign Task
                                    </a>
                                    <a href="../modules/schedule/create.php" class="btn btn-outline-success">
                                        <i class="fas fa-calendar-plus"></i> Schedule Meeting
                                    </a>
                                    <a href="../modules/reports/create.php" class="btn btn-outline-info">
                                        <i class="fas fa-file-alt"></i> Coordination Report
                                    </a>
                                    <a href="../modules/documents/upload.php" class="btn btn-outline-warning">
                                        <i class="fas fa-upload"></i> Share Documents
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Project Coordination Overview -->
                <?php if (!empty($projects)): ?>
                <div class="card mt-4">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h6 class="m-0 font-weight-bold text-primary">
                            <i class="fas fa-project-diagram"></i> Projects I'm Coordinating
                        </h6>
                        <a href="../modules/projects/list.php" class="btn btn-sm btn-primary">View All</a>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <?php foreach (array_slice($projects, 0, 6) as $project): ?>
                            <div class="col-md-4 mb-3">
                                <div class="card border-left-info h-100">
                                    <div class="card-body">
                                        <h6 class="card-title"><?php echo htmlspecialchars($project['name']); ?></h6>
                                        <p class="card-text text-muted mb-2">
                                            <i class="fas fa-user"></i>
                                            <?php echo htmlspecialchars($project['client_name']); ?>
                                        </p>

                                        <?php 
                                        $progress = $project['total_tasks'] > 0 ? 
                                            round(($project['completed_tasks'] / $project['total_tasks']) * 100) : 0;
                                        ?>

                                        <div class="mb-2">
                                            <small class="text-muted">Progress: <?php echo $progress; ?>%</small>
                                            <div class="progress" style="height: 6px;">
                                                <div class="progress-bar bg-info"
                                                    style="width: <?php echo $progress; ?>%"></div>
                                            </div>
                                        </div>

                                        <div class="d-flex justify-content-between align-items-center">
                                            <?php echo getStatusBadge($project['status'], 'project'); ?>
                                            <a href="../modules/projects/view.php?id=<?php echo $project['id']; ?>"
                                                class="btn btn-sm btn-outline-primary">View</a>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<style>
.notification-item.unread {
    background-color: #f8f9ff;
}
</style>

<?php include '../includes/footer.php'; ?>