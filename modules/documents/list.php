<?php 
include '../../includes/header.php';
require_once '../../config/roles.php';

// Check permission
if (!hasPermission($current_user['role'], 'documents', 'read')) {
    header("Location: ../../dashboard/" . $current_user['role'] . ".php");
    exit();
}

// Fetch projects for filtering
$projects_query = "SELECT id, name FROM projects ORDER BY name";
$projects_stmt = $db->prepare($projects_query);
$projects_stmt->execute();
$projects = $projects_stmt->fetchAll(PDO::FETCH_ASSOC);

// Fetch documents with project and user information
$documents_query = "SELECT d.*, p.name as project_name, u.name as uploaded_by_name 
                    FROM project_documents d 
                    LEFT JOIN projects p ON d.project_id = p.id 
                    LEFT JOIN users u ON d.uploaded_by = u.id 
                    ORDER BY d.created_at DESC";
$documents_stmt = $db->prepare($documents_query);
$documents_stmt->execute();
$documents = $documents_stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<div class="container-fluid">
    <div class="row">
        <div class="col-md-3">
            <?php include '../../includes/sidebar.php'; ?>
        </div>
        <div class="col-md-9">
            <div class="main-content p-4">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h2><i class="fas fa-folder-open"></i> Document Management</h2>
                    <div class="btn-group">
                        <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#uploadModal">
                            <i class="fas fa-cloud-upload-alt"></i> Upload Documents
                        </button>
                        <button class="btn btn-outline-success" onclick="toggleView('grid')">
                            <i class="fas fa-th"></i> Grid View
                        </button>
                        <button class="btn btn-outline-info" onclick="toggleView('list')" id="listViewBtn">
                            <i class="fas fa-list"></i> List View
                        </button>
                    </div>
                </div>

                <!-- Filter and Search Bar -->
                <div class="card mb-4">
                    <div class="card-body">
                        <div class="row align-items-center">
                            <div class="col-md-3">
                                <select class="form-control" id="projectFilter" onchange="filterDocuments()">
                                    <option value="">All Projects</option>
                                    <?php foreach ($projects as $project): ?>
                                    <option value="<?php echo $project['id']; ?>">
                                        <?php echo htmlspecialchars($project['name']); ?>
                                    </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-3">
                                <select class="form-control" id="typeFilter" onchange="filterDocuments()">
                                    <option value="">All Types</option>
                                    <option value="design">Design Files</option>
                                    <option value="contract">Contracts</option>
                                    <option value="invoice">Invoices</option>
                                    <option value="report">Reports</option>
                                    <option value="photo">Photos</option>
                                    <option value="other">Other</option>
                                </select>
                            </div>
                            <div class="col-md-4">
                                <input type="text" class="form-control" id="searchInput"
                                    placeholder="Search documents..." onkeyup="filterDocuments()">
                            </div>
                            <div class="col-md-2">
                                <div class="dropdown">
                                    <button class="btn btn-outline-secondary dropdown-toggle w-100"
                                        data-bs-toggle="dropdown">
                                        <i class="fas fa-sort"></i> Sort
                                    </button>
                                    <ul class="dropdown-menu">
                                        <li><a class="dropdown-item" href="#" onclick="sortDocuments('name')">Name</a>
                                        </li>
                                        <li><a class="dropdown-item" href="#" onclick="sortDocuments('date')">Date</a>
                                        </li>
                                        <li><a class="dropdown-item" href="#" onclick="sortDocuments('size')">Size</a>
                                        </li>
                                        <li><a class="dropdown-item" href="#" onclick="sortDocuments('type')">Type</a>
                                        </li>
                                    </ul>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Document Statistics -->
                <div class="row mb-4">
                    <div class="col-md-3">
                        <div class="card bg-primary text-white">
                            <div class="card-body text-center">
                                <h4><?php echo count($documents); ?></h4>
                                <p class="mb-0">Total Documents</p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card bg-success text-white">
                            <div class="card-body text-center">
                                <h4><?php echo count(array_filter($documents, fn($d) => $d['category'] == 'design')); ?>
                                </h4>
                                <p class="mb-0">Design Files</p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card bg-warning text-white">
                            <div class="card-body text-center">
                                <h4><?php echo count(array_filter($documents, fn($d) => $d['category'] == 'photo')); ?>
                                </h4>
                                <p class="mb-0">Photos</p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card bg-info text-white">
                            <div class="card-body text-center">
                                <h4 id="totalSize"><?php 
                                $totalSize = array_sum(array_column($documents, 'file_size'));
                                echo round($totalSize / (1024 * 1024), 1) . ' MB';
                                ?></h4>
                                <p class="mb-0">Storage Used</p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Grid View -->
                <div id="gridView" class="documents-grid">
                    <div class="row" id="documentsGrid">
                        <?php foreach ($documents as $doc): ?>
                        <div class="col-xl-2 col-lg-3 col-md-4 col-sm-6 mb-4 document-item"
                            data-project="<?php echo $doc['project_id']; ?>" data-type="<?php echo $doc['category']; ?>"
                            data-name="<?php echo strtolower($doc['file_name']); ?>">
                            <div class="card document-card h-100">
                                <div class="document-preview">
                                    <?php
                                    $ext = strtolower(pathinfo($doc['file_name'], PATHINFO_EXTENSION));
                                    $isImage = in_array($ext, ['jpg', 'jpeg', 'png', 'gif']);
                                    ?>

                                    <?php if ($isImage): ?>
                                    <img src="../../<?php echo htmlspecialchars($doc['file_path']); ?>"
                                        class="card-img-top document-thumbnail"
                                        alt="<?php echo htmlspecialchars($doc['file_name']); ?>"
                                        onclick="previewDocument(<?php echo $doc['id']; ?>)">
                                    <?php else: ?>
                                    <div class="document-icon" onclick="previewDocument(<?php echo $doc['id']; ?>)">
                                        <i class="<?php echo getFileIcon($ext); ?> fa-4x"></i>
                                    </div>
                                    <?php endif; ?>

                                    <div class="document-overlay">
                                        <div class="btn-group">
                                            <button class="btn btn-light btn-sm"
                                                onclick="downloadDocument(<?php echo $doc['id']; ?>)">
                                                <i class="fas fa-download"></i>
                                            </button>
                                            <button class="btn btn-light btn-sm"
                                                onclick="shareDocument(<?php echo $doc['id']; ?>)">
                                                <i class="fas fa-share"></i>
                                            </button>
                                            <?php if (hasPermission($current_user['role'], 'documents', 'delete')): ?>
                                            <button class="btn btn-danger btn-sm"
                                                onclick="deleteDocument(<?php echo $doc['id']; ?>)">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </div>

                                <div class="card-body p-2">
                                    <h6 class="card-title mb-1"
                                        title="<?php echo htmlspecialchars($doc['file_name']); ?>">
                                        <?php echo htmlspecialchars(strlen($doc['file_name']) > 15 ? substr($doc['file_name'], 0, 15) . '...' : $doc['file_name']); ?>
                                    </h6>
                                    <small class="text-muted d-block">
                                        <?php echo htmlspecialchars($doc['project_name']); ?>
                                    </small>
                                    <small class="text-muted d-block">
                                        <?php echo round($doc['file_size'] / 1024, 1); ?> KB
                                    </small>
                                    <small class="text-muted">
                                        <?php echo date('M d, Y', strtotime($doc['created_at'])); ?>
                                    </small>
                                </div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>

                <!-- List View -->
                <div id="listView" class="documents-list" style="display: none;">
                    <div class="card">
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-hover">
                                    <thead>
                                        <tr>
                                            <th>Document</th>
                                            <th>Project</th>
                                            <th>Category</th>
                                            <th>Size</th>
                                            <th>Uploaded By</th>
                                            <th>Date</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody id="documentsTable">
                                        <?php foreach ($documents as $doc): ?>
                                        <tr class="document-row" data-project="<?php echo $doc['project_id']; ?>"
                                            data-type="<?php echo $doc['category']; ?>"
                                            data-name="<?php echo strtolower($doc['file_name']); ?>">
                                            <td>
                                                <div class="d-flex align-items-center">
                                                    <i
                                                        class="<?php echo getFileIcon(pathinfo($doc['file_name'], PATHINFO_EXTENSION)); ?> me-2"></i>
                                                    <div>
                                                        <h6 class="mb-0">
                                                            <?php echo htmlspecialchars($doc['file_name']); ?></h6>
                                                        <?php if ($doc['description']): ?>
                                                        <small
                                                            class="text-muted"><?php echo htmlspecialchars($doc['description']); ?></small>
                                                        <?php endif; ?>
                                                    </div>
                                                </div>
                                            </td>
                                            <td><?php echo htmlspecialchars($doc['project_name']); ?></td>
                                            <td>
                                                <span
                                                    class="badge bg-<?php echo getCategoryColor($doc['category']); ?>">
                                                    <?php echo ucfirst($doc['category']); ?>
                                                </span>
                                            </td>
                                            <td><?php echo formatFileSize($doc['file_size']); ?></td>
                                            <td><?php echo htmlspecialchars($doc['uploaded_by_name']); ?></td>
                                            <td><?php echo date('M d, Y H:i', strtotime($doc['created_at'])); ?></td>
                                            <td>
                                                <div class="btn-group btn-group-sm">
                                                    <button class="btn btn-outline-primary"
                                                        onclick="previewDocument(<?php echo $doc['id']; ?>)">
                                                        <i class="fas fa-eye"></i>
                                                    </button>
                                                    <button class="btn btn-outline-success"
                                                        onclick="downloadDocument(<?php echo $doc['id']; ?>)">
                                                        <i class="fas fa-download"></i>
                                                    </button>
                                                    <button class="btn btn-outline-info"
                                                        onclick="shareDocument(<?php echo $doc['id']; ?>)">
                                                        <i class="fas fa-share"></i>
                                                    </button>
                                                    <?php if (hasPermission($current_user['role'], 'documents', 'delete')): ?>
                                                    <button class="btn btn-outline-danger"
                                                        onclick="deleteDocument(<?php echo $doc['id']; ?>)">
                                                        <i class="fas fa-trash"></i>
                                                    </button>
                                                    <?php endif; ?>
                                                </div>
                                            </td>
                                        </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Upload Modal -->
