<?php 
include '../../includes/header.php';
require_once '../../config/roles.php';

// Check permission
if (!hasPermission($current_user['role'], 'tasks', 'create')) {
    header("Location: ../../dashboard/" . $current_user['role'] . ".php");
    exit();
}

// Get project_id from URL if provided
$project_id = intval($_GET['project_id'] ?? 0);

// Fetch projects for dropdown
$projects_query = "SELECT id, name FROM projects WHERE status != 'cancelled' AND status != 'completed' ORDER BY name";
$projects_stmt = $db->prepare($projects_query);
$projects_stmt->execute();
$projects = $projects_stmt->fetchAll(PDO::FETCH_ASSOC);

// Fetch users for assignment
$users_query = "SELECT id, name, role FROM users WHERE status = 'active' ORDER BY name";
$users_stmt = $db->prepare($users_query);
$users_stmt->execute();
$users = $users_stmt->fetchAll(PDO::FETCH_ASSOC);

$message = '';
$error = '';

if ($_POST) {
    try {
        $project_id = intval($_POST['project_id']);
        $title = trim($_POST['title']);
        $description = trim($_POST['description']);
        $assigned_to = !empty($_POST['assigned_to']) ? intval($_POST['assigned_to']) : null;
        $priority = $_POST['priority'];
        $start_date = $_POST['start_date'];
        $due_date = $_POST['due_date'];
        $estimated_hours = !empty($_POST['estimated_hours']) ? floatval($_POST['estimated_hours']) : null;

        // Validation
        if (empty($title) || !$project_id) {
            throw new Exception('Please fill in all required fields.');
        }

        if (!empty($due_date) && !empty($start_date) && strtotime($due_date) < strtotime($start_date)) {
            throw new Exception('Due date cannot be earlier than start date.');
        }

        // Insert task
        $query = "INSERT INTO tasks (project_id, title, description, assigned_to, assigned_by, priority, 
                  start_date, due_date, estimated_hours) 
                  VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";
        
        $stmt = $db->prepare($query);
        $stmt->execute([
            $project_id, $title, $description, $assigned_to, $current_user['id'],
            $priority, $start_date, $due_date, $estimated_hours
        ]);

        $message = 'Task created successfully!';
        header("Location: list.php?success=1");
        exit();

    } catch (Exception $e) {
        $error = $e->getMessage();
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
                    <h2><i class="fas fa-plus"></i> Create New Task</h2>
                    <a href="list.php" class="btn btn-secondary">
                        <i class="fas fa-arrow-left"></i> Back to Tasks
                    </a>
                </div>

                <?php if ($error): ?>
                <div class="alert alert-danger"><?php echo $error; ?></div>
                <?php endif; ?>

                <?php if ($message): ?>
                <div class="alert alert-success"><?php echo $message; ?></div>
                <?php endif; ?>

                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0">Task Details</h5>
                    </div>
                    <div class="card-body">
                        <form method="POST" class="needs-validation" novalidate>
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label class="form-label">Task Title <span class="text-danger">*</span></label>
                                        <input type="text" class="form-control" name="title" required
                                            value="<?php echo htmlspecialchars($_POST['title'] ?? ''); ?>"
                                            placeholder="Enter task title...">
                                        <div class="invalid-feedback">Please provide a task title.</div>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label class="form-label">Project <span class="text-danger">*</span></label>
                                        <select class="form-control" name="project_id" required>
                                            <option value="">Select Project</option>
                                            <?php foreach ($projects as $project): ?>
                                            <option value="<?php echo $project['id']; ?>"
                                                <?php echo ($project_id == $project['id'] || ($_POST['project_id'] ?? '') == $project['id']) ? 'selected' : ''; ?>>
                                                <?php echo htmlspecialchars($project['name']); ?>
                                            </option>
                                            <?php endforeach; ?>
                                        </select>
                                        <div class="invalid-feedback">Please select a project.</div>
                                    </div>
                                </div>
                            </div>

                            <div class="mb-3">
                                <label class="form-label">Description</label>
                                <textarea class="form-control" name="description" rows="4"
                                    placeholder="Task description, requirements, and notes..."><?php echo htmlspecialchars($_POST['description'] ?? ''); ?></textarea>
                            </div>

                            <div class="row">
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label class="form-label">Assign To</label>
                                        <select class="form-control" name="assigned_to">
                                            <option value="">Select Team Member</option>
                                            <?php foreach ($users as $user): ?>
                                            <option value="<?php echo $user['id']; ?>"
                                                <?php echo ($_POST['assigned_to'] ?? '') == $user['id'] ? 'selected' : ''; ?>>
                                                <?php echo htmlspecialchars($user['name']) . ' (' . ucfirst(str_replace('_', ' ', $user['role'])) . ')'; ?>
                                            </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label class="form-label">Priority</label>
                                        <select class="form-control" name="priority">
                                            <option value="low"
                                                <?php echo ($_POST['priority'] ?? 'medium') == 'low' ? 'selected' : ''; ?>>
                                                Low</option>
                                            <option value="medium"
                                                <?php echo ($_POST['priority'] ?? 'medium') == 'medium' ? 'selected' : ''; ?>>
                                                Medium</option>
                                            <option value="high"
                                                <?php echo ($_POST['priority'] ?? 'medium') == 'high' ? 'selected' : ''; ?>>
                                                High</option>
                                            <option value="critical"
                                                <?php echo ($_POST['priority'] ?? 'medium') == 'critical' ? 'selected' : ''; ?>>
                                                Critical</option>
                                        </select>
                                    </div>
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-md-4">
                                    <div class="mb-3">
                                        <label class="form-label">Start Date</label>
                                        <input type="date" class="form-control" name="start_date"
                                            value="<?php echo $_POST['start_date'] ?? date('Y-m-d'); ?>">
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="mb-3">
                                        <label class="form-label">Due Date</label>
                                        <input type="date" class="form-control" name="due_date"
                                            value="<?php echo $_POST['due_date'] ?? ''; ?>">
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="mb-3">
                                        <label class="form-label">Estimated Hours</label>
                                        <input type="number" class="form-control" name="estimated_hours" min="0"
                                            step="0.5" placeholder="e.g., 8.5"
                                            value="<?php echo $_POST['estimated_hours'] ?? ''; ?>">
                                    </div>
                                </div>
                            </div>

                            <div class="d-flex justify-content-between">
                                <a href="list.php" class="btn btn-secondary">Cancel</a>
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-save"></i> Create Task
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include '../../includes/footer.php'; ?>