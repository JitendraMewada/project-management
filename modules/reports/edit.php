<?php 
include '../../includes/header.php';
require_once '../../config/roles.php';

// Check permission
if (!hasPermission($current_user['role'], 'reports', 'update')) {
    header("Location: ../../dashboard/" . $current_user['role'] . ".php");
    exit();
}

$report_id = intval($_GET['id'] ?? 0);
if (!$report_id) {
    header("Location: list.php");
    exit();
}

// Fetch report details
$query = "SELECT * FROM project_reports WHERE id = ?";
$stmt = $db->prepare($query);
$stmt->execute([$report_id]);
$report = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$report) {
    header("Location: list.php");
    exit();
}

// Check if user can edit this report
if ($current_user['role'] != 'admin' && $current_user['role'] != 'manager' && 
    $report['created_by'] != $current_user['id']) {
    header("Location: list.php");
    exit();
}

// Fetch projects for dropdown
$projects_query = "SELECT id, name, client_name FROM projects ORDER BY name";
$projects_stmt = $db->prepare($projects_query);
$projects_stmt->execute();
$projects = $projects_stmt->fetchAll(PDO::FETCH_ASSOC);

$message = '';
$error = '';

if ($_POST) {
    try {
        $project_id = intval($_POST['project_id']);
        $report_type = $_POST['report_type'];
        $title = trim($_POST['title']);
        $content = trim($_POST['content']);
        $report_date = $_POST['report_date'];
        
        // Get existing attachments
        $existing_attachments = json_decode($report['attachments'], true) ?? [];
        $attachments = $existing_attachments;

        // Validation
        if (empty($title) || empty($content) || !$project_id || empty($report_type)) {
            throw new Exception('Please fill in all required fields.');
        }

        // Handle new file attachments
        if (isset($_FILES['new_attachments']) && !empty($_FILES['new_attachments']['name'][0])) {
            $upload_dir = '../../uploads/reports/';
            if (!is_dir($upload_dir)) {
                mkdir($upload_dir, 0755, true);
            }
            
            $allowed_extensions = ['pdf', 'doc', 'docx', 'jpg', 'jpeg', 'png', 'gif', 'xls', 'xlsx'];
            
            foreach ($_FILES['new_attachments']['name'] as $key => $filename) {
                if ($_FILES['new_attachments']['error'][$key] == 0) {
                    $file_extension = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
                    
                    if (in_array($file_extension, $allowed_extensions)) {
                        $new_filename = uniqid() . '_' . $filename;
                        $filepath = $upload_dir . $new_filename;
                        
                        if (move_uploaded_file($_FILES['new_attachments']['tmp_name'][$key], $filepath)) {
                            $attachments[] = [
                                'filename' => $filename,
                                'path' => 'uploads/reports/' . $new_filename,
                                'size' => $_FILES['new_attachments']['size'][$key],
                                'type' => $_FILES['new_attachments']['type'][$key]
                            ];
                        }
                    }
                }
            }
        }

        // Handle attachment deletions
        if (isset($_POST['delete_attachments'])) {
            $delete_indices = $_POST['delete_attachments'];
            foreach ($delete_indices as $index) {
                if (isset($attachments[$index])) {
                    // Delete file from server
                    $file_path = '../../' . $attachments[$index]['path'];
                    if (file_exists($file_path)) {
                        unlink($file_path);
                    }
                    unset($attachments[$index]);
                }
            }
            $attachments = array_values($attachments); // Re-index array
        }

        // Update report
        $query = "UPDATE project_reports SET project_id = ?, report_type = ?, title = ?, content = ?, 
                  attachments = ?, report_date = ?, updated_at = CURRENT_TIMESTAMP WHERE id = ?";
        
        $stmt = $db->prepare($query);
        $stmt->execute([
            $project_id, $report_type, $title, $content, 
            json_encode($attachments), $report_date, $report_id
        ]);

        $message = 'Report updated successfully!';
        
        // Refresh report data
        $stmt = $db->prepare("SELECT * FROM project_reports WHERE id = ?");
        $stmt->execute([$report_id]);
        $report = $stmt->fetch(PDO::FETCH_ASSOC);

    } catch (Exception $e) {
        $error = $e->getMessage();
    }
}

// Parse existing attachments
$existing_attachments = json_decode($report['attachments'], true) ?? [];
?>