<div class="modal fade" id="uploadModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Upload Documents</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="uploadForm" enctype="multipart/form-data">
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label">Project <span class="text-danger">*</span></label>
                                <select class="form-control" name="project_id" required>
                                    <option value="">Select Project</option>
                                    <?php foreach ($projects as $project): ?>
                                    <option value="<?php echo $project['id']; ?>">
                                        <?php echo htmlspecialchars($project['name']); ?>
                                    </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label">Category</label>
                                <select class="form-control" name="category">
                                    <option value="design">Design Files</option>
                                    <option value="contract">Contracts</option>
                                    <option value="invoice">Invoices</option>
                                    <option value="report">Reports</option>
                                    <option value="photo">Photos</option>
                                    <option value="other">Other</option>
                                </select>
                            </div>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Description</label>
                        <textarea class="form-control" name="description" rows="2"
                            placeholder="Brief description of the documents..."></textarea>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Files <span class="text-danger">*</span></label>
                        <div class="upload-area" id="uploadArea">
                            <input type="file" class="form-control" name="files[]" multiple required id="fileInput"
                                accept=".pdf,.doc,.docx,.xls,.xlsx,.ppt,.pptx,.jpg,.jpeg,.png,.gif,.dwg,.skp">
                            <div class="upload-text">
                                <i class="fas fa-cloud-upload-alt fa-3x mb-3"></i>
                                <p>Drag and drop files here or click to browse</p>
                                <small class="text-muted">
                                    Supported: PDF, DOC, XLS, PPT, Images, CAD files (Max: 10MB per file)
                                </small>
                            </div>
                        </div>
                    </div>

                    <div id="filePreview" class="file-preview"></div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-upload"></i> Upload Documents
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php
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
?>

