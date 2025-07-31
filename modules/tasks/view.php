<?php 
include '../../includes/header.php';
require_once '../../config/roles.php';

// Check permission
if (!hasPermission($current_user['role'], 'tasks', 'read')) {
    header("Location: ../../dashboard/" . $current_user['role'] . ".php");
    exit();
}

$task_id = intval($_GET['id'] ?? 0);
if (!$task_id) {
    header("Location: list.php");
    exit();
}

// Fetch task details
$query = "SELECT t.*, p.name as project_name, p.client_name, 
          u1.name as assigned_to_name, u1.email as assigned_to_email, u1.role as assigned_to_role,
          u2.name as assigned_by_name
          FROM tasks t 
          LEFT JOIN projects p ON t.project_id = p.id 
          LEFT JOIN users u1 ON t.assigned_to = u1.id 
          LEFT JOIN users u2 ON t.assigned_by = u2.id
          WHERE t.id = ?";
$stmt = $db->prepare($query);
$stmt->execute([$task_id]);
$task = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$task) {
    header("Location: list.php");
    exit();
}

// Check if user can view this task
if ($current_user['role'] != 'admin' && $current_user['role'] != 'manager' && 
    $task['assigned_to'] != $current_user['id'] && $task['assigned_by'] != $current_user['id']) {
    header("Location: list.php");
    exit();
}

// Handle status update
if ($_POST && isset($_POST['update_status'])) {
    $new_status = $_POST['status'];
    $actual_hours = !empty($_POST['actual_hours']) ? floatval($_POST['actual_hours']) : null;
    
    $update_query = "UPDATE tasks SET status = ?, actual_hours = ?";
    $params = [$new_status, $actual_hours];
    
    if ($new_status == 'completed') {
        $update_query .= ", completion_date = CURRENT_DATE";
    }
    
    $update_query .= ", updated_at = CURRENT_TIMESTAMP WHERE id = ?";
    $params[] = $task_id;
    
    $update_stmt = $db->prepare($update_query);
    $update_stmt->execute($params);
    
    // Refresh task data
    $stmt->execute([$task_id]);
    $task = $stmt->fetch(PDO::FETCH_ASSOC);
    
    $message = 'Task status updated successfully!';
}
?>

