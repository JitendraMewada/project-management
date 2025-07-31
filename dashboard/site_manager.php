<?php 
include '../includes/header.php';
require_once '../config/roles.php';

// Check if user has site-manager role
if ($current_user['role'] !== 'site-manager') {
    header("Location: " . $current_user['role'] . ".php");
    exit();
}

// Fetch site manager's projects
$projects_query = "SELECT p.*, 
                   COUNT(t.id) as total_tasks,
                   COUNT(CASE WHEN t.status = 'completed' THEN 1 END) as completed_tasks
                   FROM projects p 
                   LEFT JOIN tasks t ON p.id = t.project_id 
                   WHERE p.site_manager_id = ? 
                   GROUP BY p.id 
                   ORDER BY p.created_at DESC";
$projects_stmt = $db->prepare($projects_query);
$projects_stmt->execute([$current_user['id']]);
$projects = $projects_stmt->fetchAll(PDO::FETCH_ASSOC);

// Fetch today's attendance
$attendance_query = "SELECT a.*, u.name as user_name 
                     FROM attendance a 
                     LEFT JOIN users u ON a.user_id = u.id 
                     WHERE a.date = CURDATE() 
                     ORDER BY a.check_in_time DESC";
$attendance_stmt = $db->prepare($attendance_query);
$attendance_stmt->execute();
$today_attendance = $attendance_stmt->fetchAll(PDO::FETCH_ASSOC);

// Fetch inventory alerts
$inventory_query = "SELECT * FROM inventory_items 
                    WHERE stock_quantity <= minimum_stock 
                    ORDER BY stock_quantity ASC LIMIT 5";
$inventory_stmt = $db->prepare($inventory_query);
$inventory_stmt->execute();
$low_stock_items = $inventory_stmt->fetchAll(PDO::FETCH_ASSOC);

// Fetch upcoming schedules
$schedule_query = "SELECT s.*, p.name as project_name, u.name as assigned_to_name
                   FROM project_schedules s
                   LEFT JOIN projects p ON s.project_id = p.id
                   LEFT JOIN users u ON s.assigned_to = u.id
                   WHERE s.scheduled_date >= CURDATE() 
                   AND s.scheduled_date <= DATE_ADD(CURDATE(), INTERVAL 7 DAY)
                   ORDER BY s.scheduled_date, s.start_time LIMIT 10";