<style>
.document-card {
    transition: transform 0.3s ease, box-shadow 0.3s ease;
    cursor: pointer;
}

.document-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 8px 25px rgba(0, 0, 0, 0.15);
}

.document-preview {
    position: relative;
    height: 150px;
    overflow: hidden;
    background: #f8f9fa;
}

.document-thumbnail {
    width: 100%;
    height: 100%;
    object-fit: cover;
}

.document-icon {
    height: 100%;
    display: flex;
    align-items: center;
    justify-content: center;
    color: #6c757d;
}

.document-overlay {
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: rgba(0, 0, 0, 0.7);
    display: flex;
    align-items: center;
    justify-content: center;
    opacity: 0;
    transition: opacity 0.3s ease;
}

.document-card:hover .document-overlay {
    opacity: 1;
}

.upload-area {
    border: 2px dashed #dee2e6;
    border-radius: 8px;
    padding: 40px;
    text-align: center;
    background: #f8f9fa;
    transition: all 0.3s ease;
    position: relative;
}

.upload-area:hover {
    border-color: #007bff;
    background: #e7f3ff;
}

.upload-area.dragover {
    border-color: #007bff;
    background: #e7f3ff;
    transform: scale(1.02);
}

.upload-text {
    pointer-events: none;
}

.file-preview {
    max-height: 200px;
    overflow-y: auto;
}

