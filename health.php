<?php
// System health check endpoint
header('Content-Type: application/json');

$health = [
    'status' => 'healthy',
    'timestamp' => date('c'),
    'checks' => []
];

// Database check
try {
    require_once 'config/database.php';
    $database = new Database();
    $db = $database->getConnection();
    $db->query('SELECT 1');
    
    $health['checks']['database'] = [
        'status' => 'healthy',
        'message' => 'Database connection successful'
    ];
} catch (Exception $e) {
    $health['status'] = 'unhealthy';
    $health['checks']['database'] = [
        'status' => 'unhealthy',
        'message' => 'Database connection failed: ' . $e->getMessage()
    ];
}

// File system checks
$upload_dir = 'uploads/';
if (is_writable($upload_dir)) {
    $health['checks']['filesystem'] = [
        'status' => 'healthy',
        'message' => 'Upload directory is writable'
    ];
} else {
    $health['status'] = 'unhealthy';
    $health['checks']['filesystem'] = [
        'status' => 'unhealthy',
        'message' => 'Upload directory is not writable'
    ];
}

// Memory usage check
$memory_usage = memory_get_usage(true);
$memory_limit = ini_get('memory_limit');
$memory_limit_bytes = $memory_limit == -1 ? PHP_INT_MAX : 
    (int)$memory_limit * (strpos($memory_limit, 'G') ? 1073741824 : 
    (strpos($memory_limit, 'M') ? 1048576 : 1));

$memory_usage_percent = ($memory_usage / $memory_limit_bytes) * 100;

$health['checks']['memory'] = [
    'status' => $memory_usage_percent < 80 ? 'healthy' : 'warning',
    'usage' => round($memory_usage / 1048576, 2) . ' MB',
    'limit' => $memory_limit,
    'percentage' => round($memory_usage_percent, 2) . '%'
];

// Disk space check
$disk_free = disk_free_space('.');
$disk_total = disk_total_space('.');
$disk_usage_percent = (($disk_total - $disk_free) / $disk_total) * 100;

$health['checks']['disk'] = [
    'status' => $disk_usage_percent < 90 ? 'healthy' : 'critical',
    'free' => round($disk_free / 1073741824, 2) . ' GB',
    'total' => round($disk_total / 1073741824, 2) . ' GB',
    'usage_percent' => round($disk_usage_percent, 2) . '%'
];

// Response time check
$start_time = microtime(true);
usleep(100); // Small delay to measure
$response_time = (microtime(true) - $start_time) * 1000;

$health['checks']['performance'] = [
    'status' => $response_time < 500 ? 'healthy' : 'warning',
    'response_time' => round($response_time, 2) . ' ms'
];

// Email service check (optional)
if (class_exists('EmailService')) {
    try {
        $emailService = new EmailService();
        $health['checks']['email'] = [
            'status' => 'healthy',
            'message' => 'Email service is configured'
        ];
    } catch (Exception $e) {
        $health['checks']['email'] = [
            'status' => 'warning',
            'message' => 'Email service configuration issue'
        ];
    }
}

// Overall status determination
$unhealthy_checks = array_filter($health['checks'], function($check) {
    return $check['status'] === 'unhealthy' || $check['status'] === 'critical';
});

if (!empty($unhealthy_checks)) {
    $health['status'] = 'unhealthy';
    http_response_code(503);
}

echo json_encode($health, JSON_PRETTY_PRINT);
?>