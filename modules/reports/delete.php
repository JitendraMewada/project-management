<?php 
include '../../includes/header.php';
require_once '../../config/roles.php';

// Check permission
if (!hasPermission($current_user['role'], 'reports', 'delete')) {
    header("Location: ../../dashboard/" . $current_user['role'] . ".php");
    exit();
}

$report_id = intval($_GET['id'] ?? 0);
if (!$report_id) {
    header("Location: list.php");
    exit();
}

// Fetch report details
$query = "SELECT r.*, p.name as project_name FROM project_reports r 
          LEFT JOIN projects p ON r.project_id = p.id 
          WHERE r.id = ?";
$stmt = $db->prepare($query);
$stmt->execute([$report_id]);
$report = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$report) {
    header("Location: list.php");
    exit();
}

$error = '';

if ($_POST) {
    if (isset($_POST['confirm_delete'])) {
        try {
            // Delete associated files
            $attachments = json_decode($report['attachments'], true) ?? [];
            foreach ($attachments as $attachment) {
                $file_path = '../../' . $attachment['path'];
                if (file_exists($file_path)) {
                    unlink($file_path);
                }
            }

            // Delete report
            $delete_query = "DELETE FROM project_reports WHERE id = ?";
            $delete_stmt = $db->prepare($delete_query);
            $delete_stmt->execute([$report_id]);

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

$attachments = json_decode($report['attachments'], true) ?? [];
?>

<div class="container-fluid">
    <div class="row">
        <div class="col-md-3">
            <?php include '../../includes/sidebar.php'; ?>
        </div>
        <div class="col-md-9">
            <div class="main-content p-4">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h2><i class="fas fa-trash text-danger"></i> Delete Report</h2>
                    <a href="list.php" class="btn btn-secondary">
                        <i class="fas fa-arrow-left"></i> Back to List
                    </a>
                </div>

                <?php if ($error): ?>
                <div class="alert alert-danger"><?php echo $error; ?></div>
                <?php endif; ?>

                <div class="card border-danger">
                    <div class="card-header bg-danger text-white">
                        <h5 class="mb-0"><i class="fas fa-exclamation-triangle"></i> Confirm Report Deletion</h5>
                    </div>
                    <div class="card-body">
                        <div class="alert alert-warning">
                            <strong>Warning!</strong> This action cannot be undone. Deleting this report will
                            permanently remove it and all associated files.
                        </div>

                        <h6>Report Details:</h6>
                        <div class="row">
                            <div class="col-md-8">
                                <table class="table table-sm">
                                    <tr>
                                        <td><strong>Title:</strong></td>
                                        <td><?php echo htmlspecialchars($report['title']); ?></td>
                                    </tr>
                                    <tr>
                                        <td><strong>Project:</strong></td>
                                        <td><?php echo htmlspecialchars($report['project_name']); ?></td>
                                    </tr>
                                    <tr>
                                        <td><strong>Type:</strong></td>
                                        <td>
                                            <span class="badge bg-<?php 
                                                echo match($report['report_type']) {
                                                    'daily' => 'primary',
                                                    'weekly' => 'success',
                                                    'milestone' => 'warning',
                                                    'final' => 'info',
                                                    default => 'secondary'
                                                };
                                            ?>"><?php echo ucfirst($report['report_type']); ?></span>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td><strong>Report Date:</strong></td>
                                        <td><?php echo date('M d, Y', strtotime($report['report_date'])); ?></td>
                                    </tr>
                                    <tr>
                                        <td><strong>Created:</strong></td>
                                        <td><?php echo date('M d, Y H:i', strtotime($report['created_at'])); ?></td>
                                    </tr>
                                    <tr>
                                        <td><strong>Attachments:</strong></td>
                                        <td><?php echo count($attachments); ?> files</td>
                                    </tr>
                                </table>
                            </div>
                            <div class="col-md-4">
                                <div class="card bg-light">
                                    <div class="card-body text-center">
                                        <i class="fas fa-file-alt fa-3x text-muted mb-3"></i>
                                        <h5>Report Data</h5>
                                        <p class="text-muted">This report and all attachments will be permanently
                                            deleted</p>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <?php if (!empty($attachments)): ?>
                        <hr>
                        <h6>Files to be deleted:</h6>
                        <div class="row">
                            <?php foreach ($attachments as $attachment): ?>
                            <div class="col-md-6 mb-2">
                                <div class="d-flex align-items-center">
                                    <i class="fas fa-file text-danger me-2"></i>
                                    <span><?php echo htmlspecialchars($attachment['filename']); ?></span>
                                    <small class="text-muted ms-2">(<?php echo round($attachment['size'] / 1024, 1); ?>
                                        KB)</small>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        </div>
                        <?php endif; ?>

                        <hr>
                        <h6>Content Preview:</h6>
                        <div class="bg-light p-3 rounded" style="max-height: 200px; overflow-y: auto;">
                            <?php echo nl2br(htmlspecialchars(substr($report['content'], 0, 500))); ?>
                            <?php if (strlen($report['content']) > 500): ?>
                            <p class="text-muted mt-2"><em>... (content truncated)</em></p>
                            <?php endif; ?>
                        </div>

                        <form method="POST" class="mt-4">
                            <div class="form-check mb-3">
                                <input class="form-check-input" type="checkbox" name="confirm_delete" id="confirmDelete"
                                    required>
                                <label class="form-check-label" for="confirmDelete">
                                    I confirm that I want to permanently delete this report and all associated files
                                </label>
                            </div>

                            <div class="d-flex justify-content-between">
                                <a href="list.php" class="btn btn-secondary">
                                    <i class="fas fa-times"></i> Cancel
                                </a>
                                <button type="submit" class="btn btn-danger" id="deleteBtn" disabled>
                                    <i class="fas fa-trash"></i> Delete Report Permanently
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