.file-item {
    display: flex;
    align-items: center;
    padding: 8px;
    border: 1px solid #dee2e6;
    border-radius: 4px;
    margin-bottom: 5px;
    background: white;
}

.file-item .btn-remove {
    margin-left: auto;
}

.documents-grid .col-xl-2 {
    flex: 0 0 16.666667%;
    max-width: 16.666667%;
}

@media (max-width: 768px) {
    .documents-grid .col-xl-2 {
        flex: 0 0 50%;
        max-width: 50%;
    }
}
</style>

<script>
let currentView = 'grid';

// File upload handling
document.getElementById('fileInput').addEventListener('change', handleFileSelect);
document.getElementById('uploadArea').addEventListener('dragover', handleDragOver);
document.getElementById('uploadArea').addEventListener('drop', handleDrop);
document.getElementById('uploadArea').addEventListener('dragenter', handleDragEnter);
document.getElementById('uploadArea').addEventListener('dragleave', handleDragLeave);

function handleFileSelect(e) {
    const files = e.target.files;
    showFilePreview(files);
}

function handleDragOver(e) {
    e.preventDefault();
}

function handleDragEnter(e) {
    e.preventDefault();
    e.target.classList.add('dragover');
}

function handleDragLeave(e) {
    e.preventDefault();
    e.target.classList.remove('dragover');
}

function handleDrop(e) {
    e.preventDefault();
    e.target.classList.remove('dragover');

    const files = e.dataTransfer.files;
    document.getElementById('fileInput').files = files;
    showFilePreview(files);
}

function showFilePreview(files) {
    const preview = document.getElementById('filePreview');
    preview.innerHTML = '';

    Array.from(files).forEach((file, index) => {
        const fileItem = document.createElement('div');
        fileItem.className = 'file-item';
        fileItem.innerHTML = `
            <i class="${getFileIconClass(file.name)} me-2"></i>
            <span class="flex-fill">${file.name} (${formatBytes(file.size)})</span>
            <button type="button" class="btn btn-sm btn-outline-danger btn-remove" onclick="removeFile(${index})">
                <i class="fas fa-times"></i>
            </button>
        `;
        preview.appendChild(fileItem);
    });
}

function removeFile(index) {
    const input = document.getElementById('fileInput');
    const dt = new DataTransfer();

    Array.from(input.files).forEach((file, i) => {
        if (i !== index) dt.items.add(file);
    });

    input.files = dt.files;
    showFilePreview(input.files);
}

