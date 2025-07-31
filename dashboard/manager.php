<?php include '../includes/header.php'; ?>

<div class="container-fluid">
    <div class="row">
        <div class="col-md-3">
            <?php include '../includes/sidebar.php'; ?>
        </div>
        <div class="col-md-9">
            <div class="main-content p-4">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h2><i class="fas fa-user-tie"></i> Manager Dashboard</h2>
                    <div class="d-flex gap-2">
                        <button class="btn btn-primary" onclick="createNewProject()">
                            <i class="fas fa-plus"></i> New Project
                        </button>
                        <button class="btn btn-outline-success" onclick="exportReport()">
                            <i class="fas fa-download"></i> Export Report
                        </button>
                    </div>
                </div>

                <!-- Manager-specific Stats -->
                <div class="row mb-4">
                    <div class="col-xl-3 col-md-6">
                        <div class="card border-left-primary">
                            <div class="card-body">
                                <div class="row align-items-center">
                                    <div class="col">
                                        <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">
                                            My Projects
                                        </div>
                                        <div class="h5 mb-0 font-weight-bold text-gray-800" id="myProjects">0</div>
                                    </div>
                                    <div class="col-auto">
                                        <i class="fas fa-project-diagram fa-2x text-gray-300"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="col-xl-3 col-md-6">
                        <div class="card border-left-success">
                            <div class="card-body">
                                <div class="row align-items-center">
                                    <div class="col">
                                        <div class="text-xs font-weight-bold text-success text-uppercase mb-1">
                                            Team Members
                                        </div>
                                        <div class="h5 mb-0 font-weight-bold text-gray-800" id="teamMembers">0</div>
                                    </div>
                                    <div class="col-auto">
                                        <i class="fas fa-users fa-2x text-gray-300"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="col-xl-3 col-md-6">
                        <div class="card border-left-warning">
                            <div class="card-body">
                                <div class="row align-items-center">
                                    <div class="col">
                                        <div class="text-xs font-weight-bold text-warning text-uppercase mb-1">
                                            Pending Tasks
                                        </div>
                                        <div class="h5 mb-0 font-weight-bold text-gray-800" id="pendingTasks">0</div>
                                    </div>
                                    <div class="col-auto">
                                        <i class="fas fa-clock fa-2x text-gray-300"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="col-xl-3 col-md-6">
                        <div class="card border-left-info">
                            <div class="card-body">
                                <div class="row align-items-center">
                                    <div class="col">
                                        <div class="text-xs font-weight-bold text-info text-uppercase mb-1">
                                            Budget Utilization
                                        </div>
                                        <div class="h5 mb-0 font-weight-bold text-gray-800" id="budgetUtil">0%</div>
                                    </div>
                                    <div class="col-auto">
                                        <i class="fas fa-rupee-sign fa-2x text-gray-300"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Project Overview and Team Performance -->
                <div class="row mb-4">
                    <div class="col-xl-8">
                        <div class="card">
                            <div class="card-header">
                                <h5 class="mb-0"><i class="fas fa-chart-area"></i> Project Progress Overview</h5>
                            </div>
                            <div class="card-body">
                                <canvas id="managerProjectChart" height="300"></canvas>
                            </div>
                        </div>
                    </div>

                    <div class="col-xl-4">
                        <div class="card">
                            <div class="card-header">
                                <h5 class="mb-0"><i class="fas fa-tasks"></i> Task Distribution</h5>
                            </div>
                            <div class="card-body">
                                <canvas id="taskDistributionChart" height="300"></canvas>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Projects Table with Advanced Features -->
                <div class="card">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h5 class="mb-0"><i class="fas fa-project-diagram"></i> My Projects</h5>
                        <div class="btn-group btn-group-sm">
                            <button class="btn btn-outline-primary active" onclick="filterProjects('all')">All</button>
                            <button class="btn btn-outline-primary" onclick="filterProjects('active')">Active</button>
                            <button class="btn btn-outline-primary" onclick="filterProjects('overdue')">Overdue</button>
                            <button class="btn btn-outline-primary"
                                onclick="filterProjects('completed')">Completed</button>
                        </div>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-hover" id="projectsTable">
                                <thead>
                                    <tr>
                                        <th>Project</th>
                                        <th>Client</th>
                                        <th>Progress</th>
                                        <th>Budget</th>
                                        <th>Team</th>
                                        <th>Status</th>
                                        <th>Due Date</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody id="projectsTableBody">
                                    <!-- Projects will be loaded dynamically -->
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
.border-left-primary {
    border-left: 0.25rem solid #4e73df !important;
}

