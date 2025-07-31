<?php 
include '../../includes/header.php';
require_once '../../config/roles.php';

// Check permission
if (!hasPermission($current_user['role'], 'projects', 'read')) {
    header("Location: ../../dashboard/" . $current_user['role'] . ".php");
    exit();
}

// Fetch projects based on user role
$query = "SELECT p.*, u1.name as manager_name, u2.name as designer_name, u3.name as site_manager_name 
          FROM projects p 
          LEFT JOIN users u1 ON p.manager_id = u1.id 
          LEFT JOIN users u2 ON p.designer_id = u2.id 
          LEFT JOIN users u3 ON p.site_manager_id = u3.id";

if ($current_user['role'] != 'admin') {
    $query .= " WHERE p.manager_id = ? OR p.designer_id = ? OR p.site_manager_id = ?";
}

$query .= " ORDER BY p.created_at DESC";
$stmt = $db->prepare($query);

if ($current_user['role'] != 'admin') {
    $stmt->execute([$current_user['id'], $current_user['id'], $current_user['id']]);
} else {
    $stmt->execute();
}

$projects = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<div class="container-fluid">
    <div class="row">
        <div class="col-md-3">
            <?php include '../../includes/sidebar.php'; ?>
        </div>
        <div class="col-md-9">
            <div class="main-content p-4">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h2><i class="fas fa-project-diagram"></i> Projects</h2>
                    <?php if (hasPermission($current_user['role'], 'projects', 'create')): ?>
                    <a href="create.php" class="btn btn-primary">
                        <i class="fas fa-plus"></i> New Project
                    </a>
                    <?php endif; ?>
                </div>

                <!-- Project Statistics -->
                <div class="row mb-4">
                    <div class="col-md-3">
                        <div class="card bg-success text-white">
                            <div class="card-body text-center">
                                <h3><?php echo count(array_filter($projects, fn($p) => $p['status'] == 'completed')); ?>
                                </h3>
                                <p>Completed</p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card bg-warning text-white">
                            <div class="card-body text-center">
                                <h3><?php echo count(array_filter($projects, fn($p) => $p['status'] == 'in_progress')); ?>
                                </h3>
                                <p>In Progress</p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card bg-info text-white">
                            <div class="card-body text-center">
                                <h3><?php echo count(array_filter($projects, fn($p) => $p['status'] == 'planning')); ?>
                                </h3>
                                <p>Planning</p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card bg-secondary text-white">
                            <div class="card-body text-center">
                                <h3><?php echo count(array_filter($projects, fn($p) => $p['status'] == 'on_hold')); ?>
                                </h3>
                                <p>On Hold</p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Projects Table -->
                <div class="card">
                    <div class="card-header">
                        <div class="row align-items-center">
                            <div class="col-md-6">
                                <h5 class="mb-0">All Projects</h5>
                            </div>
                            <div class="col-md-6">
                                <input type="text" class="form-control table-search" placeholder="Search projects...">
                            </div>
                        </div>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-hover data-table">
                                <thead>
                                    <tr>
                                        <th>Project Name</th>
                                        <th>Client</th>
                                        <th>Type</th>
                                        <th>Status</th>
                                        <th>Progress</th>
                                        <th>Budget</th>
                                        <th>Manager</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($projects as $project): ?>
                                    <tr>
                                        <td>
                                            <strong><?php echo htmlspecialchars($project['name']); ?></strong>
                                            <br><small
                                                class="text-muted"><?php echo date('M d, Y', strtotime($project['start_date'])); ?></small>
                                        </td>
                                        <td>
                                            <?php echo htmlspecialchars($project['client_name']); ?>
                                            <br><small
                                                class="text-muted"><?php echo htmlspecialchars($project['client_email']); ?></small>
                                        </td>
                                        <td>
                                            <span
                                                class="badge bg-info"><?php echo ucfirst($project['project_type']); ?></span>
                                        </td>
                                        <td>
                                            <span
                                                class="badge bg-<?php 
                                                echo match($project['status']) {
                                                    'completed' => 'success',
                                                    'in_progress' => 'warning',
                                                    'planning' => 'info',
                                                    'on_hold' => 'secondary',
                                                    'cancelled' => 'danger',
                                                    default => 'secondary'
                                                };
                                            ?>"><?php echo ucfirst(str_replace('_', ' ', $project['status'])); ?></span>
                                        </td>
                                        <td>
                                            <div class="progress" style="height: 20px;">
                                                <div class="progress-bar bg-<?php echo $project['progress'] < 30 ? 'danger' : ($project['progress'] < 70 ? 'warning' : 'success'); ?>"
                                                    style="width: <?php echo $project['progress']; ?>%">
                                                    <?php echo $project['progress']; ?>%
                                                </div>
                                            </div>
                                        </td>
                                        <td>
                                            <?php if ($project['budget']): ?>
                                            ₹<?php echo number_format($project['budget'], 2); ?>
                                            <?php else: ?>
                                            <span class="text-muted">Not set</span>
                                            <?php endif; ?>
                                        </td>
                                        <td><?php echo htmlspecialchars($project['manager_name'] ?? 'Unassigned'); ?>
                                        </td>
                                        <td>
                                            <div class="btn-group btn-group-sm">
                                                <a href="view.php?id=<?php echo $project['id']; ?>"
                                                    class="btn btn-outline-primary" title="View">
                                                    <i class="fas fa-eye"></i>
                                                </a>
                                                <?php if (hasPermission($current_user['role'], 'projects', 'update')): ?>
                                                <a href="edit.php?id=<?php echo $project['id']; ?>"
                                                    class="btn btn-outline-success" title="Edit">
                                                    <i class="fas fa-edit"></i>
                                                </a>
                                                <?php endif; ?>
                                                <?php if (hasPermission($current_user['role'], 'projects', 'delete')): ?>
                                                <a href="delete.php?id=<?php echo $project['id']; ?>"
                                                    class="btn btn-outline-danger delete-btn" title="Delete">
                                                    <i class="fas fa-trash"></i>
                                                </a>
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

<?php include '../../includes/footer.php'; ?>