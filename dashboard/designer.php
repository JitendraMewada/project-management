<?php 
include '../includes/header.php';
require_once '../config/roles.php';

// Check if user has designer role
if ($current_user['role'] !== 'designer') {
    header("Location: " . $current_user['role'] . ".php");
    exit();
}

// Fetch designer's projects
$projects_query = "SELECT p.*, 
                   COUNT(t.id) as total_tasks,
                   COUNT(CASE WHEN t.status = 'completed' THEN 1 END) as completed_tasks
                   FROM projects p 
                   LEFT JOIN tasks t ON p.id = t.project_id 
                   WHERE p.designer_id = ? 
                   GROUP BY p.id 
                   ORDER BY p.created_at DESC LIMIT 6";
$projects_stmt = $db->prepare($projects_query);
$projects_stmt->execute([$current_user['id']]);
$projects = $projects_stmt->fetchAll(PDO::FETCH_ASSOC);

// Fetch designer's tasks
$tasks_query = "SELECT t.*, p.name as project_name 
                FROM tasks t 
                LEFT JOIN projects p ON t.project_id = p.id 
                WHERE t.assigned_to = ? 
                ORDER BY t.due_date ASC, t.priority DESC LIMIT 8";
$tasks_stmt = $db->prepare($tasks_query);
$tasks_stmt->execute([$current_user['id']]);
$tasks = $tasks_stmt->fetchAll(PDO::FETCH_ASSOC);

// Fetch recent designs
$designs_query = "SELECT d.*, p.name as project_name 
                  FROM designs d 
                  LEFT JOIN projects p ON d.project_id = p.id 
                  WHERE d.designer_id = ? 
                  ORDER BY d.created_at DESC LIMIT 6";