.border-left-success {
    border-left: 0.25rem solid #1cc88a !important;
}

.border-left-warning {
    border-left: 0.25rem solid #f6c23e !important;
}

.border-left-info {
    border-left: 0.25rem solid #36b9cc !important;
}

.text-xs {
    font-size: 0.7rem;
}

.progress-ring {
    position: relative;
    display: inline-block;
    width: 40px;
    height: 40px;
}

.progress-ring svg {
    width: 100%;
    height: 100%;
    transform: rotate(-90deg);
}

.progress-ring circle {
    fill: transparent;
    stroke-width: 3;
    stroke-dasharray: 126;
    stroke-linecap: round;
}

.progress-ring .progress-ring-bg {
    stroke: #e9ecef;
}

.progress-ring .progress-ring-fill {
    stroke: #28a745;
    stroke-dashoffset: 126;
    transition: stroke-dashoffset 0.5s ease;
}

.team-avatars {
    display: flex;
    margin-left: -5px;
}

.team-avatar {
    width: 24px;
    height: 24px;
    border-radius: 50%;
    border: 2px solid white;
    margin-left: -5px;
    background: #6c757d;
    color: white;
    font-size: 10px;
    display: flex;
    align-items: center;
    justify-content: center;
}

.status-indicator {
    width: 10px;
    height: 10px;
    border-radius: 50%;
    display: inline-block;
    margin-right: 5px;
}

.status-active {
    background-color: #28a745;
}

.status-overdue {
    background-color: #dc3545;
}

.status-completed {
    background-color: #17a2b8;
}

.status-planning {
    background-color: #ffc107;
}
</style>

<script>
let managerProjectChart, taskDistributionChart;

document.addEventListener('DOMContentLoaded', function() {
    initializeManagerDashboard();
});

function initializeManagerDashboard() {
    loadManagerStats();
    initializeManagerCharts();
    loadManagerProjects();
}

function loadManagerStats() {
    // Mock data - replace with actual API calls
    animateCounter('myProjects', 12);
    animateCounter('teamMembers', 25);
    animateCounter('pendingTasks', 34);

    setTimeout(() => {
        document.getElementById('budgetUtil').textContent = '78%';
    }, 1000);
}

