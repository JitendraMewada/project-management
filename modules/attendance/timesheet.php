<?php
include '../../includes/header.php';
require_once '../../config/roles.php';
include '../../includes/functions.php';

// Check if user logged in
if (!isset($current_user) || empty($current_user['id'])) {
    header('Location: ../../login.php');
    exit;
}

$userId = (int)$current_user['id'];
$canViewOthers = in_array($current_user['role'], ['admin', 'manager']);
$users = [];

if ($canViewOthers) {
    $users = $db->query("SELECT id, name FROM users WHERE status = 'active' ORDER BY name")->fetchAll(PDO::FETCH_ASSOC);
}

$filterUserId = $canViewOthers && isset($_GET['user_id']) ? (int)$_GET['user_id'] : $userId;
$filterFromDate = $_GET['from_date'] ?? date('Y-m-01');
$filterToDate = $_GET['to_date'] ?? date('Y-m-d');

if (!DateTime::createFromFormat('Y-m-d', $filterFromDate)) $filterFromDate = date('Y-m-01');
if (!DateTime::createFromFormat('Y-m-d', $filterToDate)) $filterToDate = date('Y-m-d');

$attendanceRecords = [];
$error = '';

try {
    $sql = "SELECT a.*, u.name AS user_name FROM attendance a JOIN users u ON a.user_id = u.id
            WHERE a.user_id = ? AND a.date BETWEEN ? AND ? 
            ORDER BY a.date DESC, a.check_in_time DESC";
    $stmt = $db->prepare($sql);
    $stmt->execute([$filterUserId, $filterFromDate, $filterToDate]);
    $attendanceRecords = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    $error = "Failed to fetch attendance records: " . $e->getMessage();
}
?>

<div class="container-fluid">
    <div class="row">
        <div class="col-md-3">
            <?php include '../../includes/sidebar.php'; ?>
        </div>
        <div class="col-md-9">
            <div class="main-content p-4">
                <h2><i class="fas fa-clock"></i> Timesheet</h2>

                <?php if ($error): ?>
                <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
                <?php endif; ?>

                <form method="GET" class="row g-3 align-items-center mb-4">
                    <?php if ($canViewOthers): ?>
                    <div class="col-auto">
                        <label for="user_id" class="col-form-label">User</label>
                        <select id="user_id" name="user_id" class="form-select">
                            <?php foreach ($users as $u): ?>
                            <option value="<?= $u['id'] ?>" <?= $u['id'] == $filterUserId ? 'selected' : '' ?>>
                                <?= htmlspecialchars($u['name']) ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <?php endif; ?>

                    <div class="col-auto">
                        <label for="from_date" class="col-form-label">From</label>
                        <input type="date" id="from_date" name="from_date" class="form-control" required
                            max="<?= date('Y-m-d') ?>" value="<?= htmlspecialchars($filterFromDate) ?>">
                    </div>

                    <div class="col-auto">
                        <label for="to_date" class="col-form-label">To</label>
                        <input type="date" id="to_date" name="to_date" class="form-control" required
                            max="<?= date('Y-m-d') ?>" value="<?= htmlspecialchars($filterToDate) ?>">
                    </div>

                    <div class="col-auto align-self-end">
                        <button type="submit" class="btn btn-primary">View</button>
                    </div>
                    <div class="col-auto align-self-end">
                        <button type="button" class="btn btn-outline-secondary" onclick="window.print()">Print</button>
                    </div>
                </form>

                <?php if (empty($attendanceRecords)): ?>
                <div class="alert alert-info">No attendance records found for the selected date range.</div>
                <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-striped table-bordered align-middle">
                        <thead>
                            <tr>
                                <th>Date</th>
                                <?php if ($canViewOthers): ?><th>User</th><?php endif; ?>
                                <th>Check In</th>
                                <th>Check Out</th>
                                <th>Status</th>
                                <th>Total Hours</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($attendanceRecords as $record): ?>
                            <tr>
                                <td><?= htmlspecialchars(date('M d, Y', strtotime($record['date']))) ?></td>
                                <?php if ($canViewOthers): ?>
                                <td><?= htmlspecialchars($record['user_name']) ?></td>
                                <?php endif; ?>
                                <td><?= htmlspecialchars($record['check_in_time'] ? date('H:i:s', strtotime($record['check_in_time'])) : '-') ?>
                                </td>
                                <td><?= htmlspecialchars($record['check_out_time'] ? date('H:i:s', strtotime($record['check_out_time'])) : '-') ?>
                                </td>
                                <td><?= getStatusBadge($record['status'] ?? 'unknown', 'user') ?></td>
                                <td>
                                    <?php
                                    if (!empty($record['total_hours'])) {
                                        echo htmlspecialchars(round($record['total_hours'], 2)) . ' h';
                                    } elseif (!empty($record['check_in_time']) && !empty($record['check_out_time'])) {
                                        $duration = (strtotime($record['check_out_time'])-strtotime($record['check_in_time']))/3600;
                                        echo htmlspecialchars(round($duration, 2)) . ' h';
                                    } else {
                                        echo '-';
                                    }
                                    ?>
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

    body>* {
        box-shadow: none !important;
    }
}
</style>

<?php include '../../includes/footer.php'; ?>