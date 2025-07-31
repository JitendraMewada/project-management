<?php 
include '../../includes/header.php';
require_once '../../config/roles.php';

// Check permission
if (!hasPermission($current_user['role'], 'tasks', 'read')) {
    header("Location: ../../dashboard/" . $current_user['role'] . ".php");
    exit();
}

// Fetch tasks based on user role
$query = "SELECT t.*, p.name as project_name, u1.name as assigned_to_name, u2.name as assigned_by_name 
          FROM tasks t 
          LEFT JOIN projects p ON t.project_id = p.id 
          LEFT JOIN users u1 ON t.assigned_to = u1.id 
          LEFT JOIN users u2 ON t.assigned_by = u2.id";

if ($current_user['role'] != 'admin' && $current_user['role'] != 'manager') {
    $query .= " WHERE t.assigned_to = ?";
}

$query .= " ORDER BY t.priority DESC, t.due_date ASC";
$stmt = $db->prepare($query);

if ($current_user['role'] != 'admin' && $current_user['role'] != 'manager') {
    $stmt->execute([$current_user['id']]);
} else {
    $stmt->execute();
}

$tasks = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<div class="container-fluid">
    <div class="row">
        <div class="col-md-3">
            <?php include '../../includes/sidebar.php'; ?>
        </div>
        <div class="col-md-9">
            <div class="main-content p-4">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h2><i class="fas fa-tasks"></i> Task Management</h2>
                    <?php if (hasPermission($current_user['role'], 'tasks', 'create')): ?>
                    <a href="create.php" class="btn btn-primary">
                        <i class="fas fa-plus"></i> New Task
                    </a>
                    <?php endif; ?>
                </div>

                <!-- Task Statistics -->
                <div class="row mb-4">
                    <div class="col-md-3">
                        <div class="card bg-danger text-white">
                            <div class="card-body text-center">
                                <h3><?php echo count(array_filter($tasks, fn($t) => $t['priority'] == 'critical')); ?>
                                </h3>
                                <p>Critical</p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card bg-warning text-white">
                            <div class="card-body text-center">
                                <h3><?php echo count(array_filter($tasks, fn($t) => $t['priority'] == 'high')); ?></h3>
                                <p>High Priority</p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card bg-info text-white">
                            <div class="card-body text-center">
                                <h3><?php echo count(array_filter($tasks, fn($t) => $t['status'] == 'in_progress')); ?>
                                </h3>
                                <p>In Progress</p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card bg-success text-white">
                            <div class="card-body text-center">
                                <h3><?php echo count(array_filter($tasks, fn($t) => $t['status'] == 'completed')); ?>
                                </h3>
                                <p>Completed</p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Tasks Table -->
                <div class="card">
                    <div class="card-header">
                        <div class="row align-items-center">
                            <div class="col-md-6">
                                <h5 class="mb-0">All Tasks</h5>
                            </div>
                            <div class="col-md-6">
                                <div class="d-flex gap-2">
                                    <select class="form-control" id="statusFilter">
                                        <option value="">All Status</option>
                                        <option value="pending">Pending</option>
                                        <option value="in_progress">In Progress</option>
                                        <option value="completed">Completed</option>
                                        <option value="cancelled">Cancelled</option>
                                    </select>
                                    <input type="text" class="form-control table-search" placeholder="Search tasks...">
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-hover data-table">
                                <thead>
                                    <tr>
                                        <th>Task</th>
                                        <th>Project</th>
                                        <th>Assigned To</th>
                                        <th>Priority</th>
                                        <th>Status</th>
                                        <th>Due Date</th>
                                        <th>Progress</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($tasks as $task): ?>
                                    <tr>
                                        <td>
                                            <strong><?php echo htmlspecialchars($task['title']); ?></strong>
                                            <?php if ($task['description']): ?>
                                            <br><small
                                                class="text-muted"><?php echo htmlspecialchars(substr($task['description'], 0, 50)) . '...'; ?></small>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <span
                                                class="badge bg-secondary"><?php echo htmlspecialchars($task['project_name']); ?></span>
                                        </td>
                                        <td><?php echo htmlspecialchars($task['assigned_to_name'] ?? 'Unassigned'); ?>
                                        </td>
                                        <td>
                                            <span class="badge bg-<?php 
                                                echo match($task['priority']) {
                                                    'critical' => 'danger',
                                                    'high' => 'warning',
                                                    'medium' => 'info',
                                                    'low' => 'secondary',
                                                    default => 'secondary'
                                                };
                                            ?>"><?php echo ucfirst($task['priority']); ?></span>
                                        </td>
                                        <td>
                                            <span class="badge bg-<?php 
                                                echo match($task['status']) {
                                                    'completed' => 'success',
                                                    'in_progress' => 'warning',
                                                    'pending' => 'info',
                                                    'cancelled' => 'danger',
                                                    default => 'secondary'
                                                };
                                            ?>"><?php echo ucfirst(str_replace('_', ' ', $task['status'])); ?></span>
                                        </td>
                                        <td>
                                            <?php if ($task['due_date']): ?>
                                            <?php 
                                                $due_date = strtotime($task['due_date']);
                                                $is_overdue = $due_date < time() && $task['status'] != 'completed';
                                                ?>
                                            <span class="<?php echo $is_overdue ? 'text-danger fw-bold' : ''; ?>">
                                                <?php echo date('M d, Y', $due_date); ?>
                                                <?php if ($is_overdue): ?>
                                                <i class="fas fa-exclamation-triangle"></i>
                                                <?php endif; ?>
                                            </span>
                                            <?php else: ?>
                                            <span class="text-muted">No due date</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <?php 
                                            $progress = 0;
                                            if ($task['status'] == 'completed') $progress = 100;
                                            elseif ($task['status'] == 'in_progress') $progress = 50;
                                            elseif ($task['status'] == 'pending') $progress = 0;
                                            ?>
                                            <div class="progress" style="height: 15px;">
                                                <div class="progress-bar bg-<?php echo $progress < 30 ? 'danger' : ($progress < 70 ? 'warning' : 'success'); ?>"
                                                    style="width: <?php echo $progress; ?>%">
                                                    <?php echo $progress; ?>%
                                                </div>
                                            </div>
                                        </td>
                                        <td>
                                            <div class="btn-group btn-group-sm">
                                                <a href="view.php?id=<?php echo $task['id']; ?>"
                                                    class="btn btn-outline-primary" title="View">
                                                    <i class="fas fa-eye"></i>
                                                </a>
                                                <?php if (hasPermission($current_user['role'], 'tasks', 'update') || $task['assigned_to'] == $current_user['id']): ?>
                                                <a href="edit.php?id=<?php echo $task['id']; ?>"
                                                    class="btn btn-outline-success" title="Edit">
                                                    <i class="fas fa-edit"></i>
                                                </a>
                                                <?php endif; ?>
                                                <?php if (hasPermission($current_user['role'], 'tasks', 'delete')): ?>
                                                <a href="delete.php?id=<?php echo $task['id']; ?>"
                                                    class="btn btn-outline-danger delete-btn" title="Delete">
                                                    <i class="fas fa-trash"></i>
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

<script>
// Filter tasks by status
document.getElementById('statusFilter').addEventListener('change', function() {
    const selectedStatus = this.value.toLowerCase();
    const rows = document.querySelectorAll('.data-table tbody tr');

    rows.forEach(row => {
        const statusCell = row.cells[4]; // Status column
        const statusText = statusCell.textContent.toLowerCase().trim();

        if (selectedStatus === '' || statusText.includes(selectedStatus.replace('_', ' '))) {
            row.style.display = '';
        } else {
            row.style.display = 'none';
        }
    });
});
</script>

<?php include '../../includes/footer.php'; ?>