function initializeManagerCharts() {
    // Project Progress Chart
    const ctx1 = document.getElementById('managerProjectChart').getContext('2d');
    managerProjectChart = new Chart(ctx1, {
        type: 'line',
        data: {
            labels: ['Week 1', 'Week 2', 'Week 3', 'Week 4'],
            datasets: [{
                label: 'Planned Progress',
                data: [25, 50, 75, 100],
                borderColor: '#6c757d',
                backgroundColor: 'rgba(108, 117, 125, 0.1)',
                borderDash: [5, 5]
            }, {
                label: 'Actual Progress',
                data: [20, 45, 68, 85],
                borderColor: '#28a745',
                backgroundColor: 'rgba(40, 167, 69, 0.1)',
                tension: 0.4
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            scales: {
                y: {
                    beginAtZero: true,
                    max: 100,
                    ticks: {
                        callback: function(value) {
                            return value + '%';
                        }
                    }
                }
            }
        }
    });

    // Task Distribution Chart
    const ctx2 = document.getElementById('taskDistributionChart').getContext('2d');
    taskDistributionChart = new Chart(ctx2, {
        type: 'doughnut',
        data: {
            labels: ['Completed', 'In Progress', 'Pending', 'Overdue'],
            datasets: [{
                data: [45, 30, 20, 5],
                backgroundColor: [
                    '#28a745',
                    '#ffc107',
                    '#17a2b8',
                    '#dc3545'
                ],
                borderWidth: 0
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    position: 'bottom'
                }
            }
        }
    });
}

function loadManagerProjects() {
    const projects = [{
            id: 1,
            name: 'Modern Office Interior',
            client: 'Tech Corp Ltd',
            progress: 75,
            budget: '₹15,00,000',
            team: ['JD', 'SM', 'RK'],
            status: 'active',
            dueDate: '2025-08-15',
            isOverdue: false
        },
        {
            id: 2,
            name: 'Luxury Villa Design',
            client: 'Mr. Sharma',
            progress: 45,
            budget: '₹25,00,000',
            team: ['AS', 'MJ', 'PK', 'LB'],
            status: 'active',
            dueDate: '2025-09-30',
            isOverdue: false
        },
        {
            id: 3,
            name: 'Restaurant Renovation',
            client: 'Food Paradise',
            progress: 90,
            budget: '₹8,00,000',
            team: ['NK', 'RT'],
            status: 'overdue',
            dueDate: '2025-07-25',
            isOverdue: true
        }
    ];

    const tableBody = document.getElementById('projectsTableBody');
    tableBody.innerHTML = projects.map(project => `
        <tr data-status="${project.status}" class="${project.isOverdue ? 'table-warning' : ''}">
            <td>
                <div class="d-flex align-items-center">
                    <div class="progress-ring me-2">
                        <svg>
                            <circle class="progress-ring-bg" cx="20" cy="20" r="20"></circle>
                            <circle class="progress-ring-fill" cx="20" cy="20" r="20" 
                                    style="stroke-dashoffset: ${126 - (126 * project.progress / 100)};"></circle>
                        </svg>
                        <div class="position-absolute" style="top: 50%; left: 50%; transform: translate(-50%, -50%); font-size: 8px;">
                            ${project.progress}%
                        </div>
                    </div>
                    <div>
                        <h6 class="mb-0">${project.name}</h6>
                        <small class="text-muted">ID: #${project.id}</small>
                    </div>
                </div>
            </td>
            <td>${project.client}</td>
            <td>
                <div class="progress" style="height: 6px;">
                    <div class="progress-bar bg-${project.progress >= 75 ? 'success' : project.progress >= 50 ? 'warning' : 'danger'}" 
                         style="width: ${project.progress}%"></div>
                </div>
                <small class="text-muted">${project.progress}% Complete</small>
            </td>
            <td>${project.budget}</td>
            <td>
                <div class="team-avatars">
                    ${project.team.map(member => `<div class="team-avatar">${member}</div>`).join('')}
                </div>
            </td>
            <td>
                <span class="status-indicator status-${project.status}"></span>
                ${project.status.charAt(0).toUpperCase() + project.status.slice(1)}
            </td>
            <td>
                <span class="${project.isOverdue ? 'text-danger fw-bold' : ''}">
                    ${new Date(project.dueDate).toLocaleDateString()}
                    ${project.isOverdue ? '<i class="fas fa-exclamation-triangle ms-1"></i>' : ''}
                </span>
            </td>
            <td>
                <div class="btn-group btn-group-sm">
                    <button class="btn btn-outline-primary" onclick="viewProject(${project.id})">
                        <i class="fas fa-eye"></i>
                    </button>
                    <button class="btn btn-outline-success" onclick="editProject(${project.id})">
                        <i class="fas fa-edit"></i>
                    </button>
                    <button class="btn btn-outline-info" onclick="generateReport(${project.id})">
                        <i class="fas fa-chart-line"></i>
                    </button>
                </div>
            </td>
        </tr>
    `).join('');
}

function filterProjects(status) {
    const buttons = document.querySelectorAll('.btn-group .btn');
    buttons.forEach(btn => btn.classList.remove('active'));
    event.target.classList.add('active');

    const rows = document.querySelectorAll('#projectsTableBody tr');
    rows.forEach(row => {
        if (status === 'all' || row.dataset.status === status) {
            row.style.display = '';
        } else {
            row.style.display = 'none';
        }
    });
}

function createNewProject() {
    window.location.href = '../modules/projects/create.php';
}

function viewProject(id) {
    window.location.href = '../modules/projects/view.php?id=' + id;
}

function editProject(id) {
    window.location.href = '../modules/projects/edit.php?id=' + id;
}

function generateReport(id) {
    window.location.href = '../modules/reports/create.php?project_id=' + id;
}

function exportReport() {
    // Implement export functionality
    alert('Export functionality will generate a comprehensive report of all managed projects.');
}

function animateCounter(elementId, target) {
    const element = document.getElementById(elementId);
    const duration = 2000;
    const start = 0;
    const increment = target / (duration / 50);
    let current = start;

    const timer = setInterval(() => {
        current += increment;
        if (current >= target) {
            current = target;
            clearInterval(timer);
        }
        element.textContent = Math.floor(current);
    }, 50);
}
</script>

<?php include '../includes/footer.php'; ?>