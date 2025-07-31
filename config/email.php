<?php
// Email configuration for Interior Project Management System
require_once __DIR__ . '/../vendor/autoload.php';
 // PHPMailer via Composer

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

class EmailService {
    private $mailer;
    
    public function __construct() {
        $this->mailer = new PHPMailer(true);
        $this->configureSMTP();
    }
    
    private function configureSMTP() {
        try {
            // Server settings
            $this->mailer->isSMTP();
            $this->mailer->Host       = 'smtp.gmail.com'; // Set your SMTP server
            $this->mailer->SMTPAuth   = true;
            $this->mailer->Username   = 'your-email@gmail.com'; // Your email
            $this->mailer->Password   = 'your-app-password';     // Your app password
            $this->mailer->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
            $this->mailer->Port       = 587;
            
            // Default sender
            $this->mailer->setFrom('your-email@gmail.com', 'Interior Project Management');
            
        } catch (Exception $e) {
            error_log("Email configuration error: " . $e->getMessage());
        }
    }
    
    public function sendEmail($to, $subject, $body, $isHTML = true, $attachments = []) {
        try {
            $this->mailer->clearAddresses();
            $this->mailer->clearAttachments();
            
            // Recipients
            if (is_array($to)) {
                foreach ($to as $email => $name) {
                    if (is_numeric($email)) {
                        $this->mailer->addAddress($name);
                    } else {
                        $this->mailer->addAddress($email, $name);
                    }
                }
            } else {
                $this->mailer->addAddress($to);
            }
            
            // Content
            $this->mailer->isHTML($isHTML);
            $this->mailer->Subject = $subject;
            $this->mailer->Body    = $body;
            
            // Attachments
            foreach ($attachments as $attachment) {
                if (is_array($attachment)) {
                    $this->mailer->addAttachment($attachment['path'], $attachment['name']);
                } else {
                    $this->mailer->addAttachment($attachment);
                }
            }
            
            $this->mailer->send();
            return ['success' => true, 'message' => 'Email sent successfully'];
            
        } catch (Exception $e) {
            return ['success' => false, 'message' => $this->mailer->ErrorInfo];
        }
    }
    
    public function sendNotification($userId, $type, $data) {
        // Get user email
        global $db;
        $query = "SELECT email, name FROM users WHERE id = ? AND status = 'active'";
        $stmt = $db->prepare($query);
        $stmt->execute([$userId]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$user) {
            return ['success' => false, 'message' => 'User not found'];
        }
        
        $template = $this->getEmailTemplate($type, $data);
        
        return $this->sendEmail(
            [$user['email'] => $user['name']], 
            $template['subject'], 
            $template['body']
        );
    }
    
    private function getEmailTemplate($type, $data) {
        $templates = [
            'task_assigned' => [
                'subject' => 'New Task Assigned: ' . $data['task_title'],
                'body' => $this->generateTaskAssignedEmail($data)
            ],
            'task_completed' => [
                'subject' => 'Task Completed: ' . $data['task_title'],
                'body' => $this->generateTaskCompletedEmail($data)
            ],
            'project_created' => [
                'subject' => 'New Project Created: ' . $data['project_name'],
                'body' => $this->generateProjectCreatedEmail($data)
            ],
            'project_updated' => [
                'subject' => 'Project Update: ' . $data['project_name'],
                'body' => $this->generateProjectUpdatedEmail($data)
            ],
            'deadline_reminder' => [
                'subject' => 'Deadline Reminder: ' . $data['item_name'],
                'body' => $this->generateDeadlineReminderEmail($data)
            ],
            'user_welcome' => [
                'subject' => 'Welcome to Interior Project Management',
                'body' => $this->generateWelcomeEmail($data)
            ]
        ];
        
        return $templates[$type] ?? [
            'subject' => 'Notification from Interior Project Management',
            'body' => 'You have a new notification.'
        ];
    }
    
