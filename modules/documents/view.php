<?php 
include '../../includes/header.php';
require_once '../../config/roles.php';

$document_id = intval($_GET['id'] ?? 0);
if (!$document_id) {
    header("Location: list.php");
    exit();
}

// Fetch document details
$query = "SELECT d.*, p.name as project_name, u.name as uploaded_by_name
          FROM project_documents d 
          LEFT JOIN projects p ON d.project_id = p.id 
          LEFT JOIN users u ON d.uploaded_by = u.id
          WHERE d.id = ?";
$stmt = $db->prepare($query);
$stmt->execute([$document_id]);
$document = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$document) {
    header("Location: list.php");
    exit();
}

// Check permissions
if (!hasPermission($current_user['role'], 'documents', 'read')) {
    header("Location: ../../dashboard/" . $current_user['role'] . ".php");
    exit();
}

$file_extension = strtolower(pathinfo($document['original_name'], PATHINFO_EXTENSION));
$is_image = in_array($file_extension, ['jpg', 'jpeg', 'png', 'gif', 'bmp']);
$is_pdf = $file_extension === 'pdf';
?>

<div class="container-fluid">
    <div class="row">
        <div class="col-md-3">
            <?php include '../../includes/sidebar.php'; ?>
        </div>
        <div class="col-md-9">
            <div class="main-content p-4">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h2><i class="fas fa-file"></i> Document Viewer</h2>
                    <div class="btn-group">
                        <a href="list.php" class="btn btn-secondary">
                            <i class="fas fa-arrow-left"></i> Back to Documents
                        </a>
                        <a href="download.php?id=<?php echo $document['id']; ?>" class="btn btn-success">
                            <i class="fas fa-download"></i> Download
                        </a>
                        <button class="btn btn-info" onclick="printDocument()">
                            <i class="fas fa-print"></i> Print
                        </button>
                    </div>
                </div>

                <!-- Document Information -->
                <div class="card mb-4">
                    <div class="card-header">
                        <h5 class="mb-0"><?php echo htmlspecialchars($document['original_name']); ?></h5>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6">
                                <table class="table table-borderless table-sm">
                                    <tr>
                                        <td><strong>Project:</strong></td>
                                        <td><?php echo htmlspecialchars($document['project_name']); ?></td>
                                    </tr>
                                    <tr>
                                        <td><strong>Category:</strong></td>
                                        <td>
                                            <span
                                                class="badge bg-<?php echo getCategoryColor($document['category']); ?>">
                                                <?php echo ucfirst($document['category']); ?>
                                            </span>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td><strong>File Size:</strong></td>
                                        <td><?php echo formatFileSize($document['file_size']); ?></td>
                                    </tr>
                                </table>
                            </div>
                            <div class="col-md-6">
                                <table class="table table-borderless table-sm">
                                    <tr>
                                        <td><strong>Uploaded By:</strong></td>
                                        <td><?php echo htmlspecialchars($document['uploaded_by_name']); ?></td>
                                    </tr>
                                    <tr>
                                        <td><strong>Upload Date:</strong></td>
                                        <td><?php echo date('M d, Y H:i', strtotime($document['created_at'])); ?></td>
                                    </tr>
                                    <tr>
                                        <td><strong>Downloads:</strong></td>
                                        <td><?php echo $document['download_count']; ?> times</td>
                                    </tr>
                                </table>
                            </div>
                        </div>

                        <?php if ($document['description']): ?>
                        <hr>
                        <h6>Description:</h6>
                        <p><?php echo nl2br(htmlspecialchars($document['description'])); ?></p>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Document Preview -->
                <div class="card">
                    <div class="card-header">
                        <h6 class="mb-0">Document Preview</h6>
                    </div>
                    <div class="card-body">
                        <?php if ($is_image): ?>
                        <div class="text-center">
                            <img src="../../<?php echo htmlspecialchars($document['file_path']); ?>" class="img-fluid"
                                style="max-height: 600px;"
                                alt="<?php echo htmlspecialchars($document['original_name']); ?>">
                        </div>
                        <?php elseif ($is_pdf): ?>
                        <div class="pdf-viewer">
                            <iframe src="../../<?php echo htmlspecialchars($document['file_path']); ?>#toolbar=1"
                                width="100%" height="600px" frameborder="0">
                                <p>Your browser does not support PDFs.
                                    <a href="download.php?id=<?php echo $document['id']; ?>">Download the PDF</a>
                                </p>
                            </iframe>
                        </div>
                        <?php else: ?>
                        <div class="text-center py-5">
                            <i class="<?php echo getFileIcon($file_extension); ?> fa-5x mb-3"></i>
                            <h4><?php echo htmlspecialchars($document['original_name']); ?></h4>
                            <p class="text-muted">This file type cannot be previewed in the browser.</p>
                            <a href="download.php?id=<?php echo $document['id']; ?>" class="btn btn-primary">
                                <i class="fas fa-download"></i> Download to View
                            </a>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
function printDocument() {
    window.print();
}

// Track document view for analytics
fetch('track_view.php', {
    method: 'POST',
    headers: {
        'Content-Type': 'application/json'
    },
    body: JSON.stringify({
        document_id: <?php echo $document['id']; ?>
    })
});
</script>

<?php 
function getCategoryColor($category) {
    return match($category) {
        'design' => 'primary',
        'contract' => 'success',
        'invoice' => 'warning',
        'report' => 'info',
        'photo' => 'secondary',
        default => 'light'
    };
}

function formatFileSize($bytes) {
    if ($bytes >= 1048576) {
        return round($bytes / 1048576, 1) . ' MB';
    } elseif ($bytes >= 1024) {
        return round($bytes / 1024, 1) . ' KB';
    } else {
        return $bytes . ' B';
    }
}

function getFileIcon($extension) {
    return match(strtolower($extension)) {
        'pdf' => 'fas fa-file-pdf text-danger',
        'doc', 'docx' => 'fas fa-file-word text-primary',
        'xls', 'xlsx' => 'fas fa-file-excel text-success',
        'ppt', 'pptx' => 'fas fa-file-powerpoint text-warning',
        'jpg', 'jpeg', 'png', 'gif' => 'fas fa-file-image text-info',
        'dwg', 'skp' => 'fas fa-drafting-compass text-secondary',
        'zip', 'rar' => 'fas fa-file-archive text-dark',
        default => 'fas fa-file text-muted'
    };
}

include '../../includes/footer.php'; 
?>