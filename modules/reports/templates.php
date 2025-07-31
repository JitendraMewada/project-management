<?php
include '../../includes/header.php';
require_once '../../config/roles.php';

// Only allow users with 'reports' create permission (e.g. admin, managers)
if (!hasPermission($current_user['role'], 'reports', 'create')) {
    header("Location: ../../dashboard/" . $current_user['role'] . ".php");
    exit();
}

$message = '';
$error = '';

// Handle template deletion if requested (e.g., via GET param delete_id)
if (isset($_GET['delete_id'])) {
    $delete_id = intval($_GET['delete_id']);
    try {
        $stmt = $db->prepare("DELETE FROM report_templates WHERE id = ?");
        $stmt->execute([$delete_id]);
        $message = "Template deleted successfully.";
    } catch (Exception $e) {
        $error = "Error deleting template: " . $e->getMessage();
    }
}

// Fetch all templates
$stmt = $db->prepare("SELECT * FROM report_templates ORDER BY created_at DESC");
$stmt->execute();
$templates = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<div class="container-fluid">
    <div class="row">
        <div class="col-md-3">
            <?php include '../../includes/sidebar.php'; ?>
        </div>
        <div class="col-md-9">
            <div class="main-content p-4">

                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h2><i class="fas fa-file-alt"></i> Report Templates</h2>
                    <a href="create_template.php" class="btn btn-primary">
                        <i class="fas fa-plus"></i> New Template
                    </a>
                </div>

                <?php if ($error): ?>
                <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
                <?php endif; ?>

                <?php if ($message): ?>
                <div class="alert alert-success"><?= htmlspecialchars($message) ?></div>
                <?php endif; ?>

                <?php if (empty($templates)): ?>
                <div class="text-center py-5">
                    <i class="fas fa-file-alt fa-4x text-muted mb-3"></i>
                    <h5 class="text-muted">No report templates found</h5>
                    <p>Create a new template to get started.</p>
                    <a href="create_template.php" class="btn btn-primary">
                        <i class="fas fa-plus"></i> Add Template
                    </a>
                </div>
                <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>Template Name</th>
                                <th>Description</th>
                                <th>Type</th>
                                <th>Created At</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($templates as $template): ?>
                            <tr>
                                <td><?= htmlspecialchars($template['name']) ?></td>
                                <td><?= htmlspecialchars(substr($template['description'], 0, 80)) ?></td>
                                <td><?= ucfirst(str_replace('_', ' ', $template['type'])) ?></td>
                                <td><?= date('M d, Y', strtotime($template['created_at'])) ?></td>
                                <td>
                                    <a href="edit_template.php?id=<?= $template['id'] ?>"
                                        class="btn btn-sm btn-outline-success" title="Edit">
                                        <i class="fas fa-edit"></i>
                                    </a>
                                    <a href="?delete_id=<?= $template['id'] ?>"
                                        onclick="return confirm('Are you sure you want to delete this template?')"
                                        class="btn btn-sm btn-outline-danger" title="Delete">
                                        <i class="fas fa-trash"></i>
                                    </a>
                                    <a href="view_template.php?id=<?= $template['id'] ?>"
                                        class="btn btn-sm btn-outline-primary" title="View">
                                        <i class="fas fa-eye"></i>
                                    </a>
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
</div>

<?php include '../../includes/footer.php'; ?>