    private function generateTaskAssignedEmail($data) {
        return "
        <div style='font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto;'>
            <div style='background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 20px; text-align: center;'>
                <h2>🎯 New Task Assigned</h2>
            </div>
            
            <div style='padding: 20px; background: #f8f9fa;'>
                <h3>Hello {$data['assigned_to_name']},</h3>
                <p>You have been assigned a new task in the <strong>{$data['project_name']}</strong> project.</p>
                
                <div style='background: white; padding: 15px; border-radius: 8px; margin: 20px 0;'>
                    <h4 style='color: #667eea; margin-top: 0;'>{$data['task_title']}</h4>
                    <p><strong>Priority:</strong> <span style='color: " . $this->getPriorityColor($data['priority']) . ";'>" . ucfirst($data['priority']) . "</span></p>
                    <p><strong>Due Date:</strong> {$data['due_date']}</p>
                    <p><strong>Description:</strong></p>
                    <p>{$data['description']}</p>
                </div>
                
                <div style='text-align: center; margin: 30px 0;'>
                    <a href='{$data['task_url']}' style='background: #667eea; color: white; padding: 12px 30px; text-decoration: none; border-radius: 25px; display: inline-block;'>
                        View Task Details
                    </a>
                </div>
                
                <p>Best regards,<br>Interior Project Management Team</p>
            </div>
            
            <div style='background: #6c757d; color: white; padding: 15px; text-align: center; font-size: 12px;'>
                <p>This is an automated message from Interior Project Management System</p>
            </div>
        </div>";
    }
    
    private function generateTaskCompletedEmail($data) {
        return "
        <div style='font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto;'>
            <div style='background: linear-gradient(135deg, #28a745 0%, #20c997 100%); color: white; padding: 20px; text-align: center;'>
                <h2>✅ Task Completed</h2>
            </div>
            
            <div style='padding: 20px; background: #f8f9fa;'>
                <h3>Great News!</h3>
                <p><strong>{$data['completed_by_name']}</strong> has completed a task in the <strong>{$data['project_name']}</strong> project.</p>
                
                <div style='background: white; padding: 15px; border-radius: 8px; margin: 20px 0;'>
                    <h4 style='color: #28a745; margin-top: 0;'>{$data['task_title']}</h4>
                    <p><strong>Completed on:</strong> {$data['completion_date']}</p>
                    <p><strong>Time taken:</strong> {$data['actual_hours']} hours</p>
                </div>
                
                <div style='text-align: center; margin: 30px 0;'>
                    <a href='{$data['project_url']}' style='background: #28a745; color: white; padding: 12px 30px; text-decoration: none; border-radius: 25px; display: inline-block;'>
                        View Project Progress
                    </a>
                </div>
                
                <p>Keep up the excellent work!</p>
                <p>Best regards,<br>Interior Project Management Team</p>
            </div>
        </div>";
    }
    
    private function generateProjectCreatedEmail($data) {
        return "
        <div style='font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto;'>
            <div style='background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%); color: white; padding: 20px; text-align: center;'>
                <h2>🏗️ New Project Created</h2>
            </div>
            
            <div style='padding: 20px; background: #f8f9fa;'>
                <h3>Hello {$data['team_member_name']},</h3>
                <p>You have been assigned to a new project: <strong>{$data['project_name']}</strong></p>
                
                <div style='background: white; padding: 15px; border-radius: 8px; margin: 20px 0;'>
                    <h4 style='color: #4facfe; margin-top: 0;'>{$data['project_name']}</h4>
                    <p><strong>Client:</strong> {$data['client_name']}</p>
                    <p><strong>Your Role:</strong> {$data['user_role']}</p>
                    <p><strong>Start Date:</strong> {$data['start_date']}</p>
                    <p><strong>Expected Completion:</strong> {$data['end_date']}</p>
                    <p><strong>Budget:</strong> ₹{$data['budget']}</p>
                </div>
                
                <div style='text-align: center; margin: 30px 0;'>
                    <a href='{$data['project_url']}' style='background: #4facfe; color: white; padding: 12px 30px; text-decoration: none; border-radius: 25px; display: inline-block;'>
                        View Project Details
                    </a>
                </div>
                
                <p>Let's make this project a success!</p>
                <p>Best regards,<br>Interior Project Management Team</p>
            </div>
        </div>";
    }
    
