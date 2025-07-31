<?php 
include '../../includes/header.php';
require_once '../../config/roles.php';

// Check permission
if (!hasPermission($current_user['role'], 'reports', 'read')) {
    header("Location: ../../dashboard/" . $current_user['role'] . ".php");
    exit();
}

$report_id = intval($_GET['id'] ?? 0);
if (!$report_id) {
    header("Location: list.php");
    exit();
}

// Fetch report details
$query = "SELECT r.*, p.name as project_name, p.client_name, u.name as created_by_name
          FROM project_reports r 
          LEFT JOIN projects p ON r.project_id = p.id 
          LEFT JOIN users u ON r.created_by = u.id
          WHERE r.id = ?";
$stmt = $db->prepare($query);
$stmt->execute([$report_id]);
$report = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$report) {
    header("Location: list.php");
    exit();
}

// Check if user can view this report
if ($current_user['role'] != 'admin' && $current_user['role'] != 'manager' && 
    $report['created_by'] != $current_user['id']) {
    header("Location: list.php");
    exit();
}

// Parse attachments
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
                    <h2><i class="fas fa-file-alt"></i> Report Details</h2>
                    <div class="btn-group">
                        <a href="list.php" class="btn btn-secondary">
                            <i class="fas fa-arrow-left"></i> Back to List
                        </a>
                        <?php if (hasPermission($current_user['role'], 'reports', 'update') || $report['created_by'] == $current_user['id']): ?>
                        <a href="edit.php?id=<?php echo $report['id']; ?>" class="btn btn-primary">
                            <i class="fas fa-edit"></i> Edit Report
                        </a>
                        <?php endif; ?>
                        <button class="btn btn-success" onclick="exportToPDF()">
                            <i class="fas fa-file-pdf"></i> Export PDF
                        </button>
                        <button class="btn btn-info" onclick="printReport()">
                            <i class="fas fa-print"></i> Print
                        </button>
                    </div>
                </div>

                <!-- Report Header -->
                <div class="card mb-4">
                    <div class="card-header">
                        <div class="d-flex justify-content-between align-items-center">
                            <h4 class="mb-0"><?php echo htmlspecialchars($report['title']); ?></h4>
                            <span class="badge bg-<?php 
                                echo match($report['report_type']) {
                                    'daily' => 'primary',
                                    'weekly' => 'success',
                                    'milestone' => 'warning',
                                    'final' => 'info',
                                    default => 'secondary'
                                };
                            ?> fs-6"><?php echo ucfirst($report['report_type']); ?> Report</span>
                        </div>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6">
                                <table class="table table-sm table-borderless">
                                    <tr>
                                        <td><strong>Project:</strong></td>
                                        <td>
                                            <a href="../projects/view.php?id=<?php echo $report['project_id']; ?>"
                                                class="text-decoration-none">
                                                <?php echo htmlspecialchars($report['project_name']); ?>
                                            </a>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td><strong>Client:</strong></td>
                                        <td><?php echo htmlspecialchars($report['client_name']); ?></td>
                                    </tr>
                                    <tr>
                                        <td><strong>Report Type:</strong></td>
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
                                </table>
                            </div>
                            <div class="col-md-6">
                                <table class="table table-sm table-borderless">
                                    <tr>
                                        <td><strong>Report Date:</strong></td>
                                        <td><?php echo date('M d, Y', strtotime($report['report_date'])); ?></td>
                                    </tr>
                                    <tr>
                                        <td><strong>Created By:</strong></td>
                                        <td><?php echo htmlspecialchars($report['created_by_name']); ?></td>
                                    </tr>
                                    <tr>
                                        <td><strong>Created On:</strong></td>
                                        <td><?php echo date('M d, Y H:i', strtotime($report['created_at'])); ?></td>
                                    </tr>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Report Content -->
                <div class="card mb-4" id="reportContent">
                    <div class="card-header">
                        <h6 class="mb-0"><i class="fas fa-file-text"></i> Report Content</h6>
                    </div>
                    <div class="card-body">
                        <div class="report-content">
                            <?php echo nl2br(htmlspecialchars($report['content'])); ?>
                        </div>
                    </div>
                </div>

                <!-- Attachments -->
                <?php if (!empty($attachments)): ?>
                <div class="card mb-4">
                    <div class="card-header">
                        <h6 class="mb-0"><i class="fas fa-paperclip"></i> Attachments
                            (<?php echo count($attachments); ?>)</h6>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <?php foreach ($attachments as $attachment): ?>
                            <div class="col-md-4 mb-3">
                                <div class="card border">
                                    <div class="card-body text-center p-3">
                                        <div class="mb-2">
                                            <?php
                                            $ext = strtolower(pathinfo($attachment['filename'], PATHINFO_EXTENSION));
                                            $icon_class = match($ext) {
                                                'pdf' => 'fas fa-file-pdf text-danger',
                                                'doc', 'docx' => 'fas fa-file-word text-primary',
                                                'xls', 'xlsx' => 'fas fa-file-excel text-success',
                                                'jpg', 'jpeg', 'png', 'gif' => 'fas fa-file-image text-info',
                                                default => 'fas fa-file text-secondary'
                                            };
                                            ?>
                                            <i class="<?php echo $icon_class; ?> fa-3x"></i>
                                        </div>
                                        <h6 class="card-title"
                                            title="<?php echo htmlspecialchars($attachment['filename']); ?>">
                                            <?php echo htmlspecialchars(strlen($attachment['filename']) > 20 ? substr($attachment['filename'], 0, 20) . '...' : $attachment['filename']); ?>
                                        </h6>
                                        <p class="card-text text-muted small">
                                            <?php echo round($attachment['size'] / 1024, 1); ?> KB
                                        </p>
                                        <div class="btn-group btn-group-sm">
                                            <a href="../../<?php echo htmlspecialchars($attachment['path']); ?>"
                                                class="btn btn-outline-primary" target="_blank">
                                                <i class="fas fa-eye"></i> View
                                            </a>
                                            <a href="../../<?php echo htmlspecialchars($attachment['path']); ?>"
                                                class="btn btn-outline-success"
                                                download="<?php echo htmlspecialchars($attachment['filename']); ?>">
                                                <i class="fas fa-download"></i> Download
                                            </a>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
                <?php endif; ?>

                <!-- Quick Actions -->
                <div class="card">
                    <div class="card-header">
                        <h6 class="mb-0"><i class="fas fa-bolt"></i> Quick Actions</h6>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-4">
                                <div class="d-grid">
                                    <button class="btn btn-outline-success" onclick="shareReport()">
                                        <i class="fas fa-share-alt"></i> Share Report
                                    </button>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="d-grid">
                                    <a href="../projects/view.php?id=<?php echo $report['project_id']; ?>"
                                        class="btn btn-outline-primary">
                                        <i class="fas fa-project-diagram"></i> View Project
                                    </a>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="d-grid">
                                    <button class="btn btn-outline-info" onclick="generateSummary()">
                                        <i class="fas fa-chart-line"></i> Generate Summary
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Share Modal -->
<div class="modal fade" id="shareModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Share Report</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="mb-3">
                    <label class="form-label">Share via Email</label>
                    <div class="input-group">
                        <input type="email" class="form-control" id="shareEmail" placeholder="Enter email address">
                        <button class="btn btn-primary" onclick="sendReportEmail()">
                            <i class="fas fa-envelope"></i> Send
                        </button>
                    </div>
                </div>
                <div class="mb-3">
                    <label class="form-label">Direct Link</label>
                    <div class="input-group">
                        <input type="text" class="form-control" id="reportLink"
                            value="<?php echo "http://" . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI']; ?>" readonly>
                        <button class="btn btn-secondary" onclick="copyLink()">
                            <i class="fas fa-copy"></i> Copy
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
.report-content {
    line-height: 1.8;
    font-size: 1.1em;
    color: #333;
}

