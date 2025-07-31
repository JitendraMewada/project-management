<?php include '../includes/header.php'; ?>

<div class="container-fluid">
    <div class="row">
        <div class="col-md-3">
            <?php include '../includes/sidebar.php'; ?>
        </div>
        <div class="col-md-9">
            <div class="main-content p-4">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h2><i class="fas fa-tachometer-alt"></i> Admin Dashboard</h2>
                    <div class="d-flex gap-2">
                        <select class="form-select" id="dateRange">
                            <option value="7">Last 7 days</option>
                            <option value="30" selected>Last 30 days</option>
                            <option value="90">Last 90 days</option>
                            <option value="365">Last year</option>
                        </select>
                        <button class="btn btn-outline-primary" id="refreshBtn">
                            <i class="fas fa-sync-alt"></i> Refresh
                        </button>
                    </div>
                </div>

                <!-- Stats Cards -->
                <div class="row mb-4">
                    <div class="col-xl-3 col-md-6">
                        <div class="card gradient-card-blue text-white">
                            <div class="card-body d-flex justify-content-between align-items-center">
                                <div>
                                    <h3 id="totalProjects">0</h3>
                                    <p class="mb-0">Total Projects</p>
                                    <small id="projectsGrowth" class="growth-indicator"></small>
                                </div>
                                <div class="stat-icon"><i class="fas fa-project-diagram fa-2x"></i></div>
                            </div>
                        </div>
                    </div>

                    <div class="col-xl-3 col-md-6">
                        <div class="card gradient-card-green text-white">
                            <div class="card-body d-flex justify-content-between align-items-center">
                                <div>
                                    <h3 id="totalUsers">0</h3>
                                    <p class="mb-0">Active Users</p>
                                    <small id="usersGrowth" class="growth-indicator"></small>
                                </div>
                                <div class="stat-icon"><i class="fas fa-users fa-2x"></i></div>
                            </div>
                        </div>
                    </div>

                    <div class="col-xl-3 col-md-6">
                        <div class="card gradient-card-orange text-white">
                            <div class="card-body d-flex justify-content-between align-items-center">
                                <div>
                                    <h3 id="activeTasks">0</h3>
                                    <p class="mb-0">Active Tasks</p>
                                    <small id="tasksGrowth" class="growth-indicator"></small>
                                </div>
                                <div class="stat-icon"><i class="fas fa-tasks fa-2x"></i></div>
                            </div>
                        </div>
                    </div>

                    <div class="col-xl-3 col-md-6">
                        <div class="card gradient-card-purple text-white">
                            <div class="card-body d-flex justify-content-between align-items-center">
                                <div>
                                    <h3 id="totalRevenue">₹0</h3>
                                    <p class="mb-0">Total Revenue</p>
                                    <small id="revenueGrowth" class="growth-indicator"></small>
                                </div>
                                <div class="stat-icon"><i class="fas fa-rupee-sign fa-2x"></i></div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Charts Row -->
                <div class="row mb-4">
                    <div class="col-xl-8">
                        <div class="card">
                            <div class="card-header">
                                <h5 class="mb-0"><i class="fas fa-chart-line"></i> Project Progress Analytics</h5>
                            </div>
                            <div class="card-body">
                                <canvas id="projectProgressChart" height="300"></canvas>
                            </div>
                        </div>
                    </div>

                    <div class="col-xl-4">
                        <div class="card">
                            <div class="card-header">
                                <h5 class="mb-0"><i class="fas fa-chart-doughnut"></i> Project Status Distribution</h5>
                            </div>
                            <div class="card-body">
                                <canvas id="projectStatusChart" height="300"></canvas>
                                <div class="mt-3" id="statusLegend"></div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Additional sections below if needed -->

            </div>
        </div>
    </div>
</div>

<style>
/* Your previous gradient card and styling CSS here ... */
/* For example: */
.gradient-card-blue {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
}

.gradient-card-green {
    background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);
}

.gradient-card-orange {
    background: linear-gradient(135deg, #fa709a 0%, #fee140 100%);
}

.gradient-card-purple {
    background: linear-gradient(135deg, #a8edea 0%, #fed6e3 100%);
}

.stat-icon {
    opacity: 0.8;
}

.growth-indicator {
    font-size: 0.85rem;
    opacity: 0.9;
}

#projectStatusChart {
    max-height: 350px;
    min-height: 250px;
}
</style>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
let projectProgressChart = null;
let projectStatusChart = null;
let refreshIntervalId = null;

document.addEventListener('DOMContentLoaded', () => {
    initCharts();
    loadDashboardData();

    document.getElementById('refreshBtn').addEventListener('click', () => {
        loadDashboardData();
    });

    document.getElementById('dateRange').addEventListener('change', () => {
        loadDashboardData();
    });

    // Auto refresh every 30 seconds
    refreshIntervalId = setInterval(loadDashboardData, 30000);
});

function initCharts() {
    const ctxProgress = document.getElementById('projectProgressChart').getContext('2d');
    projectProgressChart = new Chart(ctxProgress, {
        type: 'line',
        data: {
            labels: [],
            datasets: []
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    position: 'top'
                }
            },
            scales: {
                y: {
                    beginAtZero: true
                }
            }
        },
    });

    const ctxStatus = document.getElementById('projectStatusChart').getContext('2d');
    projectStatusChart = new Chart(ctxStatus, {
        type: 'doughnut',
        data: {
            labels: ['Completed', 'In Progress', 'Planning', 'On Hold'],
            datasets: [{
                data: [0, 0, 0, 0],
                backgroundColor: ['#28a745', '#ffc107', '#17a2b8', '#6c757d'],
                borderWidth: 0
            }],
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    display: false
                }
            },
        },
    });
    renderStatusLegend([0, 0, 0, 0]);
}

