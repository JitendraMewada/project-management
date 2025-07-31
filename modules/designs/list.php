<?php 
include '../../includes/header.php';
require_once '../../config/roles.php';

// Check permission
if (!hasPermission($current_user['role'], 'designs', 'read')) {
    header("Location: ../../dashboard/" . $current_user['role'] . ".php");
    exit();
}

// Fetch designs with project information
$designs_query = "SELECT d.*, p.name as project_name, u.name as designer_name 
                  FROM designs d 
                  LEFT JOIN projects p ON d.project_id = p.id 
                  LEFT JOIN users u ON d.designer_id = u.id 
                  ORDER BY d.created_at DESC";
$designs_stmt = $db->prepare($designs_query);
$designs_stmt->execute();
$designs = $designs_stmt->fetchAll(PDO::FETCH_ASSOC);

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
                    <h2><i class="fas fa-paint-brush"></i> Design Management</h2>
                    <div class="btn-group">
                        <?php if (hasPermission($current_user['role'], 'designs', 'create')): ?>
                        <a href="create.php" class="btn btn-primary">
                            <i class="fas fa-plus"></i> New Design
                        </a>
                        <?php endif; ?>
                        <a href="gallery.php" class="btn btn-outline-info">
                            <i class="fas fa-images"></i> Design Gallery
                        </a>
                    </div>
                </div>

                <!-- Designs Grid -->
                <div class="row">
                    <?php foreach ($designs as $design): ?>
                    <div class="col-xl-4 col-lg-6 col-md-6 mb-4">
                        <div class="card design-card h-100">
                            <div class="design-preview">
                                <?php if ($design['preview_image']): ?>
                                <img src="../../<?php echo htmlspecialchars($design['preview_image']); ?>"
                                    class="card-img-top" alt="Design Preview" style="height: 200px; object-fit: cover;">
                                <?php else: ?>
                                <div class="no-preview">
                                    <i class="fas fa-palette fa-3x text-muted"></i>
                                    <p class="text-muted">No preview available</p>
                                </div>
                                <?php endif; ?>
                            </div>

                            <div class="card-body">
                                <h5 class="card-title"><?php echo htmlspecialchars($design['title']); ?></h5>
                                <p class="text-muted mb-2">
                                    <i class="fas fa-project-diagram"></i>
                                    <?php echo htmlspecialchars($design['project_name']); ?>
                                </p>
                                <p class="text-muted mb-2">
                                    <i class="fas fa-user"></i>
                                    <?php echo htmlspecialchars($design['designer_name']); ?>
                                </p>
                                <p class="card-text">
                                    <?php echo htmlspecialchars(substr($design['description'], 0, 100)) . '...'; ?>
                                </p>

                                <div class="d-flex justify-content-between align-items-center">
                                    <span
                                        class="badge bg-<?php echo $design['status'] == 'approved' ? 'success' : ($design['status'] == 'in_review' ? 'warning' : 'info'); ?>">
                                        <?php echo ucfirst(str_replace('_', ' ', $design['status'])); ?>
                                    </span>
                                    <div class="btn-group btn-group-sm">
                                        <a href="view.php?id=<?php echo $design['id']; ?>"
                                            class="btn btn-outline-primary">
                                            <i class="fas fa-eye"></i>
                                        </a>
                                        <?php if (hasPermission($current_user['role'], 'designs', 'update')): ?>
                                        <a href="edit.php?id=<?php echo $design['id']; ?>"
                                            class="btn btn-outline-success">
                                            <i class="fas fa-edit"></i>
                                        </a>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>

                            <div class="card-footer text-muted small">
                                <i class="fas fa-calendar"></i>
                                Created: <?php echo date('M d, Y', strtotime($design['created_at'])); ?>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
.design-card {
    transition: transform 0.3s ease, box-shadow 0.3s ease;
}

.design-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 8px 25px rgba(0, 0, 0, 0.15);
}

.no-preview {
    height: 200px;
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    background: #f8f9fa;
}
</style>

<?php include '../../includes/footer.php'; ?>