$schedule_stmt = $db->prepare($schedule_query);
$schedule_stmt->execute();
$upcoming_schedules = $schedule_stmt->fetchAll(PDO::FETCH_ASSOC);
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
                        <h2>Welcome, <?php echo htmlspecialchars($current_user['name']); ?>! 🏗️</h2>
                        <p class="text-muted mb-0">Managing construction sites and team operations</p>
                    </div>
                    <div class="btn-group">
                        <a href="../modules/attendance/list.php" class="btn btn-primary">
                            <i class="fas fa-user-clock"></i> Attendance
                        </a>
                        <a href="../modules/inventory/list.php" class="btn btn-outline-warning">
                            <i class="fas fa-boxes"></i> Inventory
                        </a>
                        <a href="../modules/schedule/index.php" class="btn btn-outline-info">
                            <i class="fas fa-calendar-check"></i> Schedule
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
                                            Active Sites
                                        </div>
                                        <div class="h5 mb-0 font-weight-bold text-gray-800">
                                            <?php echo count(array_filter($projects, fn($p) => $p['status'] == 'active')); ?>
                                        </div>
                                    </div>
                                    <div class="col-auto">
                                        <i class="fas fa-construction fa-2x text-gray-300"></i>
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
                                            Present Today
                                        </div>
                                        <div class="h5 mb-0 font-weight-bold text-gray-800">
                                            <?php echo count(array_filter($today_attendance, fn($a) => $a['status'] == 'present')); ?>
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
                        <div class="card border-left-warning h-100">
                            <div class="card-body">
                                <div class="row align-items-center">
                                    <div class="col">
                                        <div class="text-xs font-weight-bold text-warning text-uppercase mb-1">
                                            Low Stock Items
                                        </div>
                                        <div class="h5 mb-0 font-weight-bold text-gray-800">
                                            <?php echo count($low_stock_items); ?>
                                        </div>
                                    </div>
                                    <div class="col-auto">
                                        <i class="fas fa-exclamation-triangle fa-2x text-gray-300"></i>
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
                                            This Week Tasks
                                        </div>
                                        <div class="h5 mb-0 font-weight-bold text-gray-800">
                                            <?php echo count($upcoming_schedules); ?>
                                        </div>
                                    </div>
                                    <div class="col-auto">
                                        <i class="fas fa-calendar-week fa-2x text-gray-300"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Main Content Row -->
                <div class="row">
                    <!-- Site Projects -->
                    <div class="col-lg-8">
                        <div class="card mb-4">
                            <div class="card-header d-flex justify-content-between align-items-center">
                                <h6 class="m-0 font-weight-bold text-primary">
                                    <i class="fas fa-construction"></i> My Construction Sites
                                </h6>
                                <a href="../modules/projects/list.php" class="btn btn-sm btn-primary">View All</a>
                            </div>
                            <div class="card-body">
                                <?php if (empty($projects)): ?>
                                <div class="text-center py-4">
                                    <i class="fas fa-construction fa-3x text-muted mb-3"></i>
                                    <h6 class="text-muted">No construction sites assigned</h6>
                                    <p class="text-muted">You'll see your construction projects here once they're
                                        assigned.</p>
                                </div>
                                <?php else: ?>
                                <div class="table-responsive">
                                    <table class="table table-hover">
                                        <thead>
                                            <tr>
                                                <th>Project</th>
                                                <th>Client</th>
                                                <th>Progress</th>
                                                <th>Status</th>
                                                <th>Due Date</th>
                                                <th>Actions</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($projects as $project): ?>
                                            <tr
                                                class="<?php echo strtotime($project['end_date']) < time() && $project['status'] != 'completed' ? 'table-warning' : ''; ?>">
                                                <td>
                                                    <strong><?php echo htmlspecialchars($project['name']); ?></strong>
                                                    <br><small
                                                        class="text-muted"><?php echo htmlspecialchars($project['project_type']); ?></small>
                                                </td>
                                                <td><?php echo htmlspecialchars($project['client_name']); ?></td>
                                                <td>
                                                    <?php 
                                                        $progress = $project['total_tasks'] > 0 ? 
                                                            round(($project['completed_tasks'] / $project['total_tasks']) * 100) : 0;
                                                        ?>
                                                    <div class="progress" style="height: 6px;">
                                                        <div class="progress-bar bg-<?php echo $progress >= 75 ? 'success' : ($progress >= 50 ? 'warning' : 'danger'); ?>"
                                                            style="width: <?php echo $progress; ?>%"></div>
                                                    </div>
                                                    <small class="text-muted"><?php echo $progress; ?>%</small>
                                                </td>
                                                <td><?php echo getStatusBadge($project['status'], 'project'); ?></td>
                                                <td>
                                                    <?php if ($project['end_date']): ?>
                                                    <span
                                                        class="<?php echo strtotime($project['end_date']) < time() && $project['status'] != 'completed' ? 'text-danger fw-bold' : ''; ?>">
                                                        <?php echo date('M d, Y', strtotime($project['end_date'])); ?>
                                                    </span>
                                                    <?php else: ?>
                                                    <span class="text-muted">Not set</span>
                                                    <?php endif; ?>
                                                </td>
                                                <td>
                                                    <a href="../modules/projects/view.php?id=<?php echo $project['id']; ?>"
                                                        class="btn btn-sm btn-outline-primary">View</a>
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

                    <!-- Attendance & Alerts -->
                    <div class="col-lg-4">
                        <!-- Today's Attendance -->
                        <div class="card mb-4">
                            <div class="card-header d-flex justify-content-between align-items-center">
                                <h6 class="m-0 font-weight-bold text-primary">
                                    <i class="fas fa-user-clock"></i> Today's Attendance
                                </h6>
                                <a href="../modules/attendance/list.php" class="btn btn-sm btn-primary">View All</a>
                            </div>
                            <div class="card-body">
                                <?php if (empty($today_attendance)): ?>
                                <div class="text-center py-3">
                                    <i class="fas fa-user-clock fa-2x text-muted mb-2"></i>
                                    <p class="text-muted mb-0">No attendance records today</p>
                                </div>
                                <?php else: ?>
                                <?php foreach (array_slice($today_attendance, 0, 6) as $attendance): ?>
                                <div class="d-flex align-items-center py-2 border-bottom">
                                    <div class="flex-grow-1">
                                        <h6 class="mb-1"><?php echo htmlspecialchars($attendance['user_name']); ?></h6>
                                        <small class="text-muted">
                                            <?php if ($attendance['check_in_time']): ?>
                                            In: <?php echo date('H:i', strtotime($attendance['check_in_time'])); ?>
                                            <?php endif; ?>
                                            <?php if ($attendance['check_out_time']): ?>
                                            | Out: <?php echo date('H:i', strtotime($attendance['check_out_time'])); ?>
                                            <?php endif; ?>
                                        </small>
                                    </div>
                                    <div>
                                        <span class="badge bg-<?php 
                                                echo match($attendance['status']) {
                                                    'present' => 'success',
                                                    'late' => 'warning',
                                                    'absent' => 'danger',
                                                    default => 'secondary'
                                                };
                                            ?>"><?php echo ucfirst($attendance['status']); ?></span>
                                    </div>
                                </div>
                                <?php endforeach; ?>
                                <?php endif; ?>
                            </div>
                        </div>

                        <!-- Inventory Alerts -->
                        <?php if (!empty($low_stock_items)): ?>
                        <div class="card mb-4">
                            <div class="card-header d-flex justify-content-between align-items-center">
                                <h6 class="m-0 font-weight-bold text-warning">
                                    <i class="fas fa-exclamation-triangle"></i> Low Stock Alerts
                                </h6>
                                <a href="../modules/inventory/low_stock.php" class="btn btn-sm btn-warning">View All</a>
                            </div>
                            <div class="card-body">
                                <?php foreach ($low_stock_items as $item): ?>
                                <div class="alert alert-warning py-2 mb-2">
                                    <strong><?php echo htmlspecialchars($item['name']); ?></strong>
                                    <br><small>Stock: <?php echo $item['stock_quantity']; ?>
                                        <?php echo htmlspecialchars($item['unit']); ?>
                                        (Min: <?php echo $item['minimum_stock']; ?>)</small>
                                </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                        <?php endif; ?>

                        <!-- Quick Actions -->
                        <div class="card">
                            <div class="card-header">
                                <h6 class="m-0 font-weight-bold text-primary">
                                    <i class="fas fa-bolt"></i> Quick Actions
                                </h6>
                            </div>
                            <div class="card-body">
                                <div class="d-grid gap-2">
                                    <a href="../modules/attendance/create.php" class="btn btn-outline-primary">
                                        <i class="fas fa-user-check"></i> Mark Attendance
                                    </a>
                                    <a href="../modules/inventory/create.php" class="btn btn-outline-success">
                                        <i class="fas fa-plus"></i> Add Inventory
                                    </a>
                                    <a href="../modules/schedule/create.php" class="btn btn-outline-info">
                                        <i class="fas fa-calendar-plus"></i> Schedule Task
                                    </a>
                                    <a href="../modules/reports/create.php" class="btn btn-outline-warning">
                                        <i class="fas fa-file-alt"></i> Site Report
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Upcoming Schedule -->
                <?php if (!empty($upcoming_schedules)): ?>
                <div class="card mt-4">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h6 class="m-0 font-weight-bold text-primary">
                            <i class="fas fa-calendar-week"></i> This Week's Schedule
                        </h6>
                        <a href="../modules/schedule/index.php" class="btn btn-sm btn-primary">View All</a>
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
                                        <th>Status</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($upcoming_schedules as $schedule): ?>
                                    <tr>
                                        <td><?php echo date('M d', strtotime($schedule['scheduled_date'])); ?></td>
                                        <td>
                                            <?php echo date('H:i', strtotime($schedule['start_time'])); ?> -
                                            <?php echo date('H:i', strtotime($schedule['end_time'])); ?>
                                        </td>
                                        <td><?php echo htmlspecialchars($schedule['activity_title']); ?></td>
                                        <td><?php echo htmlspecialchars($schedule['project_name']); ?></td>
                                        <td><?php echo htmlspecialchars($schedule['assigned_to_name']); ?></td>
                                        <td>
                                            <span
                                                class="badge bg-<?php 
                                                echo match($schedule['status']) {
                                                    'completed' => 'success',
                                                    'in_progress' => 'warning',
                                                    'scheduled' => 'info',
                                                    default => 'secondary'
                                                };
                                            ?>"><?php echo ucfirst(str_replace('_', ' ', $schedule['status'])); ?></span>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?>