<?php 
$pageTitle = "Reports - Interior Project Management";
include '../../includes/header.php';
require_once '../../config/roles.php';

// Check permission
if (!hasPermission($current_user['role'], 'reports', 'read')) {
    header("Location: ../../dashboard/" . $current_user['role'] . ".php");
    exit();
}

// Fetch reports with project and user information
$reports_query = "SELECT r.id, r.title, r.description, r.report_type, r.status, r.created_at, 
                         p.name AS project_name, u.name AS created_by_name 
                  FROM reports r 
                  LEFT JOIN projects p ON r.project_id = p.id 
                  LEFT JOIN users u ON r.created_by = u.id 
                  ORDER BY r.created_at DESC";

$reports_stmt = $db->prepare($reports_query);
$reports_stmt->execute();
$reports = $reports_stmt->fetchAll(PDO::FETCH_ASSOC);

// Fetch projects for filtering
$projects_query = "SELECT id, name FROM projects ORDER BY name";
$projects_stmt = $db->prepare($projects_query);
$projects_stmt->execute();
$projects = $projects_stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<div class="container-fluid">
    <div class="row">
        <div class="col-md-3">
            <?php include '../../includes/sidebar.php'; ?>
        </div>
        <div class="col-md-9">
            <div class="main-content p-4">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h2><i class="fas fa-file-alt"></i> Project Reports</h2>
                    <div class="btn-group">
                        <?php if (hasPermission($current_user['role'], 'reports', 'create')): ?>
                        <a href="create.php" class="btn btn-primary">
                            <i class="fas fa-plus"></i> New Report
                        </a>
                        <?php endif; ?>
                        <a href="templates.php" class="btn btn-outline-info">
                            <i class="fas fa-file-template"></i> Templates
                        </a>
                        <div class="btn-group">
                            <button class="btn btn-outline-secondary dropdown-toggle" data-bs-toggle="dropdown">
                                <i class="fas fa-download"></i> Export
                            </button>
                            <ul class="dropdown-menu">
                                <li><a class="dropdown-item" href="export.php?format=pdf">PDF</a></li>
                                <li><a class="dropdown-item" href="export.php?format=excel">Excel</a></li>
                                <li><a class="dropdown-item" href="export.php?format=csv">CSV</a></li>
                            </ul>
                        </div>
                    </div>
                </div>

                <!-- Filters -->
                <div class="card mb-4">
                    <div class="card-body">
                        <div class="row align-items-center">
                            <div class="col-md-3">
                                <select class="form-control" id="projectFilter" onchange="filterReports()">
                                    <option value="">All Projects</option>
                                    <?php foreach ($projects as $project): ?>
                                    <option value="<?php echo $project['id']; ?>">
                                        <?php echo htmlspecialchars($project['name']); ?>
                                    </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-3">
                                <select class="form-control" id="typeFilter" onchange="filterReports()">
                                    <option value="">All Types</option>
                                    <option value="progress">Progress Report</option>
                                    <option value="financial">Financial Report</option>
                                    <option value="quality">Quality Report</option>
                                    <option value="safety">Safety Report</option>
                                    <option value="completion">Completion Report</option>
                                </select>
                            </div>
                            <div class="col-md-3">
                                <select class="form-control" id="statusFilter" onchange="filterReports()">
                                    <option value="">All Status</option>
                                    <option value="draft">Draft</option>
                                    <option value="submitted">Submitted</option>
                                    <option value="approved">Approved</option>
                                    <option value="rejected">Rejected</option>
                                </select>
                            </div>
                            <div class="col-md-3">
                                <input type="text" class="form-control" id="searchInput" placeholder="Search reports..."
                                    onkeyup="filterReports()">
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Reports Statistics -->
                <div class="row mb-4">
                    <div class="col-md-3">
                        <div class="card bg-primary text-white">
                            <div class="card-body text-center">
                                <h4><?php echo count($reports); ?></h4>
                                <p class="mb-0">Total Reports</p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card bg-success text-white">
                            <div class="card-body text-center">
                                <h4><?php echo count(array_filter($reports, fn($r) => $r['status'] == 'approved')); ?>
                                </h4>
                                <p class="mb-0">Approved</p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card bg-warning text-white">
                            <div class="card-body text-center">
                                <h4><?php echo count(array_filter($reports, fn($r) => $r['status'] == 'submitted')); ?>
                                </h4>
                                <p class="mb-0">Pending Review</p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card bg-info text-white">
                            <div class="card-body text-center">
                                <h4><?php echo count(array_filter($reports, fn($r) => $r['status'] == 'draft')); ?></h4>
                                <p class="mb-0">Drafts</p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Reports Table -->
                <div class="card">
                    <div class="card-body">
                        <?php if (empty($reports)): ?>
                        <div class="text-center py-5">
                            <i class="fas fa-file-alt fa-3x text-muted mb-3"></i>
                            <h5 class="text-muted">No reports found</h5>
                            <p class="text-muted">Create your first report to get started!</p>
                            <?php if (hasPermission($current_user['role'], 'reports', 'create')): ?>
                            <a href="create.php" class="btn btn-primary">
                                <i class="fas fa-plus"></i> Create Report
                            </a>
                            <?php endif; ?>
                        </div>
                        <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-hover" id="reportsTable">
                                <thead>
                                    <tr>
                                        <th>Report Title</th>
                                        <th>Type</th>
                                        <th>Project</th>
                                        <th>Created By</th>
                                        <th>Status</th>
                                        <th>Created Date</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($reports as $report): ?>
                                    <tr class="report-row" data-project="<?php echo $report['project_id']; ?>"
                                        data-type="<?php echo $report['report_type']; ?>"
                                        data-status="<?php echo $report['status']; ?>">
                                        <td>
                                            <strong><?php echo htmlspecialchars($report['title']); ?></strong>
                                            <?php if ($report['description']): ?>
                                            <br><small
                                                class="text-muted"><?php echo htmlspecialchars(substr($report['description'], 0, 60)) . '...'; ?></small>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <span class="badge bg-secondary">
                                                <?php echo ucfirst(str_replace('_', ' ', $report['report_type'])); ?>
                                            </span>
                                        </td>
                                        <td>
                                            <?php if ($report['project_name']): ?>
                                            <a href="../projects/view.php?id=<?php echo $report['project_id']; ?>">
                                                <?php echo htmlspecialchars($report['project_name']); ?>
                                            </a>
                                            <?php else: ?>
                                            <span class="text-muted">General</span>
                                            <?php endif; ?>
                                        </td>
                                        <td><?php echo htmlspecialchars($report['created_by_name']); ?></td>
                                        <td>
                                            <span class="badge bg-<?php 
                                                    echo match($report['status']) {
                                                        'approved' => 'success',
                                                        'submitted' => 'warning',
                                                        'rejected' => 'danger',
                                                        'draft' => 'secondary',
                                                        default => 'secondary'
                                                    };
                                                ?>"><?php echo ucfirst($report['status']); ?></span>
                                        </td>
                                        <td><?php echo date('M d, Y', strtotime($report['created_at'])); ?></td>
                                        <td>
                                            <div class="btn-group btn-group-sm">
                                                <a href="view.php?id=<?php echo $report['id']; ?>"
                                                    class="btn btn-outline-primary" title="View">
                                                    <i class="fas fa-eye"></i>
                                                </a>

                                                <?php if (hasPermission($current_user['role'], 'reports', 'update') && 
                                                              ($report['created_by'] == $current_user['id'] || $current_user['role'] == 'admin')): ?>
                                                <a href="edit.php?id=<?php echo $report['id']; ?>"
                                                    class="btn btn-outline-success" title="Edit">
                                                    <i class="fas fa-edit"></i>
                                                </a>
                                                <?php endif; ?>

                                                <a href="download.php?id=<?php echo $report['id']; ?>"
                                                    class="btn btn-outline-info" title="Download">
                                                    <i class="fas fa-download"></i>
                                                </a>

                                                <?php if (hasPermission($current_user['role'], 'reports', 'delete') && 
                                                              ($report['created_by'] == $current_user['id'] || $current_user['role'] == 'admin')): ?>
                                                <button class="btn btn-outline-danger" title="Delete"
                                                    onclick="deleteReport(<?php echo $report['id']; ?>)">
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
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
function filterReports() {
    const projectFilter = document.getElementById('projectFilter').value;
    const typeFilter = document.getElementById('typeFilter').value;
    const statusFilter = document.getElementById('statusFilter').value;
    const searchTerm = document.getElementById('searchInput').value.toLowerCase();

    const rows = document.querySelectorAll('.report-row');

    rows.forEach(row => {
        const project = row.dataset.project;
        const type = row.dataset.type;
        const status = row.dataset.status;
        const content = row.textContent.toLowerCase();

        const projectMatch = !projectFilter || project === projectFilter;
        const typeMatch = !typeFilter || type === typeFilter;
        const statusMatch = !statusFilter || status === statusFilter;
        const searchMatch = !searchTerm || content.includes(searchTerm);

        if (projectMatch && typeMatch && statusMatch && searchMatch) {
            row.style.display = '';
        } else {
            row.style.display = 'none';
        }
    });
}

function deleteReport(reportId) {
    if (confirm('Are you sure you want to delete this report? This action cannot be undone.')) {
        fetch('delete.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({
                    id: reportId
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    location.reload();
                } else {
                    alert('Error deleting report: ' + data.message);
                }
            })
            .catch(error => {
                alert('Error deleting report: ' + error.message);
            });
    }
}
</script>

<?php include '../../includes/footer.php'; ?>