<?php
include '../../includes/header.php';
require_once '../../config/roles.php';

// Only allow users with 'reports' create permission (e.g. admin, manager)
if (!hasPermission($current_user['role'], 'reports', 'create')) {
    header("Location: ../../dashboard/" . $current_user['role'] . ".php");
    exit();
}

$error = '';
$message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $name = trim($_POST['name'] ?? '');
        $description = trim($_POST['description'] ?? '');
        $type = $_POST['type'] ?? '';
        $content = trim($_POST['content'] ?? '');

        // Basic validation
        if ($name === '') {
            throw new Exception('Template name is required.');
        }
        if ($type === '') {
            throw new Exception('Template type is required.');
        }
        if ($content === '') {
            throw new Exception('Template content cannot be empty.');
        }

        // Check for duplicate template name
        $checkStmt = $db->prepare("SELECT COUNT(*) FROM report_templates WHERE name = ?");
        $checkStmt->execute([$name]);
        if ($checkStmt->fetchColumn() > 0) {
            throw new Exception('A template with this name already exists.');
        }

        // Insert new template
        $insertStmt = $db->prepare("
            INSERT INTO report_templates (name, description, type, content, created_at, updated_at)
            VALUES (?, ?, ?, ?, NOW(), NOW())
        ");
        $insertStmt->execute([$name, $description, $type, $content, $created_at, $updated_at]);

        $message = 'Report template created successfully!';
        // Redirect to list page with success message
        header("Location: templates.php?success=1");
        exit();

    } catch (Exception $e) {
        $error = $e->getMessage();
    }
}

// For template types, you can define allowable options here
$templateTypes = [
    'progress' => 'Progress Report',
    'financial' => 'Financial Report',
    'quality' => 'Quality Report',
    'safety' => 'Safety Report',
    'completion' => 'Completion Report',
];
?>

<div class="container-fluid">
    <div class="row">
        <div class="col-md-3">
            <?php include '../../includes/sidebar.php'; ?>
        </div>

        <div class="col-md-9">
            <div class="main-content p-4">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h2><i class="fas fa-file-alt"></i> Create New Report Template</h2>
                    <a href="templates.php" class="btn btn-secondary">
                        <i class="fas fa-arrow-left"></i> Back to Templates
                    </a>
                </div>

                <?php if ($error): ?>
                <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
                <?php endif; ?>

                <?php if ($message): ?>
                <div class="alert alert-success"><?= htmlspecialchars($message) ?></div>
                <?php endif; ?>

                <form method="POST" class="needs-validation" novalidate>
                    <div class="mb-3">
                        <label for="name" class="form-label">Template Name <span class="text-danger">*</span></label>
                        <input type="text" id="name" name="name" class="form-control" required
                            value="<?= htmlspecialchars($_POST['name'] ?? '') ?>">
                        <div class="invalid-feedback">Please enter a name for the template.</div>
                    </div>

                    <div class="mb-3">
                        <label for="description" class="form-label">Description (optional)</label>
                        <textarea id="description" name="description" rows="3"
                            class="form-control"><?= htmlspecialchars($_POST['description'] ?? '') ?></textarea>
                    </div>

                    <div class="mb-3">
                        <label for="type" class="form-label">Report Type <span class="text-danger">*</span></label>
                        <select id="type" name="type" class="form-select" required>
                            <option value="">Select type</option>
                            <?php foreach ($templateTypes as $key => $label): ?>
                            <option value="<?= $key ?>" <?= (($_POST['type'] ?? '') === $key) ? 'selected' : '' ?>>
                                <?= htmlspecialchars($label) ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                        <div class="invalid-feedback">Please select a report type.</div>
                    </div>

                    <div class="mb-3">
                        <label for="content" class="form-label">Template Content <span
                                class="text-danger">*</span></label>
                        <textarea id="content" name="content" rows="10" class="form-control"
                            required><?= htmlspecialchars($_POST['content'] ?? '') ?></textarea>
                        <div class="invalid-feedback">Please enter the template content.</div>
                        <small class="form-text text-muted">
                            You can use HTML here. This template will be used to generate report content.
                        </small>
                    </div>

                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save"></i> Save Template
                    </button>
                    <a href="templates.php" class="btn btn-secondary ms-2">Cancel</a>
                </form>

            </div>
        </div>
    </div>
</div>

<script>
// Bootstrap form validation
(() => {
    'use strict';
    const forms = document.querySelectorAll('.needs-validation');
    forms.forEach(form => {
        form.addEventListener('submit', event => {
            if (!form.checkValidity()) {
                event.preventDefault();
                event.stopPropagation();
            }
            form.classList.add('was-validated');
        }, false);
    });
})();
</script>

<?php include '../../includes/footer.php'; ?>