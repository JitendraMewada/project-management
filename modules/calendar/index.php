<?php 
include '../../includes/header.php';
require_once '../../config/roles.php';

// Check permission
if (!hasPermission($current_user['role'], 'calendar', 'read')) {
    header("Location: ../../dashboard/" . $current_user['role'] . ".php");
    exit();
}

// Fetch user's events
$events_query = "SELECT e.*, 
                 CASE 
                     WHEN e.related_type = 'project' THEN p.name
                     WHEN e.related_type = 'task' THEN t.title
                     ELSE e.title
                 END as related_name
                 FROM calendar_events e
                 LEFT JOIN projects p ON e.related_type = 'project' AND e.related_id = p.id
                 LEFT JOIN tasks t ON e.related_type = 'task' AND e.related_id = t.id
                 WHERE e.created_by = ? OR JSON_CONTAINS(e.attendees, ?)
                 ORDER BY e.start_date";
$events_stmt = $db->prepare($events_query);
$events_stmt->execute([$current_user['id'], '"' . $current_user['id'] . '"']);
$events = $events_stmt->fetchAll(PDO::FETCH_ASSOC);

// Fetch projects for event creation
$projects_query = "SELECT id, name FROM projects WHERE status != 'cancelled' ORDER BY name";
$projects_stmt = $db->prepare($projects_query);
$projects_stmt->execute();
$projects = $projects_stmt->fetchAll(PDO::FETCH_ASSOC);

// Fetch users for attendee selection
$users_query = "SELECT id, name FROM users WHERE status = 'active' ORDER BY name";
$users_stmt = $db->prepare($users_query);
$users_stmt->execute();
$users = $users_stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<div class="container-fluid">
    <div class="row">
        <div class="col-md-3">
            <?php include '../../includes/sidebar.php'; ?>
        </div>
        <div class="col-md-9">
            <div class="main-content p-4">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h2><i class="fas fa-calendar-alt"></i> Project Calendar</h2>
                    <div class="btn-group">
                        <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#createEventModal">
                            <i class="fas fa-plus"></i> New Event
                        </button>
                        <button class="btn btn-outline-info" onclick="syncGoogleCalendar()">
                            <i class="fab fa-google"></i> Sync Google
                        </button>
                        <div class="btn-group">
                            <button class="btn btn-outline-secondary dropdown-toggle" data-bs-toggle="dropdown">
                                <i class="fas fa-eye"></i> View
                            </button>
                            <ul class="dropdown-menu">
                                <li><a class="dropdown-item" href="#" onclick="changeView('month')">Month</a></li>
                                <li><a class="dropdown-item" href="#" onclick="changeView('week')">Week</a></li>
                                <li><a class="dropdown-item" href="#" onclick="changeView('day')">Day</a></li>
                                <li><a class="dropdown-item" href="#" onclick="changeView('agenda')">Agenda</a></li>
                            </ul>
                        </div>
                    </div>
                </div>

                <!-- Calendar Controls -->
                <div class="card mb-4">
                    <div class="card-body">
                        <div class="row align-items-center">
                            <div class="col-md-6">
                                <div class="btn-group">
                                    <button class="btn btn-outline-primary" onclick="navigateCalendar('prev')">
                                        <i class="fas fa-chevron-left"></i>
                                    </button>
                                    <button class="btn btn-outline-primary" onclick="navigateCalendar('today')">
                                        Today
                                    </button>
                                    <button class="btn btn-outline-primary" onclick="navigateCalendar('next')">
                                        <i class="fas fa-chevron-right"></i>
                                    </button>
                                </div>
                                <h4 id="calendarTitle" class="d-inline ms-3">Loading...</h4>
                            </div>
                            <div class="col-md-6">
                                <div class="d-flex justify-content-end">
                                    <div class="form-check form-check-inline">
                                        <input class="form-check-input" type="checkbox" id="showMeetings" checked>
                                        <label class="form-check-label" for="showMeetings">
                                            <i class="fas fa-users text-primary"></i> Meetings
                                        </label>
                                    </div>
                                    <div class="form-check form-check-inline">
                                        <input class="form-check-input" type="checkbox" id="showDeadlines" checked>
                                        <label class="form-check-label" for="showDeadlines">
                                            <i class="fas fa-clock text-danger"></i> Deadlines
                                        </label>
                                    </div>
                                    <div class="form-check form-check-inline">
                                        <input class="form-check-input" type="checkbox" id="showMilestones" checked>
                                        <label class="form-check-label" for="showMilestones">
                                            <i class="fas fa-flag text-success"></i> Milestones
                                        </label>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Calendar Widget -->
                <div class="card">
                    <div class="card-body">
                        <div id="calendar"></div>
                    </div>
                </div>

                <!-- Upcoming Events Sidebar -->
                <div class="row mt-4">
                    <div class="col-md-8">
                        <div class="card">
                            <div class="card-header">
                                <h6 class="mb-0"><i class="fas fa-clock"></i> Today's Schedule</h6>
                            </div>
                            <div class="card-body" id="todaySchedule">
                                <!-- Today's events will be populated here -->
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="card">
                            <div class="card-header">
                                <h6 class="mb-0"><i class="fas fa-list"></i> Upcoming Events</h6>
                            </div>
                            <div class="card-body" id="upcomingEvents">
                                <!-- Upcoming events will be populated here -->
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Create Event Modal -->
<div class="modal fade" id="createEventModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Create New Event</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="createEventForm">
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label">Event Title <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" name="title" required>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label">Event Type</label>
                                <select class="form-control" name="event_type">
                                    <option value="meeting">Meeting</option>
                                    <option value="deadline">Deadline</option>
                                    <option value="milestone">Milestone</option>
                                    <option value="task">Task</option>
                                    <option value="project_start">Project Start</option>
                                    <option value="project_end">Project End</option>
                                </select>
                            </div>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Description</label>
                        <textarea class="form-control" name="description" rows="3"></textarea>
                    </div>

                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label">Start Date & Time</label>
                                <input type="datetime-local" class="form-control" name="start_date" required>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label">End Date & Time</label>
                                <input type="datetime-local" class="form-control" name="end_date" required>
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label">Related Project</label>
                                <select class="form-control" name="related_project">
                                    <option value="">Select Project (Optional)</option>
                                    <?php foreach ($projects as $project): ?>
                                    <option value="<?php echo $project['id']; ?>">
                                        <?php echo htmlspecialchars($project['name']); ?>
                                    </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label">Location</label>
                                <input type="text" class="form-control" name="location"
                                    placeholder="Meeting room, address, or online link">
                            </div>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Attendees</label>
                        <select class="form-control" name="attendees[]" multiple>
                            <?php foreach ($users as $user): ?>
                            <option value="<?php echo $user['id']; ?>">
                                <?php echo htmlspecialchars($user['name']); ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                        <div class="form-text">Hold Ctrl/Cmd to select multiple attendees</div>
                    </div>

                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" name="is_all_day" id="allDayEvent">
                                <label class="form-check-label" for="allDayEvent">
                                    All Day Event
                                </label>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label">Reminder</label>
                                <select class="form-control" name="reminder_minutes">
                                    <option value="0">No reminder</option>
                                    <option value="15" selected>15 minutes before</option>
                                    <option value="30">30 minutes before</option>
                                    <option value="60">1 hour before</option>
                                    <option value="1440">1 day before</option>
                                </select>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Create Event</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Include FullCalendar CSS and JS -->