function loadDashboardData() {
    const range = document.getElementById('dateRange').value || 30;
    fetch(`/project-management/api/dashboard_data.php?range=${range}`)
        .then(res => res.json())
        .then(data => {
            if (!data.success) {
                console.error('Dashboard data fetch error:', data.error ?? 'Unknown error');
                return;
            }
            updateStatCards(data.stats);
            updateProjectProgressChart(data.charts.projectProgress);
            updateProjectStatusChart(data.charts.projectStatus);
        })
        .catch(err => {
            console.error('Error fetching dashboard data:', err);
        });
}

function updateStatCards(stats) {
    document.getElementById('totalProjects').textContent = stats.totalProjects ?? 0;
    document.getElementById('totalUsers').textContent = stats.totalUsers ?? 0;
    document.getElementById('activeTasks').textContent = stats.activeTasks ?? 0;
    document.getElementById('totalRevenue').textContent = '₹' + (stats.totalRevenue ?? 0).toLocaleString();

    updateGrowthIndicator('projectsGrowth', stats.projectsGrowth);
    updateGrowthIndicator('usersGrowth', stats.usersGrowth);
    updateGrowthIndicator('tasksGrowth', stats.tasksGrowth, '% completion rate');
    updateGrowthIndicator('revenueGrowth', stats.revenueGrowth);
}

function updateGrowthIndicator(id, value, suffix = '% from last month') {
    const element = document.getElementById(id);
    if (!element) return;
    const positive = Number(value) >= 0;
    const arrow = positive ? 'up' : 'down';
    element.innerHTML = `<i class="fas fa-arrow-${arrow}"></i> ${(positive ? '+' : '')}${value ?? 0}${suffix}`;
    element.className = `growth-indicator text-${positive ? 'success' : 'danger'}`;
}

function updateProjectProgressChart(data) {
    if (!projectProgressChart || !data) return;
    projectProgressChart.data.labels = data.labels ?? [];
    projectProgressChart.data.datasets = [{
            label: 'Projects Completed',
            data: data.completed ?? [],
            borderColor: '#667eea',
            backgroundColor: 'rgba(102, 126, 234, 0.1)',
            fill: true,
            tension: 0.4,
        },
        {
            label: 'Projects Started',
            data: data.started ?? [],
            borderColor: '#764ba2',
            backgroundColor: 'rgba(118, 75, 162, 0.1)',
            fill: true,
            tension: 0.4,
        }
    ];
    projectProgressChart.update();
}

function updateProjectStatusChart(statusData) {
    if (!projectStatusChart || !statusData) return;
    const completed = Number(statusData.completed) || 0;
    const inProgress = Number(statusData.in_progress) || 0;
    const planning = Number(statusData.planning) || 0;
    const onHold = Number(statusData.on_hold) || 0;

    projectStatusChart.data.datasets[0].data = [completed, inProgress, planning, onHold];
    projectStatusChart.update();
    renderStatusLegend([completed, inProgress, planning, onHold]);
}

function renderStatusLegend(arr) {
    const [completed, inProgress, planning, onHold] = arr;
    document.getElementById('statusLegend').innerHTML = `
    <div class="row text-center">
      <div class="col-6 mb-2">
        <div class="d-flex align-items-center">
          <div style="width: 12px; height:12px; background: #28a745; border-radius: 50%; margin-right: 8px;"></div>
          <small>Completed (${completed})</small>
        </div>
      </div>
      <div class="col-6 mb-2">
        <div class="d-flex align-items-center">
          <div style="width: 12px; height:12px; background: #ffc107; border-radius: 50%; margin-right: 8px;"></div>
          <small>In Progress (${inProgress})</small>
        </div>
      </div>
      <div class="col-6">
        <div class="d-flex align-items-center">
          <div style="width: 12px; height:12px; background: #17a2b8; border-radius: 50%; margin-right: 8px;"></div>
          <small>Planning (${planning})</small>
        </div>
      </div>
      <div class="col-6">
        <div class="d-flex align-items-center">
          <div style="width: 12px; height:12px; background: #6c757d; border-radius: 50%; margin-right: 8px;"></div>
          <small>On Hold (${onHold})</small>
        </div>
      </div>
    </div>
  `;
}
</script>

<?php include '../includes/footer.php'; ?>