@media print {

    .btn-group,
    .card-header,
    .navbar,
    .sidebar {
        display: none !important;
    }

    .card {
        border: none !important;
        box-shadow: none !important;
    }

    .main-content {
        margin-left: 0 !important;
    }
}

.attachment-preview {
    max-height: 200px;
    overflow: hidden;
}
</style>

<script>
function printReport() {
    window.print();
}

function exportToPDF() {
    // This would integrate with a PDF generation library
    alert('PDF export functionality will be implemented with a PDF library like jsPDF or server-side PDF generation.');
}

function shareReport() {
    const shareModal = new bootstrap.Modal(document.getElementById('shareModal'));
    shareModal.show();
}

function copyLink() {
    const linkInput = document.getElementById('reportLink');
    linkInput.select();
    document.execCommand('copy');

    const btn = event.target;
    const originalText = btn.innerHTML;
    btn.innerHTML = '<i class="fas fa-check"></i> Copied!';
    btn.className = 'btn btn-success';

    setTimeout(() => {
        btn.innerHTML = originalText;
        btn.className = 'btn btn-secondary';
    }, 2000);
}

function sendReportEmail() {
    const email = document.getElementById('shareEmail').value;
    if (email) {
        // This would integrate with email service
        fetch('share_report.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({
                    report_id: <?php echo $report['id']; ?>,
                    email: email
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert('Report shared successfully!');
                    bootstrap.Modal.getInstance(document.getElementById('shareModal')).hide();
                } else {
                    alert('Error sharing report: ' + data.message);
                }
            });
    }
}

function generateSummary() {
    // This would use AI/ML to generate report summary
    alert('AI-powered report summary feature will be implemented in future updates.');
}
</script>

<?php include '../../includes/footer.php'; ?>