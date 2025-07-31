<?php
require_once '../config/database.php';
require_once '../config/auth.php';

header('Content-Type: application/json');
error_reporting(0);

$database = new Database();
$db = $database->getConnection();
$auth = new Auth($db);

if (!$auth->isLoggedIn()) {
    echo json_encode(['success' => false, 'error' => 'Not authenticated']);
    exit;
}

try {
    // Date range param
    $dateRange = isset($_GET['range']) ? intval($_GET['range']) : 30;
    if ($dateRange < 1 || $dateRange > 365) $dateRange = 30;

    $rangeStart = date('Y-m-d', strtotime("-{$dateRange} days"));

    // Stats queries
    $totalProjects = (int)$db->query("SELECT COUNT(*) FROM projects")->fetchColumn();

    $stmtRecent = $db->prepare("SELECT COUNT(*) FROM projects WHERE DATE(created_at) >= ?");
    $stmtRecent->execute([$rangeStart]);
    $recentProjects = (int)$stmtRecent->fetchColumn();

    $totalUsers = (int)$db->query("SELECT COUNT(*) FROM users WHERE status = 'active'")->fetchColumn();

    $activeTasks = (int)$db->query("SELECT COUNT(*) FROM tasks WHERE status IN ('pending', 'in_progress')")->fetchColumn();

    $statusQuery = $db->query("SELECT
        SUM(status = 'completed') AS completed,
        SUM(status = 'in_progress') AS in_progress,
        SUM(status = 'planning') AS planning,
        SUM(status = 'on_hold') AS on_hold
        FROM projects");
    $row = $statusQuery->fetch(PDO::FETCH_ASSOC) ?: [];

    $revenue = $db->query("SELECT SUM(budget) FROM projects WHERE status = 'completed'")->fetchColumn();
    if (!$revenue) $revenue = 0;

    // Chart data (replace dummy projectProgress with your real data if possible)
    $charts = [
        'projectStatus' => [
            'completed' => intval($row['completed'] ?? 0),
            'in_progress' => intval($row['in_progress'] ?? 0),
            'planning' => intval($row['planning'] ?? 0),
            'on_hold' => intval($row['on_hold'] ?? 0),
        ],
        'projectProgress' => [
            'labels' => ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun'],
            'completed' => [12, 19, 15, 25, 22, 30],
            'started' => [15, 22, 18, 28, 25, 35],
        ],
        'teamPerformance' => [
            'current' => [85, 90, 78, 92, 88, 85],
            'previous' => [80, 85, 75, 88, 82, 80],
        ],
        'revenue' => [25, 32, 28, 45, 38, 52],
    ];

    // Compose response
    $response = [
        'success' => true,
        'stats' => [
            'totalProjects' => $totalProjects,
            'projects' => $recentProjects,
            'projectsGrowth' => 10,
            'totalUsers' => $totalUsers,
            'usersGrowth' => 5,
            'activeTasks' => $activeTasks,
            'tasksGrowth' => 7,
            'totalRevenue' => (float)$revenue,
            'revenueGrowth' => 6,
        ],
        'charts' => $charts,
        'timestamp' => time(),
    ];

    echo json_encode($response);

} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage(),
    ]);
}