function getFileIconClass(filename) {
    const ext = filename.split('.').pop().toLowerCase();
    const icons = {
        'pdf': 'fas fa-file-pdf text-danger',
        'doc': 'fas fa-file-word text-primary',
        'docx': 'fas fa-file-word text-primary',
        'xls': 'fas fa-file-excel text-success',
        'xlsx': 'fas fa-file-excel text-success',
        'ppt': 'fas fa-file-powerpoint text-warning',
        'pptx': 'fas fa-file-powerpoint text-warning',
        'jpg': 'fas fa-file-image text-info',
        'jpeg': 'fas fa-file-image text-info',
        'png': 'fas fa-file-image text-info',
        'gif': 'fas fa-file-image text-info'
    };
    return icons[ext] || 'fas fa-file text-muted';
}

function formatBytes(bytes) {
    if (bytes === 0) return '0 Bytes';
    const k = 1024;
    const sizes = ['Bytes', 'KB', 'MB', 'GB'];
    const i = Math.floor(Math.log(bytes) / Math.log(k));
    return parseFloat((bytes / Math.pow(k, i)).toFixed(1)) + ' ' + sizes[i];
}

// Upload form submission
document.getElementById('uploadForm').addEventListener('submit', function(e) {
    e.preventDefault();

    const formData = new FormData(this);
    const submitBtn = this.querySelector('button[type="submit"]');

    submitBtn.disabled = true;
    submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Uploading...';

    fetch('upload.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                bootstrap.Modal.getInstance(document.getElementById('uploadModal')).hide();
                location.reload();
            } else {
                alert('Upload failed: ' + data.message);
            }
        })
        .catch(error => {
            alert('Upload error: ' + error.message);
        })
        .finally(() => {
            submitBtn.disabled = false;
            submitBtn.innerHTML = '<i class="fas fa-upload"></i> Upload Documents';
        });
});

// View toggle functions
function toggleView(view) {
    currentView = view;

    if (view === 'grid') {
        document.getElementById('gridView').style.display = 'block';
        document.getElementById('listView').style.display = 'none';
        document.getElementById('listViewBtn').classList.remove('active');
        event.target.classList.add('active');
    } else {
        document.getElementById('gridView').style.display = 'none';
        document.getElementById('listView').style.display = 'block';
        document.querySelector('button[onclick="toggleView(\'grid\')"]').classList.remove('active');
        event.target.classList.add('active');
    }
}

// Filter and search functions
function filterDocuments() {
    const projectFilter = document.getElementById('projectFilter').value;
    const typeFilter = document.getElementById('typeFilter').value;
    const searchTerm = document.getElementById('searchInput').value.toLowerCase();

    const items = currentView === 'grid' ?
        document.querySelectorAll('.document-item') :
        document.querySelectorAll('.document-row');

    items.forEach(item => {
        const projectMatch = !projectFilter || item.dataset.project === projectFilter;
        const typeMatch = !typeFilter || item.dataset.type === typeFilter;
        const nameMatch = !searchTerm || item.dataset.name.includes(searchTerm);

        if (projectMatch && typeMatch && nameMatch) {
            item.style.display = '';
        } else {
            item.style.display = 'none';
        }
    });
}

function sortDocuments(criteria) {
    console.log('Sorting by:', criteria);
    // Implement sorting logic based on criteria
}

// Document actions
function previewDocument(id) {
    window.open(`view.php?id=${id}`, '_blank');
}

function downloadDocument(id) {
    window.open(`download.php?id=${id}`, '_blank');
}

function shareDocument(id) {
    // Implement sharing functionality
    alert('Share functionality will be implemented');
}

function deleteDocument(id) {
    if (confirm('Are you sure you want to delete this document?')) {
        fetch(`delete.php?id=${id}`, {
                method: 'POST'
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    location.reload();
                } else {
                    alert('Delete failed: ' + data.message);
                }
            });
    }
}
</script>

<?php include '../../includes/footer.php'; ?>