    private function generateDeadlineReminderEmail($data) {
        return "
        <div style='font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto;'>
            <div style='background: linear-gradient(135deg, #fa709a 0%, #fee140 100%); color: white; padding: 20px; text-align: center;'>
                <h2>⏰ Deadline Reminder</h2>
            </div>
            
            <div style='padding: 20px; background: #f8f9fa;'>
                <h3>Hello {$data['user_name']},</h3>
                <p style='color: #dc3545; font-weight: bold;'>This is a friendly reminder that the following item is due soon:</p>
                
                <div style='background: white; padding: 15px; border-radius: 8px; margin: 20px 0; border-left: 4px solid #dc3545;'>
                    <h4 style='color: #dc3545; margin-top: 0;'>{$data['item_name']}</h4>
                    <p><strong>Type:</strong> {$data['item_type']}</p>
                    <p><strong>Due Date:</strong> <span style='color: #dc3545;'>{$data['due_date']}</span></p>
                    <p><strong>Days Remaining:</strong> <span style='color: #dc3545;'>{$data['days_remaining']}</span></p>
                </div>
                
                <div style='text-align: center; margin: 30px 0;'>
                    <a href='{$data['item_url']}' style='background: #dc3545; color: white; padding: 12px 30px; text-decoration: none; border-radius: 25px; display: inline-block;'>
                        Take Action Now
                    </a>
                </div>
                
                <p>Don't let deadlines slip by!</p>
                <p>Best regards,<br>Interior Project Management Team</p>
            </div>
        </div>";
    }
    
    private function generateWelcomeEmail($data) {
        return "
        <div style='font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto;'>
            <div style='background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 30px; text-align: center;'>
                <h1>🎉 Welcome to Interior Project Management!</h1>
            </div>
            
            <div style='padding: 30px; background: #f8f9fa;'>
                <h3>Hello {$data['user_name']},</h3>
                <p>Welcome to our Interior Design & Contractor Project Management System! We're excited to have you on board.</p>
                
                <div style='background: white; padding: 20px; border-radius: 8px; margin: 20px 0;'>
                    <h4 style='color: #667eea; margin-top: 0;'>Your Account Details:</h4>
                    <p><strong>Name:</strong> {$data['user_name']}</p>
                    <p><strong>Email:</strong> {$data['user_email']}</p>
                    <p><strong>Role:</strong> {$data['user_role']}</p>
                    <p><strong>Login URL:</strong> <a href='{$data['login_url']}'>{$data['login_url']}</a></p>
                </div>
                
                <div style='background: #e3f2fd; padding: 15px; border-radius: 8px; margin: 20px 0;'>
                    <h5 style='color: #1976d2; margin-top: 0;'>🚀 Quick Start Guide:</h5>
                    <ul style='margin: 0; padding-left: 20px;'>
                        <li>Complete your profile setup</li>
                        <li>Explore your dashboard</li>
                        <li>Join your assigned projects</li>
                        <li>Start collaborating with your team</li>
                    </ul>
                </div>
                
                <div style='text-align: center; margin: 30px 0;'>
                    <a href='{$data['dashboard_url']}' style='background: #667eea; color: white; padding: 15px 35px; text-decoration: none; border-radius: 25px; display: inline-block; font-size: 16px;'>
                        Get Started Now
                    </a>
                </div>
                
                <p>If you have any questions, don't hesitate to reach out to our support team.</p>
                <p>Best regards,<br>Interior Project Management Team</p>
            </div>
        </div>";
    }
    
    private function getPriorityColor($priority) {
        return match($priority) {
            'critical' => '#dc3545',
            'high' => '#fd7e14',
            'medium' => '#ffc107',
            'low' => '#6c757d',
            default => '#6c757d'
        };
    }
}
?>