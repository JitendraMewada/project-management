<?php 
include '../../includes/header.php';
require_once '../../config/roles.php';

// Fetch user notifications
$notifications_query = "SELECT n.*, 
                        CASE 
                            WHEN n.related_type = 'project' THEN p.name
                            WHEN n.related_type = 'task' THEN t.title
                            ELSE n.title
                        END as item_name
                        FROM notifications n
                        LEFT JOIN projects p ON n.related_type = 'project' AND n.related_id = p.id
                        LEFT JOIN tasks t ON n.related_type = 'task' AND n.related_id = t.id
                        WHERE n.user_id = ? 
                        ORDER BY n.created_at DESC";
$notifications_stmt = $db->prepare($notifications_query);
$notifications_stmt->execute([$current_user['id']]);
$notifications = $notifications_stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<div class="container-fluid">
    <div class="row">
        <div class="col-md-3">
            <?php include '../../includes/sidebar.php'; ?>
        </div>
        <div class="col-md-9">
            <div class="main-content p-4">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h2><i class="fas fa-bell"></i> Notifications</h2>
                    <div class="btn-group">
                        <button class="btn btn-outline-primary" onclick="markAllAsRead()">
                            <i class="fas fa-check-double"></i> Mark All Read
                        </button>
                        <button class="btn btn-outline-secondary" onclick="clearAll()">
                            <i class="fas fa-trash"></i> Clear All
                        </button>
                    </div>
                </div>

                <!-- Notification Filters -->
                <div class="card mb-4">
                    <div class="card-body">
                        <div class="row align-items-center">
                            <div class="col-md-3">
                                <select class="form-control" id="typeFilter" onchange="filterNotifications()">
                                    <option value="">All Types</option>
                                    <option value="task_assigned">Task Assigned</option>
                                    <option value="task_completed">Task Completed</option>
                                    <option value="project_created">Project Created</option>
                                    <option value="deadline_reminder">Deadline Reminder</option>
                                    <option value="system">System</option>
                                </select>
                            </div>
                            <div class="col-md-3">
                                <select class="form-control" id="statusFilter" onchange="filterNotifications()">
                                    <option value="">All Status</option>
                                    <option value="unread">Unread</option>
                                    <option value="read">Read</option>
                                </select>
                            </div>
                            <div class="col-md-4">
                                <input type="text" class="form-control" id="searchInput"
                                    placeholder="Search notifications..." onkeyup="filterNotifications()">
                            </div>
                            <div class="col-md-2">
                                <button class="btn btn-info w-100" onclick="refreshNotifications()">
                                    <i class="fas fa-sync-alt"></i> Refresh
                                </button>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Notifications List -->
                <div class="notifications-container">
                    <?php if (empty($notifications)): ?>
                    <div class="text-center py-5">
                        <i class="fas fa-bell-slash fa-3x text-muted mb-3"></i>
                        <h5 class="text-muted">No notifications yet</h5>
                        <p class="text-muted">We'll notify you when there's something important!</p>
                    </div>
                    <?php else: ?>
                    <?php foreach ($notifications as $notification): ?>
                    <div class="notification-item <?php echo $notification['is_read'] ? 'read' : 'unread'; ?>"
                        data-id="<?php echo $notification['id']; ?>" data-type="<?php echo $notification['type']; ?>"
                        data-status="<?php echo $notification['is_read'] ? 'read' : 'unread'; ?>">
                        <div class="card mb-3">
                            <div class="card-body">
                                <div class="row align-items-center">
                                    <div class="col-auto">
                                        <div
                                            class="notification-icon <?php echo getNotificationIconClass($notification['type']); ?>">
                                            <i class="<?php echo getNotificationIcon($notification['type']); ?>"></i>
                                        </div>
                                    </div>
                                    <div class="col">
                                        <div class="d-flex justify-content-between align-items-start">
                                            <div class="notification-content">
                                                <h6
                                                    class="mb-1 <?php echo !$notification['is_read'] ? 'fw-bold' : ''; ?>">
                                                    <?php echo htmlspecialchars($notification['title']); ?>
                                                    <?php if (!$notification['is_read']): ?>
                                                    <span class="badge bg-primary ms-2">New</span>
                                                    <?php endif; ?>
                                                </h6>
                                                <p class="mb-2 text-muted">
                                                    <?php echo htmlspecialchars($notification['message']); ?>
                                                </p>
                                                <small class="text-muted">
                                                    <i class="fas fa-clock"></i>
                                                    <?php echo getTimeAgo($notification['created_at']); ?>
                                                </small>
                                            </div>
                                            <div class="notification-actions">
                                                <?php if (!$notification['is_read']): ?>
                                                <button class="btn btn-sm btn-outline-primary me-2"
                                                    onclick="markAsRead(<?php echo $notification['id']; ?>)">
                                                    <i class="fas fa-check"></i> Mark Read
                                                </button>
                                                <?php endif; ?>

                                                <?php if ($notification['action_url']): ?>
                                                <a href="<?php echo htmlspecialchars($notification['action_url']); ?>"
                                                    class="btn btn-sm btn-primary">
                                                    <i class="fas fa-external-link-alt"></i> View
                                                </a>
                                                <?php endif; ?>

                                                <button class="btn btn-sm btn-outline-danger ms-2"
                                                    onclick="deleteNotification(<?php echo $notification['id']; ?>)">
                                                    <i class="fas fa-trash"></i>
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                    <?php endif; ?>
                </div>

                <!-- Pagination -->
                <div class="d-flex justify-content-center mt-4">
                    <nav>
                        <ul class="pagination">
                            <li class="page-item"><a class="page-link" href="#">Previous</a></li>
                            <li class="page-item active"><a class="page-link" href="#">1</a></li>
                            <li class="page-item"><a class="page-link" href="#">2</a></li>
                            <li class="page-item"><a class="page-link" href="#">3</a></li>
                            <li class="page-item"><a class="page-link" href="#">Next</a></li>
                        </ul>
                    </nav>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