<div class="container-fluid">
    <div class="row">
        <div class="col-md-3">
            <?php include '../../includes/sidebar.php'; ?>
        </div>
        <div class="col-md-9">
            <div class="main-content p-4">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h2><i class="fas fa-eye"></i> Task Details</h2>
                    <div class="btn-group">
                        <a href="list.php" class="btn btn-secondary">
                            <i class="fas fa-arrow-left"></i> Back to List
                        </a>
                        <?php if (hasPermission($current_user['role'], 'tasks', 'update') || $task['assigned_to'] == $current_user['id']): ?>
                        <a href="edit.php?id=<?php echo $task['id']; ?>" class="btn btn-primary">
                            <i class="fas fa-edit"></i> Edit Task
                        </a>
                        <?php endif; ?>
                    </div>
                </div>

                <?php if (isset($message)): ?>
                <div class="alert alert-success"><?php echo $message; ?></div>
                <?php endif; ?>

                <!-- Task Overview -->
                <div class="row mb-4">
                    <div class="col-md-8">
                        <div class="card">
                            <div class="card-header">
                                <div class="d-flex justify-content-between align-items-center">
                                    <h5 class="mb-0"><?php echo htmlspecialchars($task['title']); ?></h5>
                                    <span class="badge bg-<?php 
                                        echo match($task['priority']) {
                                            'critical' => 'danger',
                                            'high' => 'warning',
                                            'medium' => 'info',
                                            'low' => 'secondary',
                                            default => 'secondary'
                                        };
                                    ?> fs-6"><?php echo ucfirst($task['priority']); ?> Priority</span>
                                </div>
                            </div>
                            <div class="card-body">
                                <div class="row">
                                    <div class="col-md-6">
                                        <h6>Task Information</h6>
                                        <table class="table table-sm">
                                            <tr>
                                                <td><strong>Project:</strong></td>
                                                <td>
                                                    <a href="../projects/view.php?id=<?php echo $task['project_id']; ?>"
                                                        class="text-decoration-none">
                                                        <?php echo htmlspecialchars($task['project_name']); ?>
                                                    </a>
                                                </td>
                                            </tr>
                                            <tr>
                                                <td><strong>Client:</strong></td>
                                                <td><?php echo htmlspecialchars($task['client_name']); ?></td>
                                            </tr>
                                            <tr>
                                                <td><strong>Status:</strong></td>
                                                <td>
                                                    <span
                                                        class="badge bg-<?php 
                                                        echo match($task['status']) {
                                                            'completed' => 'success',
                                                            'in_progress' => 'warning',
                                                            'pending' => 'info',
                                                            'cancelled' => 'danger',
                                                            default => 'secondary'
                                                        };
                                                    ?>"><?php echo ucfirst(str_replace('_', ' ', $task['status'])); ?></span>
                                                </td>
                                            </tr>
                                            <tr>
                                                <td><strong>Assigned To:</strong></td>
                                                <td>
                                                    <?php if ($task['assigned_to_name']): ?>
                                                    <?php echo htmlspecialchars($task['assigned_to_name']); ?>
                                                    <br><small
                                                        class="text-muted"><?php echo ucfirst(str_replace('_', ' ', $task['assigned_to_role'])); ?></small>
                                                    <?php else: ?>
                                                    <span class="text-muted">Unassigned</span>
                                                    <?php endif; ?>
                                                </td>
                                            </tr>
                                            <tr>
                                                <td><strong>Assigned By:</strong></td>
                                                <td><?php echo htmlspecialchars($task['assigned_by_name']); ?></td>
                                            </tr>
                                        </table>
                                    </div>
                                    <div class="col-md-6">
                                        <h6>Timeline & Progress</h6>
                                        <table class="table table-sm">
                                            <tr>
                                                <td><strong>Start Date:</strong></td>
                                                <td><?php echo $task['start_date'] ? date('M d, Y', strtotime($task['start_date'])) : 'Not set'; ?>
                                                </td>
                                            </tr>
                                            <tr>
                                                <td><strong>Due Date:</strong></td>
                                                <td>
                                                    <?php if ($task['due_date']): ?>
                                                    <?php 
                                                        $due_date = strtotime($task['due_date']);
                                                        $is_overdue = $due_date < time() && $task['status'] != 'completed';
                                                        ?>
                                                    <span
                                                        class="<?php echo $is_overdue ? 'text-danger fw-bold' : ''; ?>">
                                                        <?php echo date('M d, Y', $due_date); ?>
                                                        <?php if ($is_overdue): ?>
                                                        <i class="fas fa-exclamation-triangle"></i> Overdue
                                                        <?php endif; ?>
                                                    </span>
                                                    <?php else: ?>
                                                    <span class="text-muted">No due date</span>
                                                    <?php endif; ?>
                                                </td>
                                            </tr>
                                            <tr>
                                                <td><strong>Completion Date:</strong></td>
                                                <td><?php echo $task['completion_date'] ? date('M d, Y', strtotime($task['completion_date'])) : '-'; ?>
                                                </td>
                                            </tr>
                                            <tr>
                                                <td><strong>Estimated Hours:</strong></td>
                                                <td><?php echo $task['estimated_hours'] ? $task['estimated_hours'] . ' hrs' : 'Not set'; ?>
                                                </td>
                                            </tr>
                                            <tr>
                                                <td><strong>Actual Hours:</strong></td>
                                                <td><?php echo $task['actual_hours'] ? $task['actual_hours'] . ' hrs' : 'Not recorded'; ?>
                                                </td>
                                            </tr>
                                        </table>
                                    </div>
                                </div>

                                <?php if ($task['description']): ?>
                                <hr>
                                <h6>Description</h6>
                                <div class="bg-light p-3 rounded">
                                    <?php echo nl2br(htmlspecialchars($task['description'])); ?>
                                </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>

                    <div class="col-md-4">
                        <!-- Status Update -->
                        <?php if ($task['assigned_to'] == $current_user['id'] || hasPermission($current_user['role'], 'tasks', 'update')): ?>
                        <div class="card">
                            <div class="card-header">
                                <h6 class="mb-0">Update Status</h6>
                            </div>
                            <div class="card-body">
                                <form method="POST">
                                    <div class="mb-3">
                                        <label class="form-label">Status</label>
                                        <select class="form-control" name="status">
                                            <option value="pending"
                                                <?php echo $task['status'] == 'pending' ? 'selected' : ''; ?>>Pending
                                            </option>
                                            <option value="in_progress"
                                                <?php echo $task['status'] == 'in_progress' ? 'selected' : ''; ?>>In
                                                Progress</option>
                                            <option value="completed"
                                                <?php echo $task['status'] == 'completed' ? 'selected' : ''; ?>>
                                                Completed</option>
                                            <option value="cancelled"
                                                <?php echo $task['status'] == 'cancelled' ? 'selected' : ''; ?>>
                                                Cancelled</option>
                                        </select>
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label">Actual Hours</label>
                                        <input type="number" class="form-control" name="actual_hours" min="0" step="0.5"
                                            value="<?php echo $task['actual_hours']; ?>" placeholder="Hours spent">
                                    </div>
                                    <button type="submit" name="update_status" class="btn btn-primary w-100">
                                        <i class="fas fa-save"></i> Update Status
                                    </button>
                                </form>
                            </div>
                        </div>
                        <?php endif; ?>

                        <!-- Progress Indicator -->
                        <div class="card mt-3">
                            <div class="card-header">
                                <h6 class="mb-0">Progress Overview</h6>
                            </div>
                            <div class="card-body text-center">
                                <?php 
                                $progress = 0;
                                if ($task['status'] == 'completed') $progress = 100;
                                elseif ($task['status'] == 'in_progress') $progress = 50;
                                elseif ($task['status'] == 'pending') $progress = 0;
                                ?>
                                <div class="progress mb-3" style="height: 25px;">
                                    <div class="progress-bar bg-<?php echo $progress < 30 ? 'danger' : ($progress < 70 ? 'warning' : 'success'); ?>"
                                        style="width: <?php echo $progress; ?>%">
                                        <?php echo $progress; ?>%
                                    </div