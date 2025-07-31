<?php
require_once '../config/database.php';
require_once '/project-management/config/email.php'; // Email configuration



header('Content-Type: application/json');
echo json_encode(['success' => true, 'count' => 0, 'notifications' => []]);

class NotificationService {
    private $db;
    private $emailService;
    
    public function __construct() {
        $database = new Database();
        $this->db = $database->getConnection();
        $this->emailService = new EmailService();
    }
    
    public function createNotification($userId, $type, $title, $message, $relatedType = null, $relatedId = null, $actionUrl = null) {
        try {
            // Insert notification
            $query = "INSERT INTO notifications (user_id, type, title, message, related_type, related_id, action_url) 
                      VALUES (?, ?, ?, ?, ?, ?, ?)";
            $stmt = $this->db->prepare($query);
            $stmt->execute([$userId, $type, $title, $message, $relatedType, $relatedId, $actionUrl]);
            
            $notificationId = $this->db->lastInsertId();
            
            // Check if user wants email notifications
            if ($this->shouldSendEmail($userId, $type)) {
                $this->sendEmailNotification($userId, $type, [
                    'title' => $title,
                    'message' => $message,
                    'action_url' => $actionUrl
                ]);
                
                // Mark email as sent
                $this->db->prepare("UPDATE notifications SET is_email_sent = TRUE WHERE id = ?")
                         ->execute([$notificationId]);
            }
            
            return ['success' => true, 'id' => $notificationId];
            
        } catch (Exception $e) {
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }
    
    public function notifyTaskAssigned($taskId, $assignedToId, $assignedById) {
        // Get task and project details
        $query = "SELECT t.*, p.name as project_name, u1.name as assigned_to_name, u2.name as assigned_by_name
                  FROM tasks t 
                  LEFT JOIN projects p ON t.project_id = p.id
                  LEFT JOIN users u1 ON t.assigned_to = u1.id
                  LEFT JOIN users u2 ON t.assigned_by = u2.id
                  WHERE t.id = ?";
        $stmt = $this->db->prepare($query);
        $stmt->execute([$taskId]);
        $task = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($task && $assignedToId != $assignedById) {
            $title = "New Task Assigned: " . $task['title'];
            $message = "You have been assigned a new task in the " . $task['project_name'] . " project.";
            $actionUrl = "/modules/tasks/view.php?id=" . $taskId;
            
            $this->createNotification($assignedToId, 'task_assigned', $title, $message, 'task', $taskId, $actionUrl);
            
            // Notify project manager if different from assigner
            if ($task['project_id']) {
                $managerQuery = "SELECT manager_id FROM projects WHERE id = ?";
                $managerStmt = $this->db->prepare($managerQuery);
                $managerStmt->execute([$task['project_id']]);
                $managerId = $managerStmt->fetchColumn();
                
                if ($managerId && $managerId != $assignedById && $managerId != $assignedToId) {
                    $managerTitle = "Task Assigned in Your Project: " . $task['title'];
                    $managerMessage = $task['assigned_by_name'] . " assigned a task to " . $task['assigned_to_name'] . " in " . $task['project_name'] . ".";
                    
                    $this->createNotification($managerId, 'task_assigned', $managerTitle, $managerMessage, 'task', $taskId, $actionUrl);
                }
            }
        }
    }
    
    public function notifyTaskCompleted($taskId, $completedById) {
        // Get task and project details
        $query = "SELECT t.*, p.name as project_name, p.manager_id, u.name as completed_by_name
                  FROM tasks t 
                  LEFT JOIN projects p ON t.project_id = p.id
                  LEFT JOIN users u ON t.assigned_to = u.id
                  WHERE t.id = ?";
        $stmt = $this->db->prepare($query);
        $stmt->execute([$taskId]);
        $task = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($task) {
            $title = "Task Completed: " . $task['title'];
            $message = $task['completed_by_name'] . " has completed the task in " . $task['project_name'] . " project.";
            $actionUrl = "/modules/tasks/view.php?id=" . $taskId;
            
            // Notify task assigner if different from completer
            if ($task['assigned_by'] && $task['assigned_by'] != $completedById) {
                $this->createNotification($task['assigned_by'], 'task_completed', $title, $message, 'task', $taskId, $actionUrl);
            }
            
            // Notify project manager if different
            if ($task['manager_id'] && $task['manager_id'] != $completedById && $task['manager_id'] != $task['assigned_by']) {
                $this->createNotification($task['manager_id'], 'task_completed', $title, $message, 'task', $taskId, $actionUrl);
            }
        }
    }
    
    public function notifyProjectCreated($projectId, $teamMemberIds) {
        // Get project details
        $query = "SELECT * FROM projects WHERE id = ?";
        $stmt = $this->db->prepare($query);
        $stmt->execute([$projectId]);
        $project = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($project) {
            $title = "New Project Created: " . $project['name'];
            $message = "You have been assigned to a new project: " . $project['name'] . " for client " . $project['client_name'] . ".";
            $actionUrl = "/modules/projects/view.php?id=" . $projectId;
            
            foreach ($teamMemberIds as $memberId) {
                if ($memberId != $project['created_by']) {
                    $this->createNotification($memberId, 'project_created', $title, $message, 'project', $projectId, $actionUrl);
                }
            }
        }
    }
    
    public function notifyDeadlineReminder($itemType, $itemId, $userId, $daysRemaining) {
        // Get item details based on type
        $itemDetails = $this->getItemDetails($itemType, $itemId);
        
        if ($itemDetails) {
            $title = "Deadline Reminder: " . $itemDetails['name'];
            $message = "The " . $itemType . " '" . $itemDetails['name'] . "' is due in " . $daysRemaining . " day(s).";
            $actionUrl = "/modules/" . $itemType . "s/view.php?id=" . $itemId;
            
            $this->createNotification($userId, 'deadline_reminder', $title, $message, $itemType, $itemId, $actionUrl);
        }
    }
    
    public function sendWelcomeEmail($userId) {
        $query = "SELECT * FROM users WHERE id = ?";
        $stmt = $this->db->prepare($query);
        $stmt->execute([$userId]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($user) {
            $title = "Welcome to Interior Project Management";
            $message = "Your account has been created successfully. Welcome to our team!";
            $actionUrl = "/dashboard/" . $user['role'] . ".php";
            
            $this->createNotification($userId, 'user_welcome', $title, $message, 'user', $userId, $actionUrl);
        }
    }
    
    private function shouldSendEmail($userId, $type) {
        $query = "SELECT * FROM user_email_preferences WHERE user_id = ?";
        $stmt = $this->db->prepare($query);
        $stmt->execute([$userId]);
        $prefs = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$prefs) {
            // Default to sending emails if no preferences set
            return true;
        }
        
        return match($type) {
            'task_assigned' => $prefs['task_assigned'],
            'task_completed' => $prefs['task_completed'],
            'project_created' => $prefs['project_created'],
            'project_updated' => $prefs['project_updated'],
            'deadline_reminder' => $prefs['deadline_reminder'],
            'system' => $prefs['system_notifications'],
            default => true
        };
    }
    
    private function sendEmailNotification($userId, $type, $data) {
        return $this->emailService->sendNotification($userId, $type, $data);
    }
    
    private function getItemDetails($type, $id) {
        $table = $type . 's';
        $nameField = $type === 'project' ? 'name' : 'title';
        
        $query = "SELECT {$nameField} as name FROM {$table} WHERE id = ?";
        $stmt = $this->db->prepare($query);
        $stmt->execute([$id]);
        
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
    
    public function getUnreadCount($userId) {
        $query = "SELECT COUNT(*) FROM notifications WHERE user_id = ? AND is_read = FALSE";
        $stmt = $this->db->prepare($query);
        $stmt->execute([$userId]);
        
        return $stmt->fetchColumn();
    }
    
    public function markAsRead($notificationId, $userId) {
        $query = "UPDATE notifications SET is_read = TRUE, read_at = CURRENT_TIMESTAMP 
                  WHERE id = ? AND user_id = ?";
        $stmt = $this->db->prepare($query);
        $stmt->execute([$notificationId, $userId]);
        
        return $stmt->rowCount() > 0;
    }
    
    public function markAllAsRead($userId) {
        $query = "UPDATE notifications SET is_read = TRUE, read_at = CURRENT_TIMESTAMP 
                  WHERE user_id = ? AND is_read = FALSE";
        $stmt = $this->db->prepare($query);
        $stmt->execute([$userId]);
        
        return $stmt->rowCount();
    }
}
?>