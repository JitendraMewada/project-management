<?php
// This script should be run daily via cron job
// Add to crontab: 0 9 * * * /usr/bin/php /path/to/your/project/crons/deadline_reminders.php

require_once '../config/database.php';
require_once '../services/NotificationService.php';

$database = new Database();
$db = $database->getConnection();
$notificationService = new NotificationService();

// Check for upcoming project deadlines (3 days, 1 day, today)
$project_query = "SELECT p.*, u.id as manager_id FROM projects p 
                  LEFT JOIN users u ON p.manager_id = u.id
                  WHERE p.end_date IS NOT NULL 
                  AND p.status NOT IN ('completed', 'cancelled')
                  AND DATEDIFF(p.end_date, CURDATE()) IN (3, 1, 0)";
$project_stmt = $db->prepare($project_query);
$project_stmt->execute();
$projects = $project_stmt->fetchAll(PDO::FETCH_ASSOC);

foreach ($projects as $project) {
    $daysRemaining = (strtotime($project['end_date']) - strtotime(date('Y-m-d'))) / (60 * 60 * 24);
    
    if ($project['manager_id']) {
        $notificationService->notifyDeadlineReminder('project', $project['id'], $project['manager_id'], $daysRemaining);
    }
}

// Check for upcoming task deadlines
$task_query = "SELECT t.*, u.id as assigned_to FROM tasks t 
               LEFT JOIN users u ON t.assigned_to = u.id
               WHERE t.due_date IS NOT NULL 
               AND t.status NOT IN ('completed', 'cancelled')
               AND DATEDIFF(t.due_date, CURDATE()) IN (3, 1, 0)";
$task_stmt = $db->prepare($task_query);
$task_stmt->execute();
$tasks = $task_stmt->fetchAll(PDO::FETCH_ASSOC);

foreach ($tasks as $task) {
    $daysRemaining = (strtotime($task['due_date']) - strtotime(date('Y-m-d'))) / (60 * 60 * 24);
    
    if ($task['assigned_to']) {
        $notificationService->notifyDeadlineReminder('task', $task['id'], $task['assigned_to'], $daysRemaining);
    }
}

echo "Deadline reminders processed: " . (count($projects) + count($tasks)) . " notifications sent.\n";
?>