<link href='https://cdn.jsdelivr.net/npm/fullcalendar@6.1.10/index.global.min.css' rel='stylesheet' />
<script src='https://cdn.jsdelivr.net/npm/fullcalendar@6.1.10/index.global.min.js'></script>

<style>
.fc-event {
    cursor: pointer;
}

.fc-event-meeting {
    background-color: #007bff;
    border-color: #007bff;
}

.fc-event-deadline {
    background-color: #dc3545;
    border-color: #dc3545;
}

.fc-event-milestone {
    background-color: #28a745;
    border-color: #28a745;
}

.fc-event-task {
    background-color: #ffc107;
    border-color: #ffc107;
    color: #000;
}

.fc-event-project {
    background-color: #17a2b8;
    border-color: #17a2b8;
}

.event-item {
    padding: 8px 12px;
    margin-bottom: 8px;
    border-radius: 6px;
    border-left: 4px solid;
}

.event-meeting {
    border-left-color: #007bff;
    background-color: #e3f2fd;
}

.event-deadline {
    border-left-color: #dc3545;
    background-color: #ffebee;
}

.event-milestone {
    border-left-color: #28a745;
    background-color: #e8f5e8;
}

.event-task {
    border-left-color: #ffc107;
    background-color: #fff8e1;
}
</style>

<script>
let calendar;
let eventsData = <?php echo json_encode($events); ?>;

document.addEventListener('DOMContentLoaded', function() {
    initializeCalendar();
    loadTodaySchedule();
    loadUpcomingEvents();
});