$designs_stmt = $db->prepare($designs_query);
$designs_stmt->execute([$current_user['id']]);
$designs = $designs_stmt->fetchAll(PDO::FETCH_ASSOC);
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
                        <h2>Welcome back, <?php echo htmlspecialchars($current_user['name']); ?>! 🎨</h2>
                        <p class="text-muted mb-0">Here's what's happening with your design projects</p>
                    </div>
                    <div class="btn-group">
                        <a href="../modules/designs/create.php" class="btn btn-primary">
                            <i class="fas fa-palette"></i> New Design
                        </a>
                        <a href="../modules/projects/list.php" class="btn btn-outline-info">
                            <i class="fas fa-project-diagram"></i> My Projects
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
                                            Active Projects
                                        </div>
                                        <div class="h5 mb-0 font-weight-bold text-gray-800">
                                            <?php echo count(array_filter($projects, fn($p) => $p['status'] != 'completed')); ?>
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
                                            Pending Tasks
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
                        <div class="card border-left-info h-100">
                            <div class="card-body">
                                <div class="row align-items-center">
                                    <div class="col">
                                        <div class="text-xs font-weight-bold text-info text-uppercase mb-1">
                                            Design Files
                                        </div>
                                        <div class="h5 mb-0 font-weight-bold text-gray-800">
                                            <?php echo count($designs); ?>
                                        </div>
                                    </div>
                                    <div class="col-auto">
                                        <i class="fas fa-palette fa-2x text-gray-300"></i>
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
                                            Overdue Tasks
                                        </div>
                                        <div class="h5 mb-0 font-weight-bold text-gray-800">
                                            <?php echo count(array_filter($tasks, fn($t) => $t['due_date'] && strtotime($t['due_date']) < time() && $t['status'] != 'completed')); ?>
                                        </div>
                                    </div>
                                    <div class="col-auto">
                                        <i class="fas fa-exclamation-triangle fa-2x text-gray-300"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Main Content Row -->
                <div class="row">
                    <!-- My Projects -->
                    <div class="col-lg-8">
                        <div class="card mb-4">
                            <div class="card-header d-flex justify-content-between align-items-center">
                                <h6 class="m-0 font-weight-bold text-primary">
                                    <i class="fas fa-project-diagram"></i> My Projects
                                </h6>
                                <a href="../modules/projects/list.php" class="btn btn-sm btn-primary">View All</a>
                            </div>
                            <div class="card-body">
                                <?php if (empty($projects)): ?>
                                <div class="text-center py-4">
                                    <i class="fas fa-project-diagram fa-3x text-muted mb-3"></i>
                                    <h6 class="text-muted">No projects assigned yet</h6>
                                    <p class="text-muted">You'll see your design projects here once they're assigned.
                                    </p>
                                </div>
                                <?php else: ?>
                                <div class="row">
                                    <?php foreach ($projects as $project): ?>
                                    <div class="col-md-6 mb-3">
                                        <div class="card border-left-primary h-100">
                                            <div class="card-body">
                                                <h6 class="card-title"><?php echo htmlspecialchars($project['name']); ?>
                                                </h6>
                                                <p class="card-text text-muted">
                                                    <i class="fas fa-user"></i>
                                                    <?php echo htmlspecialchars($project['client_name']); ?>
                                                </p>

                                                <?php 
                                                    $progress = $project['total_tasks'] > 0 ? 
                                                        round(($project['completed_tasks'] / $project['total_tasks']) * 100) : 0;
                                                    ?>

                                                <div class="mb-2">
                                                    <small class="text-muted">Progress:
                                                        <?php echo $progress; ?>%</small>
                                                    <div class="progress" style="height: 6px;">
                                                        <div class="progress-bar"
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
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>

                    <!-- My Tasks & Quick Actions -->
                    <div class="col-lg-4">
                        <!-- My Tasks -->
                        <div class="card mb-4">
                            <div class="card-header d-flex justify-content-between align-items-center">
                                <h6 class="m-0 font-weight-bold text-primary">
                                    <i class="fas fa-tasks"></i> My Tasks
                                </h6>
                                <a href="../modules/tasks/list.php" class="btn btn-sm btn-primary">View All</a>
                            </div>
                            <div class="card-body">
                                <?php if (empty($tasks)): ?>
                                <div class="text-center py-3">
                                    <i class="fas fa-tasks fa-2x text-muted mb-2"></i>
                                    <p class="text-muted mb-0">No tasks assigned</p>
                                </div>
                                <?php else: ?>
                                <?php foreach (array_slice($tasks, 0, 5) as $task): ?>
                                <div class="d-flex align-items-center py-2 border-bottom">
                                    <div class="flex-grow-1">
                                        <h6 class="mb-1"><?php echo htmlspecialchars($task['title']); ?></h6>
                                        <small class="text-muted">
                                            <i class="fas fa-project-diagram"></i>
                                            <?php echo htmlspecialchars($task['project_name']); ?>
                                        </small>
                                        <?php if ($task['due_date']): ?>
                                        <br><small
                                            class="text-<?php echo strtotime($task['due_date']) < time() ? 'danger' : 'muted'; ?>">
                                            <i class="fas fa-calendar"></i>
                                            Due: <?php echo date('M d', strtotime($task['due_date'])); ?>
                                        </small>
                                        <?php endif; ?>
                                    </div>
                                    <div>
                                        <?php echo getPriorityBadge($task['priority']); ?>
                                    </div>
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
                                    <a href="../modules/designs/create.php" class="btn btn-outline-primary">
                                        <i class="fas fa-palette"></i> Create New Design
                                    </a>
                                    <a href="../modules/documents/upload.php" class="btn btn-outline-success">
                                        <i class="fas fa-upload"></i> Upload Files
                                    </a>
                                    <a href="../modules/reports/create.php" class="btn btn-outline-info">
                                        <i class="fas fa-file-alt"></i> Create Report
                                    </a>
                                    <a href="../modules/calendar/index.php" class="btn btn-outline-warning">
                                        <i class="fas fa-calendar"></i> View Calendar
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Recent Designs -->
                <?php if (!empty($designs)): ?>
                <div class="card mt-4">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h6 class="m-0 font-weight-bold text-primary">
                            <i class="fas fa-palette"></i> Recent Designs
                        </h6>
                        <a href="../modules/designs/list.php" class="btn btn-sm btn-primary">View All</a>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <?php foreach (array_slice($designs, 0, 6) as $design): ?>
                            <div class="col-md-4 mb-3">
                                <div class="card h-100">
                                    <div class="design-preview"
                                        style="height: 150px; background: #f8f9fa; display: flex; align-items: center; justify-content: center;">
                                        <?php if ($design['preview_image']): ?>
                                        <img src="../<?php echo htmlspecialchars($design['preview_image']); ?>"
                                            class="img-fluid" style="max-height: 100%; max-width: 100%;">
                                        <?php else: ?>
                                        <i class="fas fa-palette fa-2x text-muted"></i>
                                        <?php endif; ?>
                                    </div>
                                    <div class="card-body p-2">
                                        <h6 class="card-title mb-1"><?php echo htmlspecialchars($design['title']); ?>
                                        </h6>
                                        <small
                                            class="text-muted"><?php echo htmlspecialchars($design['project_name']); ?></small>
                                        <br><?php echo getStatusBadge($design['status']); ?>
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
.border-left-primary {
    border-left: 0.25rem solid #4e73df !important;
}

.border-left-success {
    border-left: 0.25rem solid #1cc88a !important;
}

.border-left-info {
    border-left: 0.25rem solid #36b9cc !important;
}

.border-left-warning {
    border-left: 0.25rem solid #f6c23e !important;
}

.text-xs {
    font-size: 0.7rem;
}
</style>

<?php include '../includes/footer.php'; ?>