.notification-item.unread {
    border-left: 4px solid #007bff;
}

.notification-item.unread .card {
    background-color: #f8f9ff;
}

.notification-icon {
    width: 50px;
    height: 50px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 20px;
    color: white;
}

.notification-icon.task {
    background: linear-gradient(135deg, #667eea, #764ba2);
}

.notification-icon.project {
    background: linear-gradient(135deg, #4facfe, #00f2fe);
}

.notification-icon.deadline {
    background: linear-gradient(135deg, #fa709a, #fee140);
}

.notification-icon.system {
    background: linear-gradient(135deg, #a8edea, #fed6e3);
}

.notification-icon.user {
    background: linear-gradient(135deg, #ffecd2, #fcb69f);
}

.notification-content {
    flex: 1;
}

.notification-actions {
    min-width: 200px;
    text-align: right;
}

@media (max-width: 768px) {
    .notification-actions {
        min-width: auto;
        margin-top: 10px;
    }

    .row.align-items-center {
        flex-direction: column;
        align-items: flex-start !important;
    }
}
</style>

<script>
function markAsRead(notificationId) {
    fetch('mark_read.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({
                id: notificationId
            })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                const item = document.querySelector(`[data-id="${notificationId}"]`);
                item.classList.remove('unread');
                item.classList.add('read');
                item.dataset.status = 'read';

                // Remove "New" badge and update styling
                const badge = item.querySelector('.badge');
                if (badge) badge.remove();

                const title = item.querySelector('h6');
                title.classList.remove('fw-bold');

                const card = item.querySelector('.card');
                card.style.backgroundColor = '';

                // Remove mark read button
                const markReadBtn = item.querySelector('button[onclick*="markAsRead"]');
                if (markReadBtn) markReadBtn.remove();

                updateNotificationCount();
            }
        });
}

function markAllAsRead() {
    if (confirm('Mark all notifications as read?')) {
        fetch('mark_all_read.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                }
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    location.reload();
                }
            });
    }
}

function deleteNotification(notificationId) {
    if (confirm('Delete this notification?')) {
        fetch('delete.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({
                    id: notificationId
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    document.querySelector(`[data-id="${notificationId}"]`).remove();
                    updateNotificationCount();
                }
            });
    }
}

function clearAll() {
    if (confirm('Clear all notifications? This action cannot be undone.')) {
        fetch('clear_all.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                }
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    location.reload();
                }
            });
    }
}

function filterNotifications() {
    const typeFilter = document.getElementById('typeFilter').value;
    const statusFilter = document.getElementById('statusFilter').value;
    const searchTerm = document.getElementById('searchInput').value.toLowerCase();

    const items = document.querySelectorAll('.notification-item');

    items.forEach(item => {
        const type = item.dataset.type;
        const status = item.dataset.status;
        const content = item.textContent.toLowerCase();

        const typeMatch = !typeFilter || type === typeFilter;
        const statusMatch = !statusFilter || status === statusFilter;
        const searchMatch = !searchTerm || content.includes(searchTerm);

        if (typeMatch && statusMatch && searchMatch) {
            item.style.display = '';
        } else {
            item.style.display = 'none';
        }
    });
}

function refreshNotifications() {
    location.reload();
}

function updateNotificationCount() {
    const unreadCount = document.querySelectorAll('.notification-item.unread').length;
    const badge = document.querySelector('.notification-badge');
    if (badge) {
        badge.textContent = unreadCount;
        badge.style.display = unreadCount > 0 ? 'inline' : 'none';
    }
}

// Auto-refresh notifications every 60 seconds
setInterval(refreshNotifications, 60000);
</script>

<?php
function getNotificationIcon($type) {
    return match($type) {
        'task_assigned', 'task_completed' => 'fas fa-tasks',
        'project_created', 'project_updated' => 'fas fa-project-diagram',
        'deadline_reminder' => 'fas fa-clock',
        'user_welcome', 'user_created' => 'fas fa-user-plus',
        'system' => 'fas fa-cog',
        default => 'fas fa-bell'
    };
}

function getNotificationIconClass($type) {
    return match($type) {
        'task_assigned', 'task_completed' => 'task',
        'project_created', 'project_updated' => 'project',
        'deadline_reminder' => 'deadline',
        'user_welcome', 'user_created' => 'user',
        'system' => 'system',
        default => 'system'
    };
}

function getTimeAgo($datetime) {
    $time = time() - strtotime($datetime);
    
    if ($time < 60) return 'Just now';
    if ($time < 3600) return floor($time/60) . ' minutes ago';
    if ($time < 86400) return floor($time/3600) . ' hours ago';
    if ($time < 2592000) return floor($time/86400) . ' days ago';
    
    return date('M d, Y', strtotime($datetime));
}

include '../../includes/footer.php';
?>