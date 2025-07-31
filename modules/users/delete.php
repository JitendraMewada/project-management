<?php 
include '../../includes/header.php';
require_once '../../config/roles.php';

// Check permission
if (!hasPermission($current_user['role'], 'users', 'delete')) {
    header("Location: ../../dashboard/" . $current_user['role'] . ".php");
    exit();
}

$user_id = intval($_GET['id'] ?? 0);
if (!$user_id) {
    header("Location: list.php");
    exit();
}

// Prevent self-deletion
if ($user_id == $current_user['id']) {
    header("Location: list.php?error=cannot_delete_self");
    exit();
}

// Fetch user details
$query = "SELECT * FROM users WHERE id = ?";
$stmt = $db->prepare($query);
$stmt->execute([$user_id]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$user) {
    header("Location: list.php");
    exit();
}

$error = '';

if ($_POST) {
    if (isset($_POST['confirm_delete'])) {
        try {
            // Check user's involvement in projects and tasks
            $projects_query = "SELECT COUNT(*) FROM projects WHERE manager_id = ? OR designer_id = ? OR site_manager_id = ?";
            $projects_stmt = $db->prepare($projects_query);
            $projects_stmt->execute([$user_id, $user_id, $user_id]);
            $project_count = $projects_stmt->fetchColumn();

            $tasks_query = "SELECT COUNT(*) FROM tasks WHERE assigned_to = ? OR assigned_by = ?";
            $tasks_stmt = $db->prepare($tasks_query);
            $tasks_stmt->execute([$user_id, $user_id]);
            $task_count = $tasks_stmt->fetchColumn();

            if (($project_count > 0 || $task_count > 0) && !isset($_POST['reassign_data'])) {
                throw new Exception("This user is involved in {$project_count} projects and {$task_count} tasks. Please choose how to handle their data.");
            }

            // Begin transaction
            $db->beginTransaction();

            if (isset($_POST['reassign_data'])) {
                $reassign_to = intval($_POST['reassign_to']);
                
                if ($reassign_to) {
                    // Reassign projects
                    $db->prepare("UPDATE projects SET manager_id = ? WHERE manager_id = ?")->execute([$reassign_to, $user_id]);
                    $db->prepare("UPDATE projects SET designer_id = ? WHERE designer_id = ?")->execute([$reassign_to, $user_id]);
                    $db->prepare("UPDATE projects SET site_manager_id = ? WHERE site_manager_id = ?")->execute([$reassign_to, $user_id]);
                    
                    // Reassign tasks
                    $db->prepare("UPDATE tasks SET assigned_to = ? WHERE assigned_to = ?")->execute([$reassign_to, $user_id]);
                    $db->prepare("UPDATE tasks SET assigned_by = ? WHERE assigned_by = ?")->execute([$reassign_to, $user_id]);
                } else {
                    // Set to NULL (unassigned)
                    $db->prepare("UPDATE projects SET manager_id = NULL WHERE manager_id = ?")->execute([$user_id]);
                    $db->prepare("UPDATE projects SET designer_id = NULL WHERE designer_id = ?")->execute([$user_id]);
                    $db->prepare("UPDATE projects SET site_manager_id = NULL WHERE site_manager_id = ?")->execute([$user_id]);
                    
                    $db->prepare("UPDATE tasks SET assigned_to = NULL WHERE assigned_to = ?")->execute([$user_id]);
                }
            }

            // Delete user's profile image
            if ($user['profile_image'] && file_exists('../../' . $user['profile_image'])) {
                unlink('../../' . $user['profile_image']);
            }

            // Delete user
            $db->prepare("DELETE FROM users WHERE id = ?")->execute([$user_id]);

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

// Get project and task counts for display
$projects_query = "SELECT COUNT(*) as count FROM projects WHERE manager_id = ? OR designer_id = ? OR site_manager_id = ?";
$projects_stmt = $db->prepare($projects_query);
$projects_stmt->execute([$user_id, $user_id, $user_id]);
$project_count = $projects_stmt->fetch(PDO::FETCH_ASSOC)['count'];

$tasks_query = "SELECT COUNT(*) as count FROM tasks WHERE assigned_to = ? OR assigned_by = ?";
$tasks_stmt = $db->prepare($tasks_query);
$tasks_stmt->execute([$user_id, $user_id]);
$task_count = $tasks_stmt->fetch(PDO::FETCH_ASSOC)['count'];

// Fetch other users for reassignment
$other_users_query = "SELECT id, name, role FROM users WHERE id != ? AND status = 'active' ORDER BY name";
$other_users_stmt = $db->prepare($other_users_query);
$other_users_stmt->execute([$user_id]);
$other_users = $other_users_stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<div class="container-fluid">
    <div class="row">
        <div class="col-md-3">
            <?php include '../../includes/sidebar.php'; ?>
        </div>
        <div class="col-md-9">
            <div class="main-content p-4">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h2><i class="fas fa-user-times text-danger"></i> Delete User</h2>
                    <a href="list.php" class="btn btn-secondary">
                        <i class="fas fa-arrow-left"></i> Back to List
                    </a>
                </div>

                <?php if ($error): ?>
                <div class="alert alert-danger"><?php echo $error; ?></div>
                <?php endif; ?>

                <div class="card border-danger">
                    <div class="card-header bg-danger text-white">
                        <h5 class="mb-0"><i class="fas fa-exclamation-triangle"></i> Confirm User Deletion</h5>
                    </div>
                    <div class="card-body">
                        <div class="alert alert-danger">
                            <strong>Critical Warning!</strong> This action cannot be undone. Deleting this user will
                            permanently remove their account and affect system data.
                        </div>

                        <div class="row">
                            <div class="col-md-4">
                                <div class="text-center mb-3">
                                    <?php if ($user['profile_image']): ?>
                                    <img src="../../<?php echo htmlspecialchars($user['profile_image']); ?>"
                                        class="rounded-circle img-thumbnail" width="100" height="100" alt="Profile">
                                    <?php else: ?>
                                    <div class="rounded-circle bg-secondary text-white d-flex align-items-center justify-content-center mx-auto"
                                        style="width: 100px; height: 100px; font-size: 32px;">
                                        <?php echo strtoupper(substr($user['name'], 0, 1)); ?>
                                    </div>
                                    <?php endif; ?>
                                    <h5 class="mt-2"><?php echo htmlspecialchars($user['name']); ?></h5>
                                    <span class="badge bg-<?php 
                                        echo match($user['role']) {
                                            'admin' => 'danger',
                                            'manager' => 'primary',
                                            'designer' => 'success',
                                            'site_manager' => 'warning',
                                            'site_coordinator' => 'info',
                                            'site_supervisor' => 'secondary',
                                            default => 'secondary'
                                        };
                                    ?>"><?php echo ucfirst(str_replace('_', ' ', $user['role'])); ?></span>
                                </div>
                            </div>
                            <div class="col-md-8">
                                <h6>User Details:</h6>
                                <table class="table table-sm">
                                    <tr>
                                        <td><strong>Name:</strong></td>
                                        <td><?php echo htmlspecialchars($user['name']); ?></td>
                                    </tr>
                                    <tr>
                                        <td><strong>Email:</strong></td>
                                        <td><?php echo htmlspecialchars($user['email']); ?></td>
                                    </tr>
                                    <tr>
                                        <td><strong>Role:</strong></td>
                                        <td>
                                            <span class="badge bg-<?php 
                                                echo match($user['role']) {
                                                    'admin' => 'danger',
                                                    'manager' => 'primary',
                                                    'designer' => 'success',
                                                    'site_manager' => 'warning',
                                                    'site_coordinator' => 'info',
                                                    'site_supervisor' => 'secondary',
                                                    default => 'secondary'
                                                };
                                            ?>"><?php echo ucfirst(str_replace('_', ' ', $user['role'])); ?></span>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td><strong>Status:</strong></td>
                                        <td>
                                            <span
                                                class="badge bg-<?php echo $user['status'] == 'active' ? 'success' : 'secondary'; ?>">
                                                <?php echo ucfirst($user['status']); ?>
                                            </span>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td><strong>Joined:</strong></td>
                                        <td><?php echo date('M d, Y', strtotime($user['created_at'])); ?></td>
                                    </tr>
                                </table>
                            </div>
                        </div>

                        <!-- Data Impact -->
                        <div class="row mb-4">
                            <div class="col-md-6">
                                <div class="card bg-light">
                                    <div class="card-body text-center">
                                        <h4 class="text-primary"><?php echo $project_count; ?></h4>
                                        <p>Associated Projects</p>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="card bg-light">
                                    <div class="card-body text-center">
                                        <h4 class="text-info"><?php echo $task_count; ?></h4>
                                        <p>Associated Tasks</p>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <form method="POST">
                            <?php if ($project_count > 0 || $task_count > 0): ?>
                            <div class="card border-warning mb-3">
                                <div class="card-header bg-warning">
                                    <h6 class="mb-0 text-dark"><i class="fas fa-exclamation-triangle"></i> Data
                                        Reassignment Required</h6>
                                </div>
                                <div class="card-body">
                                    <p>This user has associated projects and tasks. Choose how to handle their data:</p>

                                    <div class="form-check mb-2">
                                        <input class="form-check-input" type="radio" name="reassign_data"
                                            id="reassignToUser" value="user">
                                        <label class="form-check-label" for="reassignToUser">
                                            <strong>Reassign to another user</strong>
                                        </label>
                                    </div>

                                    <div class="mb-3 ms-4" id="reassignUserSelect" style="display: none;">
                                        <select class="form-control" name="reassign_to">
                                            <option value="">Select User</option>
                                            <?php foreach ($other_users as $other_user): ?>
                                            <option value="<?php echo $other_user['id']; ?>">
                                                <?php echo htmlspecialchars($other_user['name']) . ' (' . ucfirst(str_replace('_', ' ', $other_user['role'])) . ')'; ?>
                                            </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>

                                    <div class="form-check">
                                        <input class="form-check-input" type="radio" name="reassign_data"
                                            id="setUnassigned" value="unassigned">
                                        <label class="form-check-label" for="setUnassigned">
                                            <strong>Set as unassigned</strong> (projects and tasks will have no assigned
                                            user)
                                        </label>
                                    </div>
                                </div>
                            </div>
                            <?php endif; ?>

                            <div class="form-check mb-3">
                                <input class="form-check-input" type="checkbox" name="confirm_delete" id="confirmDelete"
                                    required>
                                <label class="form-check-label" for="confirmDelete">
                                    I understand that this action cannot be undone and confirm that I want to
                                    permanently delete this user
                                </label>
                            </div>

                            <div class="d-flex justify-content-between">
                                <a href="list.php" class="btn btn-secondary">
                                    <i class="fas fa-times"></i> Cancel
                                </a>
                                <button type="submit" class="btn btn-danger" id="deleteBtn" disabled>
                                    <i class="fas fa-user-times"></i> Delete User Permanently
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
// Show/hide reassignment options
document.querySelectorAll('input[name="reassign_data"]').forEach(radio => {
    radio.addEventListener('change', function() {
        const selectDiv = document.getElementById('reassignUserSelect');
        if (this.id === 'reassignToUser') {
            selectDiv.style.display = 'block';
            selectDiv.querySelector('select').required = true;
        } else {
            selectDiv.style.display = 'none';
            selectDiv.querySelector('select').required = false;
        }
    });
});

// Enable delete button only when confirmed
document.getElementById('confirmDelete').addEventListener('change', function() {
    const deleteBtn = document.getElementById('deleteBtn');
    const hasData = <?php echo ($project_count > 0 || $task_count > 0) ? 'true' : 'false'; ?>;

    if (hasData) {
        const reassignRadio = document.querySelector('input[name="reassign_data"]:checked');
        if (this.checked && reassignRadio) {
            deleteBtn.disabled = false;
        } else {
            deleteBtn.disabled = true;
        }
    } else {
        deleteBtn.disabled = !this.checked;
    }
});

// Check reassignment selection
document.querySelectorAll('input[name="reassign_data"]').forEach(radio => {
    radio.addEventListener('change', function() {
        const confirmCheck = document.getElementById('confirmDelete');
        const deleteBtn = document.getElementById('deleteBtn');

        if (confirmCheck.checked) {
            deleteBtn.disabled = false;
        }
    });
});
</script>

<?php include '../../includes/footer.php'; ?>