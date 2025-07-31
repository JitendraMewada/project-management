<?php 
include '../../includes/header.php';
require_once '../../config/roles.php';

// Check permission
if (!hasPermission($current_user['role'], 'projects', 'create')) {
    header("Location: ../../dashboard/" . $current_user['role'] . ".php");
    exit();
}

// Fetch users for assignment dropdowns
$users_query = "SELECT id, name, role FROM users WHERE status = 'active' ORDER BY name";
$users_stmt = $db->prepare($users_query);
$users_stmt->execute();
$users = $users_stmt->fetchAll(PDO::FETCH_ASSOC);

$managers = array_filter($users, fn($u) => $u['role'] == 'manager');
$designers = array_filter($users, fn($u) => $u['role'] == 'designer');
$site_managers = array_filter($users, fn($u) => $u['role'] == 'site_manager');

$message = '';
$error = '';

if ($_POST) {
    try {
        $name = trim($_POST['name']);
        $description = trim($_POST['description']);
        $client_name = trim($_POST['client_name']);
        $client_email = trim($_POST['client_email']);
        $client_phone = trim($_POST['client_phone']);
        $project_type = $_POST['project_type'];
        $budget = !empty($_POST['budget']) ? floatval($_POST['budget']) : null;
        $start_date = $_POST['start_date'];
        $end_date = $_POST['end_date'];
        $manager_id = !empty($_POST['manager_id']) ? intval($_POST['manager_id']) : null;
        $designer_id = !empty($_POST['designer_id']) ? intval($_POST['designer_id']) : null;
        $site_manager_id = !empty($_POST['site_manager_id']) ? intval($_POST['site_manager_id']) : null;

        // Validation
        if (empty($name) || empty($client_name) || empty($project_type)) {
            throw new Exception('Please fill in all required fields.');
        }

        if (!empty($client_email) && !filter_var($client_email, FILTER_VALIDATE_EMAIL)) {
            throw new Exception('Please enter a valid email address.');
        }

        if (!empty($end_date) && !empty($start_date) && strtotime($end_date) < strtotime($start_date)) {
            throw new Exception('End date cannot be earlier than start date.');
        }

        // Insert project
        $query = "INSERT INTO projects (name, description, client_name, client_email, client_phone, 
                  project_type, budget, start_date, end_date, manager_id, designer_id, site_manager_id, created_by) 
                  VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
        
        $stmt = $db->prepare($query);
        $stmt->execute([
            $name, $description, $client_name, $client_email, $client_phone,
            $project_type, $budget, $start_date, $end_date, $manager_id, $designer_id, $site_manager_id, $current_user['id']
        ]);

        $message = 'Project created successfully!';
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
                    <h2><i class="fas fa-plus"></i> Create New Project</h2>
                    <a href="list.php" class="btn btn-secondary">
                        <i class="fas fa-arrow-left"></i> Back to Projects
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
                        <h5 class="mb-0">Project Information</h5>
                    </div>
                    <div class="card-body">
                        <form method="POST" class="needs-validation" novalidate>
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label class="form-label">Project Name <span
                                                class="text-danger">*</span></label>
                                        <input type="text" class="form-control" name="name" required
                                            value="<?php echo htmlspecialchars($_POST['name'] ?? ''); ?>">
                                        <div class="invalid-feedback">Please provide a project name.</div>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label class="form-label">Project Type <span
                                                class="text-danger">*</span></label>
                                        <select class="form-control" name="project_type" required>
                                            <option value="">Select Type</option>
                                            <option value="residential"
                                                <?php echo ($_POST['project_type'] ?? '') == 'residential' ? 'selected' : ''; ?>>
                                                Residential</option>
                                            <option value="commercial"
                                                <?php echo ($_POST['project_type'] ?? '') == 'commercial' ? 'selected' : ''; ?>>
                                                Commercial</option>
                                            <option value="industrial"
                                                <?php echo ($_POST['project_type'] ?? '') == 'industrial' ? 'selected' : ''; ?>>
                                                Industrial</option>
                                        </select>
                                        <div class="invalid-feedback">Please select a project type.</div>
                                    </div>
                                </div>
                            </div>

                            <div class="mb-3">
                                <label class="form-label">Description</label>
                                <textarea class="form-control" name="description" rows="3"
                                    placeholder="Project description and requirements..."><?php echo htmlspecialchars($_POST['description'] ?? ''); ?></textarea>
                            </div>

                            <hr>
                            <h6 class="mb-3">Client Information</h6>

                            <div class="row">
                                <div class="col-md-4">
                                    <div class="mb-3">
                                        <label class="form-label">Client Name <span class="text-danger">*</span></label>
                                        <input type="text" class="form-control" name="client_name" required
                                            value="<?php echo htmlspecialchars($_POST['client_name'] ?? ''); ?>">
                                        <div class="invalid-feedback">Please provide client name.</div>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="mb-3">
                                        <label class="form-label">Client Email</label>
                                        <input type="email" class="form-control" name="client_email"
                                            value="<?php echo htmlspecialchars($_POST['client_email'] ?? ''); ?>">
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="mb-3">
                                        <label class="form-label">Client Phone</label>
                                        <input type="tel" class="form-control" name="client_phone"
                                            value="<?php echo htmlspecialchars($_POST['client_phone'] ?? ''); ?>">
                                    </div>
                                </div>
                            </div>

                            <hr>
                            <h6 class="mb-3">Project Details</h6>

                            <div class="row">
                                <div class="col-md-4">
                                    <div class="mb-3">
                                        <label class="form-label">Budget (₹)</label>
                                        <input type="number" class="form-control" name="budget" min="0" step="0.01"
                                            value="<?php echo htmlspecialchars($_POST['budget'] ?? ''); ?>">
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="mb-3">
                                        <label class="form-label">Start Date</label>
                                        <input type="date" class="form-control" name="start_date"
                                            value="<?php echo htmlspecialchars($_POST['start_date'] ?? ''); ?>">
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="mb-3">
                                        <label class="form-label">End Date</label>
                                        <input type="date" class="form-control" name="end_date"
                                            value="<?php echo htmlspecialchars($_POST['end_date'] ?? ''); ?>">
                                    </div>
                                </div>
                            </div>

                            <hr>
                            <h6 class="mb-3">Team Assignment</h6>

                            <div class="row">
                                <div class="col-md-4">
                                    <div class="mb-3">
                                        <label class="form-label">Project Manager</label>
                                        <select class="form-control" name="manager_id">
                                            <option value="">Select Manager</option>
                                            <?php foreach ($managers as $manager): ?>
                                            <option value="<?php echo $manager['id']; ?>"
                                                <?php echo ($_POST['manager_id'] ?? '') == $manager['id'] ? 'selected' : ''; ?>>
                                                <?php echo htmlspecialchars($manager['name']); ?>
                                            </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="mb-3">
                                        <label class="form-label">Designer</label>
                                        <select class="form-control" name="designer_id">
                                            <option value="">Select Designer</option>
                                            <?php foreach ($designers as $designer): ?>
                                            <option value="<?php echo $designer['id']; ?>"
                                                <?php echo ($_POST['designer_id'] ?? '') == $designer['id'] ? 'selected' : ''; ?>>
                                                <?php echo htmlspecialchars($designer['name']); ?>
                                            </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="mb-3">
                                        <label class="form-label">Site Manager</label>
                                        <select class="form-control" name="site_manager_id">
                                            <option value="">Select Site Manager</option>
                                            <?php foreach ($site_managers as $site_manager): ?>
                                            <option value="<?php echo $site_manager['id']; ?>"
                                                <?php echo ($_POST['site_manager_id'] ?? '') == $site_manager['id'] ? 'selected' : ''; ?>>
                                                <?php echo htmlspecialchars($site_manager['name']); ?>
                                            </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                </div>
                            </div>

                            <div class="d-flex justify-content-between">
                                <a href="list.php" class="btn btn-secondary">Cancel</a>
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-save"></i> Create Project
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