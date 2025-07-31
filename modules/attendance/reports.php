<?php
include '../../includes/header.php';
require_once '../../config/roles.php';
include '../../includes/functions.php';

// Permission: only admin, manager, supervisor can access
if (!in_array($current_user['role'], ['admin', 'manager', 'site_supervisor'])) {
    header("Location: ../../dashboard/" . $current_user['role'] . ".php");
    exit;
}

$error = '';
$users = [];
$attendance_records = [];

try {
    if ($current_user['role'] === 'site_supervisor') {
        $users = [ ['id' => $current_user['id'], 'name' => $current_user['name']] ];
    } else {
        $stmt = $db->query("SELECT id, name FROM users WHERE status = 'active' ORDER BY name");
        $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    $filter_user_id = $_GET['user_id'] ?? ($current_user['role'] === 'site_supervisor' ? $current_user['id'] : '');
    $filter_from = $_GET['from_date'] ?? date('Y-m-01');
    $filter_to = $_GET['to_date'] ?? date('Y-m-d');

    if (!DateTime::createFromFormat('Y-m-d', $filter_from) || !DateTime::createFromFormat('Y-m-d', $filter_to)) {
        throw new Exception('Invalid date format');
    }

    $sql = "SELECT a.*, u.name AS user_name FROM attendance a JOIN users u ON a.user_id = u.id WHERE a.date BETWEEN ? AND ?";
    $params = [$filter_from, $filter_to];

    if ($filter_user_id !== '') {
        $sql .= " AND a.user_id = ?";
        $params[] = $filter_user_id;
    }

    $sql .= " ORDER BY a.date DESC, a.check_in_time DESC";
    $stmt = $db->prepare($sql);
    $stmt->execute($params);
    $attendance_records = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $ex) {
    $error = $ex->getMessage();
}
?>

<div class="container-fluid">
    <div class="row">
        <div class="col-md-3"><?php include '../../includes/sidebar.php'; ?></div>
        <div class="col-md-9">
            <div class="main-content p-4">
                <h2><i class="fas fa-clipboard-list"></i> Attendance Reports</h2>
                <?php if ($error): ?>
                <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
                <?php endif; ?>

                <form method="GET" class="row g-3 align-items-center mb-4">
                    <div class="col-auto">
                        <label for="from_date" class="form-label">From Date</label>
                        <input type="date" id="from_date" name="from_date" value="<?= htmlspecialchars($filter_from) ?>"
                            max="<?= date('Y-m-d') ?>" class="form-control" required>
                    </div>
                    <div class="col-auto">
                        <label for="to_date" class="form-label">To Date</label>
                        <input type="date" id="to_date" name="to_date" value="<?= htmlspecialchars($filter_to) ?>"
                            max="<?= date('Y-m-d') ?>" class="form-control" required>
                    </div>
                    <?php if (count($users) > 1): ?>
                    <div class="col-auto">
                        <label for="user_id" class="form-label">User</label>
                        <select id="user_id" name="user_id" class="form-select">
                            <option value="">All</option>
                            <?php foreach ($users as $u): ?>
                            <option value="<?= $u['id'] ?>" <?= ($filter_user_id == $u['id']) ? 'selected' : '' ?>>
                                <?= htmlspecialchars($u['name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <?php endif; ?>
                    <div class="col-auto">
                        <button type="submit" class="btn btn-primary">View</button>
                    </div>
                    <div class="col-auto">
                        <button type="button" class="btn btn-outline-secondary" onclick="window.print()">Print</button>
                    </div>
                </form>

                <?php if (empty($attendance_records)): ?>
                <div class="alert alert-info">No attendance records found for the selected criteria.</div>
                <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-bordered table-hover">
                        <thead>
                            <tr>
                                <th>Date</th>
                                <?php if (count($users) > 1): ?><th>User</th><?php endif; ?>
                                <th>Check In</th>
                                <th>Check Out</th>
                                <th>Status</th>
                                <th>Total Hours</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($attendance_records as $record): ?>
                            <tr>
                                <td><?= htmlspecialchars(date('M d, Y', strtotime($record['date']))) ?></td>
                                <?php if (count($users) > 1): ?><td><?= htmlspecialchars($record['user_name']) ?></td>
                                <?php endif; ?>
                                <td><?= htmlspecialchars($record['check_in_time'] ? date('H:i:s', strtotime($record['check_in_time'])) : '-') ?>
                                </td>
                                <td><?= htmlspecialchars($record['check_out_time'] ? date('H:i:s', strtotime($record['check_out_time'])) : '-') ?>
                                </td>
                                <td><?= getStatusBadge($record['status'] ?? 'unknown', 'user') ?></td>
                                <td><?= !empty($record['total_hours']) ? htmlspecialchars(round($record['total_hours'], 2)) . ' h' : '-' ?>
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

<style>
@media print {

    button,
    form,
    nav,
    .sidebar,
    .breadcrumb {
        display: none !important;
    }

    body {
        box-shadow: none !important;
    }
}
</style>

<?php include '../../includes/footer.php'; ?>