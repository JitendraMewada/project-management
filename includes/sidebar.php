<?php
// Assumes $auth and $current_user are initialized and available
?>

<div class="sidebar bg-light border-end">
    <div class="sidebar-content">
        <ul class="nav flex-column">
            <li class="nav-item">
                <a class="nav-link"
                    href="/project-management/dashboard/<?= htmlspecialchars($current_user['role']) ?>.php">
                    <i class="fas fa-tachometer-alt"></i> Dashboard
                </a>
            </li>

            <?php if ($auth->hasPermission('admin') || $auth->hasPermission('manager')): ?>
            <li class="nav-item">
                <a class="nav-link" href="/project-management/modules/projects/list.php">
                    <i class="fas fa-project-diagram"></i> Projects
                </a>
            </li>
            <?php endif; ?>

            <?php if ($auth->hasPermission('admin')): ?>
            <li class="nav-item">
                <a class="nav-link" href="/project-management/modules/users/list.php">
                    <i class="fas fa-users"></i> Users
                </a>
            </li>
            <?php endif; ?>

            <li class="nav-item">
                <a class="nav-link" href="/project-management/modules/tasks/list.php">
                    <i class="fas fa-tasks"></i> Tasks
                </a>
            </li>

            <li class="nav-item">
                <a class="nav-link" href="/project-management/modules/reports/list.php">
                    <i class="fas fa-chart-bar"></i> Reports
                </a>
            </li>

            <?php if ($current_user['role'] === 'designer'): ?>
            <li class="nav-item">
                <a class="nav-link" href="/project-management/modules/designs/list.php">
                    <i class="fas fa-paint-brush"></i> Designs
                </a>
            </li>
            <?php endif; ?>

            <?php if ($current_user['role'] === 'site_manager'): ?>
            <li class="nav-item">
                <a class="nav-link" href="/project-management/modules/inventory/list.php">
                    <i class="fas fa-boxes"></i> Inventory
                </a>
            </li>
            <?php endif; ?>

            <?php if ($current_user['role'] === 'site_coordinator'): ?>
            <li class="nav-item">
                <a class="nav-link" href="/project-management/modules/schedule/list.php">
                    <i class="fas fa-calendar"></i> Schedule
                </a>
            </li>
            <?php endif; ?>

            <?php if ($current_user['role'] === 'site_supervisor'): ?>
            <li class="nav-item">
                <a class="nav-link" href="/project-management/modules/attendance/list.php">
                    <i class="fas fa-clipboard-check"></i> Attendance
                </a>
            </li>
            <?php endif; ?>

            <?php if ($current_user['role'] === 'manager'): ?>
            <li class="nav-item">
                <a class="nav-link" href="/project-management/modules/team/list.php">
                    <i class="fas fa-users-cog"></i> Team Management
                </a>
            </li>
            <?php endif; ?>

            <?php if ($current_user['role'] === 'admin'): ?>
            <li class="nav-item">
                <a class="nav-link" href="/project-management/modules/settings/index.php">
                    <i class="fas fa-cogs"></i> System Settings
                </a>
            </li>
            <?php endif; ?>
        </ul>
    </div>
</div>