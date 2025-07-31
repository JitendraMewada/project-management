<?php 
include '../../includes/header.php';
require_once '../../config/roles.php';

// Check permission
if (!hasPermission($current_user['role'], 'tasks', 'delete')) {
    header("Location: ../../dashboard/" . $current_user['role'] . ".php");
    exit();
}

$task_id = intval($_GET['id'] ?? 0);
if (!$task_id) {
    header("Location: list.php");
    exit();
}

// Fetch task details
$query = "SELECT t.*, p.name as project_name FROM tasks t 
          LEFT JOIN projects p ON t.project_id = p.id 
          WHERE t.id = ?";
$stmt = $db->prepare($query);
$stmt->execute([$task_id]);
$task = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$task) {
    header("Location: list.php");
    exit();
}

$error = '';

if ($_POST) {
    if (isset($_POST['confirm_delete'])) {
        try {
            // Delete task
            $delete_query = "DELETE FROM tasks WHERE id = ?";
            $delete_stmt = $db->prepare($delete_query);
            $delete_stmt->execute([$task_id]);

            header("Location: list.php?deleted=1");
            exit();

        } catch (Exception $e) {
            $error = $e->getMessage();
        }
    } else {
        header("Location: list.php");
        exit();
    }
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
                    <h2><i class="fas fa-trash text-danger"></i> Delete Task</h2>
                    <a href="list.php" class="btn btn-secondary">
                        <i class="fas fa-arrow-left"></i> Back to List
                    </a>
                </div>

                <?php if ($error): ?>
                <div class="alert alert-danger"><?php echo $error; ?></div>
                <?php endif; ?>

                <div class="card border-danger">
                    <div class="card-header bg-danger text-white">
                        <h5 class="mb-0"><i class="fas fa-exclamation-triangle"></i> Confirm Task Deletion</h5>
                    </div>
                    <div class="card-body">
                        <div class="alert alert-warning">
                            <strong>Warning!</strong> This action cannot be undone. Deleting this task will permanently
                            remove it from the system.
                        </div>

                        <h6>Task Details:</h6>
                        <div class="row">
                            <div class="col-md-8">
                                <table class="table table-sm">
                                    <tr>
                                        <td><strong>Title:</strong></td>
                                        <td><?php echo htmlspecialchars($task['title']); ?></td>
                                    </tr>
                                    <tr>
                                        <td><strong>Project:</strong></td>
                                        <td><?php echo htmlspecialchars($task['project_name']); ?></td>
                                    </tr>
                                    <tr>
                                        <td><strong>Priority:</strong></td>
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
                                    </tr>
                                    <tr>
                                        <td><strong>Status:</strong></td>
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
                                    </tr>
                                    <tr>
                                        <td><strong>Created:</strong></td>
                                        <td><?php echo date('M d, Y H:i', strtotime($task['created_at'])); ?></td>
                                    </tr>
                                    <tr>
                                        <td><strong>Due Date:</strong></td>
                                        <td><?php echo $task['due_date'] ? date('M d, Y', strtotime($task['due_date'])) : 'Not set'; ?>
                                        </td>
                                    </tr>
                                </table>
                            </div>
                            <div class="col-md-4">
                                <div class="card bg-light">
                                    <div class="card-body text-center">
                                        <i class="fas fa-tasks fa-3x text-muted mb-3"></i>
                                        <h5>Task Information</h5>
                                        <p class="text-muted">This task will be permanently deleted</p>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <?php if ($task['description']): ?>
                        <hr>
                        <h6>Description:</h6>
                        <div class="bg-light p-3 rounded">
                            <?php echo nl2br(htmlspecialchars($task['description'])); ?>
                        </div>
                        <?php endif; ?>

                        <form method="POST" class="mt-4">
                            <div class="form-check mb-3">
                                <input class="form-check-input" type="checkbox" name="confirm_delete" id="confirmDelete"
                                    required>
                                <label class="form-check-label" for="confirmDelete">
                                    I confirm that I want to permanently delete this task
                                </label>
                            </div>

                            <div class="d-flex justify-content-between">
                                <a href="list.php" class="btn btn-secondary">
                                    <i class="fas fa-times"></i> Cancel
                                </a>
                                <button type="submit" class="btn btn-danger" id="deleteBtn" disabled>
                                    <i class="fas fa-trash"></i> Delete Task Permanently
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
// Enable delete button only when confirmed
document.getElementById('confirmDelete').addEventListener('change', function() {
    document.getElementById('deleteBtn').disabled = !this.checked;
});
</script>

<?php include '../../includes/footer.php'; ?>