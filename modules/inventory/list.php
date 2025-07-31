<?php 
include '../../includes/header.php';
require_once '../../config/roles.php';

// Check permission
if (!hasPermission($current_user['role'], 'inventory', 'read')) {
    header("Location: ../../dashboard/" . $current_user['role'] . ".php");
    exit();
}

// Fetch inventory items
$inventory_query = "SELECT i.*, p.name as project_name, s.name as supplier_name
                    FROM inventory_items i 
                    LEFT JOIN projects p ON i.project_id = p.id 
                    LEFT JOIN suppliers s ON i.supplier_id = s.id 
                    ORDER BY i.created_at DESC";
$inventory_stmt = $db->prepare($inventory_query);
$inventory_stmt->execute();
$inventory_items = $inventory_stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<div class="container-fluid">
    <div class="row">
        <div class="col-md-3">
            <?php include '../../includes/sidebar.php'; ?>
        </div>
        <div class="col-md-9">
            <div class="main-content p-4">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h2><i class="fas fa-boxes"></i> Inventory Management</h2>
                    <div class="btn-group">
                        <?php if (hasPermission($current_user['role'], 'inventory', 'create')): ?>
                        <a href="create.php" class="btn btn-primary">
                            <i class="fas fa-plus"></i> Add Item
                        </a>
                        <?php endif; ?>
                        <a href="low_stock.php" class="btn btn-warning">
                            <i class="fas fa-exclamation-triangle"></i> Low Stock
                        </a>
                        <a href="reports.php" class="btn btn-info">
                            <i class="fas fa-chart-bar"></i> Reports
                        </a>
                    </div>
                </div>

                <!-- Inventory Statistics -->
                <div class="row mb-4">
                    <div class="col-md-3">
                        <div class="card bg-primary text-white">
                            <div class="card-body text-center">
                                <h4><?php echo count($inventory_items); ?></h4>
                                <p class="mb-0">Total Items</p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card bg-warning text-white">
                            <div class="card-body text-center">
                                <h4><?php echo count(array_filter($inventory_items, fn($i) => $i['stock_quantity'] <= $i['minimum_stock'])); ?>
                                </h4>
                                <p class="mb-0">Low Stock</p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card bg-success text-white">
                            <div class="card-body text-center">
                                <h4>₹<?php echo number_format(array_sum(array_column($inventory_items, 'total_value'))); ?>
                                </h4>
                                <p class="mb-0">Total Value</p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card bg-info text-white">
                            <div class="card-body text-center">
                                <h4><?php echo count(array_unique(array_column($inventory_items, 'category'))); ?></h4>
                                <p class="mb-0">Categories</p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Inventory Table -->
                <div class="card">
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>Item Name</th>
                                        <th>Category</th>
                                        <th>Stock</th>
                                        <th>Unit Price</th>
                                        <th>Total Value</th>
                                        <th>Project</th>
                                        <th>Status</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($inventory_items as $item): ?>
                                    <tr
                                        class="<?php echo $item['stock_quantity'] <= $item['minimum_stock'] ? 'table-warning' : ''; ?>">
                                        <td>
                                            <strong><?php echo htmlspecialchars($item['name']); ?></strong>
                                            <br><small
                                                class="text-muted"><?php echo htmlspecialchars($item['sku']); ?></small>
                                        </td>
                                        <td><?php echo htmlspecialchars($item['category']); ?></td>
                                        <td>
                                            <span
                                                class="<?php echo $item['stock_quantity'] <= $item['minimum_stock'] ? 'text-danger fw-bold' : ''; ?>">
                                                <?php echo $item['stock_quantity']; ?>
                                                <?php echo htmlspecialchars($item['unit']); ?>
                                            </span>
                                            <?php if ($item['stock_quantity'] <= $item['minimum_stock']): ?>
                                            <i class="fas fa-exclamation-triangle text-warning"></i>
                                            <?php endif; ?>
                                        </td>
                                        <td>₹<?php echo number_format($item['unit_price'], 2); ?></td>
                                        <td>₹<?php echo number_format($item['total_value'], 2); ?></td>
                                        <td><?php echo htmlspecialchars($item['project_name'] ?? 'General'); ?></td>
                                        <td>
                                            <span
                                                class="badge bg-<?php echo $item['status'] == 'available' ? 'success' : 'warning'; ?>">
                                                <?php echo ucfirst($item['status']); ?>
                                            </span>
                                        </td>
                                        <td>
                                            <div class="btn-group btn-group-sm">
                                                <a href="view.php?id=<?php echo $item['id']; ?>"
                                                    class="btn btn-outline-primary">
                                                    <i class="fas fa-eye"></i>
                                                </a>
                                                <?php if (hasPermission($current_user['role'], 'inventory', 'update')): ?>
                                                <a href="edit.php?id=<?php echo $item['id']; ?>"
                                                    class="btn btn-outline-success">
                                                    <i class="fas fa-edit"></i>
                                                </a>
                                                <button class="btn btn-outline-info"
                                                    onclick="adjustStock(<?php echo $item['id']; ?>)">
                                                    <i class="fas fa-plus-minus"></i>
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

<?php include '../../includes/footer.php'; ?>