<div class="container-fluid">
    <div class="row">
        <div class="col-md-3">
            <?php include '../../includes/sidebar.php'; ?>
        </div>
        <div class="col-md-9">
            <div class="main-content p-4">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h2><i class="fas fa-edit"></i> Edit Report</h2>
                    <div class="btn-group">
                        <a href="view.php?id=<?php echo $report['id']; ?>" class="btn btn-secondary">
                            <i class="fas fa-eye"></i> View Report
                        </a>
                        <a href="list.php" class="btn btn-outline-secondary">
                            <i class="fas fa-arrow-left"></i> Back to List
                        </a>
                    </div>
                </div>

                <?php if ($error): ?>
                <div class="alert alert-danger"><?php echo $error; ?></div>
                <?php endif; ?>

                <?php if ($message): ?>
                <div class="alert alert-success"><?php echo $message; ?></div>
                <?php endif; ?>

                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0">Edit Report: <?php echo htmlspecialchars($report['title']); ?></h5>
                    </div>
                    <div class="card-body">
                        <form method="POST" enctype="multipart/form-data" class="needs-validation" novalidate>
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label class="form-label">Project <span class="text-danger">*</span></label>
                                        <select class="form-control" name="project_id" required>
                                            <option value="">Select Project</option>
                                            <?php foreach ($projects as $project): ?>
                                            <option value="<?php echo $project['id']; ?>"
                                                <?php echo $report['project_id'] == $project['id'] ? 'selected' : ''; ?>>
                                                <?php echo htmlspecialchars($project['name']) . ' - ' . htmlspecialchars($project['client_name']); ?>
                                            </option>
                                            <?php endforeach; ?>
                                        </select>
                                        <div class="invalid-feedback">Please select a project.</div>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label class="form-label">Report Type <span class="text-danger">*</span></label>
                                        <select class="form-control" name="report_type" required>
                                            <option value="">Select Type</option>
                                            <option value="daily"
                                                <?php echo $report['report_type'] == 'daily' ? 'selected' : ''; ?>>Daily
                                                Report</option>
                                            <option value="weekly"
                                                <?php echo $report['report_type'] == 'weekly' ? 'selected' : ''; ?>>
                                                Weekly Report</option>
                                            <option value="milestone"
                                                <?php echo $report['report_type'] == 'milestone' ? 'selected' : ''; ?>>
                                                Milestone Report</option>
                                            <option value="final"
                                                <?php echo $report['report_type'] == 'final' ? 'selected' : ''; ?>>Final
                                                Report</option>
                                        </select>
                                        <div class="invalid-feedback">Please select a report type.</div>
                                    </div>
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-md-8">
                                    <div class="mb-3">
                                        <label class="form-label">Report Title <span
                                                class="text-danger">*</span></label>
                                        <input type="text" class="form-control" name="title" required
                                            value="<?php echo htmlspecialchars($report['title']); ?>">
                                        <div class="invalid-feedback">Please provide a report title.</div>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="mb-3">
                                        <label class="form-label">Report Date</label>
                                        <input type="date" class="form-control" name="report_date"
                                            value="<?php echo $report['report_date']; ?>">
                                    </div>
                                </div>
                            </div>

                            <div class="mb-3">
                                <label class="form-label">Report Content <span class="text-danger">*</span></label>
                                <textarea class="form-control" name="content" rows="12"
                                    required><?php echo htmlspecialchars($report['content']); ?></textarea>
                                <div class="invalid-feedback">Please provide report content.</div>
                            </div>

                            <!-- Existing Attachments -->
                            <?php if (!empty($existing_attachments)): ?>
                            <div class="mb-3">
                                <label class="form-label">Existing Attachments</label>
                                <div class="row">
                                    <?php foreach ($existing_attachments as $index => $attachment): ?>
                                    <div class="col-md-4 mb-2">
                                        <div class="card border">
                                            <div class="card-body p-2">
                                                <div class="d-flex justify-content-between align-items-center">
                                                    <div>
                                                        <small
                                                            class="fw-bold"><?php echo htmlspecialchars($attachment['filename']); ?></small>
                                                        <br><small
                                                            class="text-muted"><?php echo round($attachment['size'] / 1024, 1); ?>
                                                            KB</small>
                                                    </div>
                                                    <div class="form-check">
                                                        <input class="form-check-input" type="checkbox"
                                                            name="delete_attachments[]" value="<?php echo $index; ?>"
                                                            id="delete_<?php echo $index; ?>">
                                                        <label class="form-check-label text-danger"
                                                            for="delete_<?php echo $index; ?>">
                                                            <small>Delete</small>
                                                        </label>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                            <?php endif; ?>

                            <!-- New Attachments -->
                            <div class="mb-3">
                                <label class="form-label">Add New Attachments</label>
                                <input type="file" class="form-control" name="new_attachments[]" multiple
                                    accept=".pdf,.doc,.docx,.jpg,.jpeg,.png,.gif,.xls,.xlsx">
                                <div class="form-text">
                                    Supported formats: PDF, DOC, DOCX, JPG, PNG, GIF, XLS, XLSX
                                </div>
                            </div>

                            <div class="d-flex justify-content-between">
                                <a href="view.php?id=<?php echo $report['id']; ?>" class="btn btn-secondary">Cancel</a>
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-save"></i> Update Report
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
// Auto-resize textarea
document.querySelector('textarea[name="content"]').addEventListener('input', function() {
    this.style.height = 'auto';
    this.style.height = this.scrollHeight + 'px';
});

// Confirm deletion of attachments
document.querySelectorAll('input[name="delete_attachments[]"]').forEach(checkbox => {
    checkbox.addEventListener('change', function() {
        if (this.checked) {
            if (!confirm(
                    'Are you sure you want to delete this attachment? This action cannot be undone.')) {
                this.checked = false;
            }
        }
    });
});
</script>

<?php include '../../includes/footer.php'; ?>