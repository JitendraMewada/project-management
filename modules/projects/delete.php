<?php 
include '../../includes/header.php';
require_once '../../config/roles.php';

// Check permission
if (!hasPermission($current_user['role'], 'projects', 'delete')) {
    header("Location: ../../dashboard/" . $current_user['role'] . ".php");
    exit();
}

$project_id = intval($_GET['id'] ?? 0);
if (!$project_id) {
    header("Location: list.php");
    exit();
}

// Fetch project details
$query = "SELECT * FROM projects WHERE id = ?";
$stmt = $db->prepare($query);
$stmt->execute([$project_id]);
$project = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$project) {
    header("Location: list.php");
    exit();
}

$error = '';
$message = '';

if ($_POST) {
    if (isset($_POST['confirm_delete'])) {
        try {
            // Check if project has tasks
            $tasks_query = "SELECT COUNT(*) FROM tasks WHERE project_id = ?";
            $tasks_stmt = $db->prepare($tasks_query);
            $tasks_stmt->execute([$project_id]);
            $task_count = $tasks_stmt->fetchColumn();

            if ($task_count > 0 && !isset($_POST['force_delete'])) {
                throw new Exception("This project has {$task_count} associated tasks. Please check 'Force Delete' to proceed.");
            }

            // Begin transaction
            $db->beginTransaction();

            // Delete related records
            $db->prepare("DELETE FROM project_materials WHERE project_id = ?")->execute([$project_id]);
            $db->prepare("DELETE FROM project_reports WHERE project_id = ?")->execute([$project_id]);
            $db->prepare("DELETE FROM tasks WHERE project_id = ?")->execute([$project_id]);
            $db->prepare("DELETE FROM projects WHERE id = ?")->execute([$project_id]);

            $db->commit();

            header("Location: list.php?deleted=1");
            exit();

        } catch (Exception $e) {
            $db->rollBack();
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
                    <h2><i class="fas fa-trash text-danger"></i> Delete Project</h2>
                    <a href="list.php" class="btn btn-secondary">
                        <i class="fas fa-arrow-left"></i> Back to List
                    </a>
                </div>

                <?php if ($error): ?>
                <div class="alert alert-danger"><?php echo $error; ?></div>
                <?php endif; ?>

                <div class="card border-danger">
                    <div class="card-header bg-danger text-white">
                        <h5 class="mb-0"><i class="fas fa-exclamation-triangle"></i> Confirm Project Deletion</h5>
                    </div>
                    <div class="card-body">
                        <div class="alert alert-warning">
                            <strong>Warning!</strong> This action cannot be undone. Deleting this project will also
                            remove:
                            <ul class="mb-0 mt-2">
                                <li>All associated tasks</li>
                                <li>All project materials</li>
                                <li>All project reports</li>
                                <li>All project history</li>
                            </ul>
                        </div>

                        <div class="row">
                            <div class="col-md-6">
                                <h6>Project Details:</h6>
                                <table class="table table-sm">
                                    <tr>
                                        <td><strong>Name:</strong></td>
                                        <td><?php echo htmlspecialchars($project['name']); ?></td>
                                    </tr>
                                    <tr>
                                        <td><strong>Client:</strong></td>
                                        <td><?php echo htmlspecialchars($project['client_name']); ?></td>
                                    </tr>
                                    <tr>
                                        <td><strong>Type:</strong></td>
                                        <td><?php echo ucfirst($project['project_type']); ?></td>
                                    </tr>
                                    <tr>
                                        <td><strong>Status:</strong></td>
                                        <td>
                                            <span
                                                class="badge bg-<?php 
                                                echo match($project['status']) {
                                                    'completed' => 'success',
                                                    'in_progress' => 'warning',
                                                    'planning' => 'info',
                                                    'on_hold' => 'secondary',
                                                    'cancelled' => 'danger',
                                                    default => 'secondary'
                                                };
                                            ?>"><?php echo ucfirst(str_replace('_', ' ', $project['status'])); ?></span>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td><strong>Created:</strong></td>
                                        <td><?php echo date('M d, Y', strtotime($project['created_at'])); ?></td>
                                    </tr>
                                </table>
                            </div>
                            <div class="col-md-6">
                                <?php
                                // Get related data counts
                                $tasks_count = $db->prepare("SELECT COUNT(*) FROM tasks WHERE project_id = ?");
                                $tasks_count->execute([$project_id]);
                                $task_count = $tasks_count->fetchColumn();

                                $materials_count = $db->prepare("SELECT COUNT(*) FROM project_materials WHERE project_id = ?");
                                $materials_count->execute([$project_id]);
                                $material_count = $materials_count->fetchColumn();

                                $reports_count = $db->prepare("SELECT COUNT(*) FROM project_reports WHERE project_id = ?");
                                $reports_count->execute([$project_id]);
                                $report_count = $reports_count->fetchColumn();
                                ?>
                                <h6>Related Data:</h6>
                                <div class="row text-center">
                                    <div class="col-4">
                                        <div class="card bg-light">
                                            <div class="card-body">
                                                <h4 class="text-primary"><?php echo $task_count; ?></h4>
                                                <small>Tasks</small>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-4">
                                        <div class="card bg-light">
                                            <div class="card-body">
                                                <h4 class="text-info"><?php echo $material_count; ?></h4>
                                                <small>Materials</small>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-4">
                                        <div class="card bg-light">
                                            <div class="card-body">
                                                <h4 class="text-success"><?php echo $report_count; ?></h4>
                                                <small>Reports</small>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <form method="POST" class="mt-4">
                            <?php if ($task_count > 0): ?>
                            <div class="form-check mb-3">
                                <input class="form-check-input" type="checkbox" name="force_delete" id="forceDelete">
                                <label class="form-check-label text-danger" for="forceDelete">
                                    <strong>Force Delete:</strong> I understand this will delete all related data
                                </label>
                            </div>
                            <?php endif; ?>

                            <div class="form-check mb-3">
                                <input class="form-check-input" type="checkbox" name="confirm_delete" id="confirmDelete"
                                    required>
                                <label class="form-check-label" for="confirmDelete">
                                    I confirm that I want to permanently delete this project
                                </label>
                            </div>

                            <div class="d-flex justify-content-between">
                                <a href="list.php" class="btn btn-secondary">
                                    <i class="fas fa-times"></i> Cancel
                                </a>
                                <button type="submit" class="btn btn-danger" id="deleteBtn" disabled>
                                    <i class="fas fa-trash"></i> Delete Project Permanently
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
    const deleteBtn = document.getElementById('deleteBtn');
    const forceDelete = document.getElementById('forceDelete');

    if (this.checked && (!forceDelete || forceDelete.checked)) {
        deleteBtn.disabled = false;
    } else {
        deleteBtn.disabled = true;
    }
});

// Handle force delete checkbox
const forceDeleteCheck = document.getElementById('forceDelete');
if (forceDeleteCheck) {
    forceDeleteCheck.addEventListener('change', function() {
        const deleteBtn = document.getElementById('deleteBtn');
        const confirmDelete = document.getElementById('confirmDelete');

        if (confirmDelete.checked && this.checked) {
            deleteBtn.disabled = false;
        } else {
            deleteBtn.disabled = true;
        }
    });
}
</script>

<?php include '../../includes/footer.php'; ?>