function initializeCalendar() {
    const calendarEl = document.getElementById('calendar');

    calendar = new FullCalendar.Calendar(calendarEl, {
        initialView: 'dayGridMonth',
        headerToolbar: false, // We'll use custom toolbar
        events: eventsData.map(event => ({
            id: event.id,
            title: event.title,
            start: event.start_date,
            end: event.end_date,
            className: 'fc-event-' + event.event_type,
            extendedProps: {
                description: event.description,
                type: event.event_type,
                location: event.location,
                attendees: event.attendees
            }
        })),
        eventClick: function(info) {
            showEventDetails(info.event);
        },
        datesSet: function(info) {
            document.getElementById('calendarTitle').textContent = info.view.title;
        },
        selectable: true,
        select: function(info) {
            openCreateEventModal(info.start, info.end);
        }
    });

    calendar.render();
}

function changeView(viewName) {
    const viewMap = {
        'month': 'dayGridMonth',
        'week': 'timeGridWeek',
        'day': 'timeGridDay',
        'agenda': 'listWeek'
    };

    calendar.changeView(viewMap[viewName]);
}

function navigateCalendar(direction) {
    if (direction === 'prev') {
        calendar.prev();
    } else if (direction === 'next') {
        calendar.next();
    } else if (direction === 'today') {
        calendar.today();
    }
}

function openCreateEventModal(start = null, end = null) {
    const modal = new bootstrap.Modal(document.getElementById('createEventModal'));

    if (start) {
        document.querySelector('input[name="start_date"]').value =
            start.toISOString().slice(0, 16);
    }

    if (end) {
        document.querySelector('input[name="end_date"]').value =
            end.toISOString().slice(0, 16);
    }

    modal.show();
}

function showEventDetails(event) {
    alert(`Event: ${event.title}\nType: ${event.extendedProps.type}\nStart: ${event.start}\nEnd: ${event.end}`);
    // Implement detailed event view modal
}

function loadTodaySchedule() {
    const today = new Date().toISOString().split('T')[0];
    const todayEvents = eventsData.filter(event =>
        event.start_date.startsWith(today)
    );

    const scheduleHtml = todayEvents.length ?
        todayEvents.map(event => `
            <div class="event-item event-${event.event_type}">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h6 class="mb-1">${event.title}</h6>
                        <small>${new Date(event.start_date).toLocaleTimeString([], {hour: '2-digit', minute:'2-digit'})}</small>
                    </div>
                    <span class="badge bg-primary">${event.event_type}</span>
                </div>
            </div>
        `).join('') :
        '<p class="text-muted text-center">No events scheduled for today</p>';

    document.getElementById('todaySchedule').innerHTML = scheduleHtml;
}

function loadUpcomingEvents() {
    const tomorrow = new Date();
    tomorrow.setDate(tomorrow.getDate() + 1);

    const upcomingEvents = eventsData.filter(event =>
        new Date(event.start_date) >= tomorrow
    ).slice(0, 5);

    const upcomingHtml = upcomingEvents.length ?
        upcomingEvents.map(event => `
            <div class="event-item event-${event.event_type}">
                <h6 class="mb-1">${event.title}</h6>
                <small>${new Date(event.start_date).toLocaleDateString()}</small>
            </div>
        `).join('') :
        '<p class="text-muted text-center">No upcoming events</p>';

    document.getElementById('upcomingEvents').innerHTML = upcomingHtml;
}

// Create event form submission
document.getElementById('createEventForm').addEventListener('submit', function(e) {
    e.preventDefault();

    const formData = new FormData(this);

    fetch('create_event.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                bootstrap.Modal.getInstance(document.getElementById('createEventModal')).hide();
                location.reload();
            } else {
                alert('Error creating event: ' + data.message);
            }
        });
});

function syncGoogleCalendar() {
    // Implement Google Calendar sync
    alert('Google Calendar sync will be implemented with Google Calendar API');
}

// Event type filters
document.querySelectorAll('input[type="checkbox"]').forEach(checkbox => {
    checkbox.addEventListener('change', function() {
        const eventType = this.id.replace('show', '').toLowerCase();
        filterEventsByType();
    });
});

function filterEventsByType() {
    const showMeetings = document.getElementById('showMeetings').checked;
    const showDeadlines = document.getElementById('showDeadlines').checked;
    const showMilestones = document.getElementById('showMilestones').checked;

    // Implement filtering logic for calendar events
    calendar.refetchEvents();
}
</script>

<?php include '../../includes/footer.php'; ?>