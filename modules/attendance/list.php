<?php
include '../../includes/header.php';
require_once '../../config/roles.php';
include '../../includes/functions.php'; // Utilities like getStatusBadge()

// Permission check to access attendance module
if (!hasPermission($current_user['role'], 'attendance', 'read')) {
    header("Location: ../../dashboard/" . $current_user['role'] . ".php");
    exit();
}

// Fetch attendance records with joined user, project, and site info
$query = "
    SELECT a.*, u.name AS user_name, p.name AS project_name, s.name AS site_name
    FROM attendance a
    LEFT JOIN users u ON a.user_id = u.id
    LEFT JOIN projects p ON a.project_id = p.id
    LEFT JOIN sites s ON a.site_id = s.id
    ORDER BY a.date DESC, a.check_in_time DESC
";
$stmt = $db->prepare($query);
$stmt->execute();
$attendance_records = $stmt->fetchAll(PDO::FETCH_ASSOC);

$today = date('Y-m-d');

// Calculate today's summaries
$today_present = count(array_filter($attendance_records, fn($a) => $a['date'] === $today && $a['status'] === 'present'));
$today_absent = count(array_filter($attendance_records, fn($a) => $a['date'] === $today && $a['status'] === 'absent'));
$today_late = count(array_filter($attendance_records, fn($a) => $a['date'] === $today && $a['status'] === 'late'));
$total_hours_today = array_reduce($attendance_records, function($carry, $item) use ($today) {
    return ($item['date'] === $today && !empty($item['total_hours'])) ? $carry + $item['total_hours'] : $carry;
}, 0);
$avg_hours = $today_present > 0 ? round($total_hours_today / $today_present, 1) : 0;
?>

<div class="container-fluid">
    <div class="row">
        <!-- Sidebar -->
        <div class="col-md-3">
            <?php include '../../includes/sidebar.php'; ?>
        </div>

        <!-- Main Content -->
        <div class="col-md-9">
            <div class="main-content p-4">

                <!-- Header and action buttons -->
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h2><i class="fas fa-user-clock"></i> Attendance Management</h2>
                    <div class="btn-group" role="group" aria-label="Attendance actions">
                        <!-- Non-admin users get check in/out buttons -->
                        <?php if ($current_user['role'] !== 'admin'): ?>
                        <button class="btn btn-success" onclick="checkIn()">
                            <i class="fas fa-sign-in-alt"></i> Check In
                        </button>
                        <button class="btn btn-warning" onclick="checkOut()">
                            <i class="fas fa-sign-out-alt"></i> Check Out
                        </button>
                        <?php endif; ?>
                        <a href="reports.php" class="btn btn-info">
                            <i class="fas fa-chart-bar"></i> Reports
                        </a>
                        <a href="timesheet.php" class="btn btn-outline-primary">
                            <i class="fas fa-clock"></i> Timesheet
                        </a>
                    </div>
                </div>

                <!-- Today's Summary Cards -->
                <div class="row text-center mb-4">
                    <div class="col-md-3">
                        <div class="card bg-success text-white">
                            <div class="card-body">
                                <h4><?= $today_present ?></h4>
                                <p>Present Today</p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card bg-danger text-white">
                            <div class="card-body">
                                <h4><?= $today_absent ?></h4>
                                <p>Absent Today</p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card bg-warning text-white">
                            <div class="card-body">
                                <h4><?= $today_late ?></h4>
                                <p>Late Today</p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card bg-info text-white">
                            <div class="card-body">
                                <h4><?= $avg_hours ?>h</h4>
                                <p>Average Hours</p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Attendance Records Table -->
                <div class="card">
                    <div class="card-header">
                        <h6><i class="fas fa-list"></i> Attendance Records</h6>
                    </div>
                    <div class="table-responsive">
                        <table class="table table-hover table-bordered align-middle mb-0">
                            <thead>
                                <tr>
                                    <th>Date</th>
                                    <th>Employee</th>
                                    <th>Project/Site</th>
                                    <th>Check In</th>
                                    <th>Check Out</th>
                                    <th>Total Hours</th>
                                    <th>Status</th>
                                    <?php if (hasPermission($current_user['role'], 'attendance', 'update')): ?>
                                    <th>Actions</th>
                                    <?php endif; ?>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($attendance_records as $record): ?>
                                <tr>
                                    <td><?= htmlspecialchars(date('M d, Y', strtotime($record['date']))) ?></td>
                                    <td><?= htmlspecialchars($record['user_name']) ?></td>
                                    <td>
                                        <?php if ($record['project_name']): ?>
                                        <i class="fas fa-project-diagram"></i>
                                        <?= htmlspecialchars($record['project_name']) ?>
                                        <?php elseif ($record['site_name']): ?>
                                        <i class="fas fa-map-marker-alt"></i>
                                        <?= htmlspecialchars($record['site_name']) ?>
                                        <?php else: ?>
                                        <span class="text-muted">Office</span>
                                        <?php endif; ?>
                                    </td>
                                    <td><?= $record['check_in_time'] ? htmlspecialchars(date('H:i', strtotime($record['check_in_time']))) : '<span class="text-muted">-</span>' ?>
                                    </td>
                                    <td><?= $record['check_out_time'] ? htmlspecialchars(date('H:i', strtotime($record['check_out_time']))) : '<span class="text-warning">Active</span>' ?>
                                    </td>
                                    <td><?= $record['total_hours'] ? htmlspecialchars($record['total_hours']) . 'h' : '<span class="text-muted">-</span>' ?>
                                    </td>
                                    <td><?= getStatusBadge($record['status'] ?? 'unknown', 'user') ?></td>
                                    <?php if (hasPermission($current_user['role'], 'attendance', 'update')): ?>
                                    <td>
                                        <div class="btn-group btn-group-sm" role="group">
                                            <a href="view.php?id=<?= (int)$record['id'] ?>"
                                                class="btn btn-outline-primary" title="View">
                                                <i class="fas fa-eye"></i>
                                            </a>
                                            <a href="edit.php?id=<?= (int)$record['id'] ?>"
                                                class="btn btn-outline-success" title="Edit">
                                                <i class="fas fa-edit"></i>
                                            </a>
                                        </div>
                                    </td>
                                    <?php endif; ?>
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


<script>
function checkIn() {
    fetch('quick_attendance.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({
                action: 'check_in'
            })
        })
        .then(res => res.json())
        .then(data => {
            alert(data.message);
            if (data.success) location.reload();
        })
        .catch(() => alert('Network or server error during check-in.'));
}

function checkOut() {
    fetch('quick_attendance.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({
                action: 'check_out'
            })
        })
        .then(res => res.json())
        .then(data => {
            alert(data.message);
            if (data.success) location.reload();
        })
        .catch(() => alert('Network or server error during check-out.'));
}
</script>


<?php include '../../includes/footer.php'; ?>