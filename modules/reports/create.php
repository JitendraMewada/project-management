<?php 
include '../../includes/header.php';
require_once '../../config/roles.php';

// Check permission
if (!hasPermission($current_user['role'], 'reports', 'create')) {
    header("Location: ../../dashboard/" . $current_user['role'] . ".php");
    exit();
}

// Get project_id from URL if provided
$project_id = intval($_GET['project_id'] ?? 0);

// Fetch projects for dropdown
$projects_query = "SELECT id, name, client_name FROM projects ORDER BY name";
$projects_stmt = $db->prepare($projects_query);
$projects_stmt->execute();
$projects = $projects_stmt->fetchAll(PDO::FETCH_ASSOC);

$message = '';
$error = '';

if ($_POST) {
    try {
        $project_id = intval($_POST['project_id']);
        $report_type = $_POST['report_type'];
        $title = trim($_POST['title']);
        $content = trim($_POST['content']);
        $report_date = $_POST['report_date'];
        $attachments = [];

        // Validation
        if (empty($title) || empty($content) || !$project_id || empty($report_type)) {
            throw new Exception('Please fill in all required fields.');
        }

        // Handle file attachments
        if (isset($_FILES['attachments']) && !empty($_FILES['attachments']['name'][0])) {
            $upload_dir = '../../uploads/reports/';
            if (!is_dir($upload_dir)) {
                mkdir($upload_dir, 0755, true);
            }
            
            $allowed_extensions = ['pdf', 'doc', 'docx', 'jpg', 'jpeg', 'png', 'gif', 'xls', 'xlsx'];
            
            foreach ($_FILES['attachments']['name'] as $key => $filename) {
                if ($_FILES['attachments']['error'][$key] == 0) {
                    $file_extension = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
                    
                    if (in_array($file_extension, $allowed_extensions)) {
                        $new_filename = uniqid() . '_' . $filename;
                        $filepath = $upload_dir . $new_filename;
                        
                        if (move_uploaded_file($_FILES['attachments']['tmp_name'][$key], $filepath)) {
                            $attachments[] = [
                                'filename' => $filename,
                                'path' => 'uploads/reports/' . $new_filename,
                                'size' => $_FILES['attachments']['size'][$key],
                                'type' => $_FILES['attachments']['type'][$key]
                            ];
                        }
                    }
                }
            }
        }

        // Insert report
        $query = "INSERT INTO reports (project_id, report_type, title, content, attachments, created_by, created_date, updated_at) 
                  VALUES (?, ?, ?, ?, ?, ?, NOW(), NOW())";

        $stmt = $db->prepare($query);
        $stmt->execute([
            $project_id, $report_type, $title, $content, 
            json_encode($attachments), $current_user['id'], $report_date
        ]);

        $message = 'Report created successfully!';
        header("Location: list.php?success=1");
        exit();

    } catch (Exception $e) {
        $error = $e->getMessage();
    }
}
?>

<div class="container-fluid">
    <div class="row">
        <div class="col-md-3">
            <?php include '../../includes/sidebar.php'; ?>
        </div>
        <div class="col-md-9">
            <div class="main-content p-4">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h2><i class="fas fa-file-alt"></i> Create New Report</h2>
                    <a href="list.php" class="btn btn-secondary">
                        <i class="fas fa-arrow-left"></i> Back to Reports
                    </a>
                </div>

                <?php if ($error): ?>
                <div class="alert alert-danger"><?php echo $error; ?></div>
                <?php endif; ?>

                <?php if ($message): ?>
                <div class="alert alert-success"><?php echo $message; ?></div>
                <?php endif; ?>

                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0">Report Details</h5>
                    </div>
                    <div class="card-body">
                        <form method="POST" enctype="multipart/form-data" class="needs-validation" novalidate>
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label class="form-label">Project <span class="text-danger">*</span></label>
                                        <select class="form-control" name="project_id" required>
                                            <option value="">Select Project</option>
                                            <?php foreach ($projects as $project): ?>
                                            <option value="<?php echo $project['id']; ?>"
                                                <?php echo ($project_id == $project['id'] || ($_POST['project_id'] ?? '') == $project['id']) ? 'selected' : ''; ?>>
                                                <?php echo htmlspecialchars($project['name']) . ' - ' . htmlspecialchars($project['client_name']); ?>
                                            </option>
                                            <?php endforeach; ?>
                                        </select>
                                        <div class="invalid-feedback">Please select a project.</div>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label class="form-label">Report Type <span class="text-danger">*</span></label>
                                        <select class="form-control" name="report_type" required>
                                            <option value="">Select Type</option>
                                            <option value="daily"
                                                <?php echo ($_POST['report_type'] ?? '') == 'daily' ? 'selected' : ''; ?>>
                                                Daily Report</option>
                                            <option value="weekly"
                                                <?php echo ($_POST['report_type'] ?? '') == 'weekly' ? 'selected' : ''; ?>>
                                                Weekly Report</option>
                                            <option value="milestone"
                                                <?php echo ($_POST['report_type'] ?? '') == 'milestone' ? 'selected' : ''; ?>>
                                                Milestone Report</option>
                                            <option value="final"
                                                <?php echo ($_POST['report_type'] ?? '') == 'final' ? 'selected' : ''; ?>>
                                                Final Report</option>
                                        </select>
                                        <div class="invalid-feedback">Please select a report type.</div>
                                    </div>
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-md-8">
                                    <div class="mb-3">
                                        <label class="form-label">Report Title <span
                                                class="text-danger">*</span></label>
                                        <input type="text" class="form-control" name="title" required
                                            value="<?php echo htmlspecialchars($_POST['title'] ?? ''); ?>"
                                            placeholder="Enter report title...">
                                        <div class="invalid-feedback">Please provide a report title.</div>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="mb-3">
                                        <label class="form-label">Report Date</label>
                                        <input type="date" class="form-control" name="report_date"
                                            value="<?php echo $_POST['report_date'] ?? date('Y-m-d'); ?>">
                                    </div>
                                </div>
                            </div>

                            <div class="mb-3">
                                <label class="form-label">Report Content <span class="text-danger">*</span></label>
                                <textarea class="form-control" name="content" rows="12" required
                                    placeholder="Enter detailed report content here..."><?php echo htmlspecialchars($_POST['content'] ?? ''); ?></textarea>
                                <div class="invalid-feedback">Please provide report content.</div>
                            </div>

                            <div class="mb-3">
                                <label class="form-label">Attachments</label>
                                <input type="file" class="form-control" name="attachments[]" multiple
                                    accept=".pdf,.doc,.docx,.jpg,.jpeg,.png,.gif,.xls,.xlsx">
                                <div class="form-text">
                                    Supported formats: PDF, DOC, DOCX, JPG, PNG, GIF, XLS, XLSX (Max: 10MB per file)
                                </div>
                            </div>

                            <!-- Report Templates -->
                            <div class="mb-3">
                                <label class="form-label">Quick Templates</label>
                                <div class="btn-group w-100" role="group">
                                    <button type="button" class="btn btn-outline-primary"
                                        onclick="loadTemplate('daily')">
                                        Daily Template
                                    </button>
                                    <button type="button" class="btn btn-outline-success"
                                        onclick="loadTemplate('weekly')">
                                        Weekly Template
                                    </button>
                                    <button type="button" class="btn btn-outline-warning"
                                        onclick="loadTemplate('milestone')">
                                        Milestone Template
                                    </button>
                                    <button type="button" class="btn btn-outline-info" onclick="loadTemplate('final')">
                                        Final Template
                                    </button>
                                </div>
                            </div>

                            <div class="d-flex justify-content-between">
                                <a href="list.php" class="btn btn-secondary">Cancel</a>
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-save"></i> Create Report
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
// Report Templates
const templates = {
    daily: `PROJECT DAILY REPORT

Date: ${new Date().toLocaleDateString()}
Project: [Project Name]
Reporter: ${<?php echo json_encode($current_user['name']); ?>}

WORK COMPLETED TODAY:
- 
- 
- 

TASKS IN PROGRESS:
- 
- 

CHALLENGES/ISSUES:
- 

NEXT DAY PLAN:
- 
- 

MATERIALS USED:
- 

TEAM PRESENT:
- 

WEATHER CONDITIONS:
[If applicable]

SAFETY NOTES:
- 

ADDITIONAL COMMENTS:
`,

    weekly: `PROJECT WEEKLY REPORT

Week of: ${new Date().toLocaleDateString()} to ${new Date(Date.now() + 6*24*60*60*1000).toLocaleDateString()}
Project: [Project Name]
Reporter: ${<?php echo json_encode($current_user['name']); ?>}

EXECUTIVE SUMMARY:
[Brief overview of week's progress]

WORK COMPLETED THIS WEEK:
- 
- 
- 

MILESTONES ACHIEVED:
- 
- 

CURRENT STATUS:
Overall Progress: ___%
Budget Status: [On track/Over/Under]

CHALLENGES AND RESOLUTIONS:
Issue: 
Resolution: 

Issue: 
Resolution: 

UPCOMING WEEK PLAN:
- 
- 
- 

RESOURCE REQUIREMENTS:
- Materials: 
- Manpower: 
- Equipment: 

QUALITY CHECKS:
- 
- 

SAFETY INCIDENTS:
[None/List incidents]

RECOMMENDATIONS:
- 
- 
`,

    milestone: `PROJECT MILESTONE REPORT

Milestone: [Milestone Name]
Completion Date: ${new Date().toLocaleDateString()}
Project: [Project Name]
Reporter: ${<?php echo json_encode($current_user['name']); ?>}

MILESTONE OVERVIEW:
[Description of completed milestone]

DELIVERABLES COMPLETED:
✓ 
✓ 
✓ 

QUALITY METRICS:
- Quality Score: ___/10
- Client Satisfaction: ___/10
- Timeline Adherence: ___/10

BUDGET ANALYSIS:
- Budgeted Amount: ₹_____
- Actual Spent: ₹_____
- Variance: ₹_____ (___%)

LESSONS LEARNED:
- 
- 

IMPACT ON PROJECT:
- Schedule Impact: 
- Budget Impact: 
- Scope Impact: 

NEXT MILESTONE:
Name: 
Target Date: 
Key Requirements: 

STAKEHOLDER APPROVAL:
[ ] Client Approved
[ ] Project Manager Approved
[ ] Quality Team Approved

ADDITIONAL NOTES:
`,

    final: `PROJECT FINAL REPORT

Project: [Project Name]
Client: [Client Name]
Completion Date: ${new Date().toLocaleDateString()}
Reporter: ${<?php echo json_encode($current_user['name']); ?>}

PROJECT OVERVIEW:
[Brief description of project scope and objectives]

PROJECT SUMMARY:
Start Date: 
End Date: 
Duration: ___ days
Final Budget: ₹_____

DELIVERABLES COMPLETED:
✓ 
✓ 
✓ 

PROJECT ACHIEVEMENTS:
- 
- 
- 

CHALLENGES OVERCOME:
Challenge: 
Solution: 

Challenge: 
Solution: 

FINAL METRICS:
- Overall Quality Score: ___/10
- Client Satisfaction: ___/10
- Timeline Performance: ___% (On time/Early/Delayed)
- Budget Performance: ___% (Under/On/Over budget)

TEAM PERFORMANCE:
Team Members: 
Performance Highlights: 
- 
- 

LESSONS LEARNED:
- 
- 
- 

CLIENT FEEDBACK:
[Summary of client feedback and testimonials]

RECOMMENDATIONS FOR FUTURE PROJECTS:
- 
- 
- 

PROJECT HANDOVER:
[ ] All deliverables handed over
[ ] Documentation provided
[ ] Training completed
[ ] Warranty information provided
[ ] Maintenance schedule shared

FINAL APPROVAL:
Client Signature: _________________ Date: _______
Project Manager: _________________ Date: _______

ADDITIONAL NOTES:
`
};

function loadTemplate(type) {
    const contentTextarea = document.querySelector('textarea[name="content"]');
    const titleInput = document.querySelector('input[name="title"]');
    const typeSelect = document.querySelector('select[name="report_type"]');

    // Set report type
    typeSelect.value = type;

    // Set template content
    contentTextarea.value = templates[type];

    // Set suggested title
    const titles = {
        daily: `Daily Report - ${new Date().toLocaleDateString()}`,
        weekly: `Weekly Report - Week of ${new Date().toLocaleDateString()}`,
        milestone: `Milestone Report - ${new Date().toLocaleDateString()}`,
        final: `Final Project Report - ${new Date().toLocaleDateString()}`
    };

    if (!titleInput.value) {
        titleInput.value = titles[type];
    }
}

// Auto-resize textarea
document.querySelector('textarea[name="content"]').addEventListener('input', function() {
    this.style.height = 'auto';
    this.style.height = this.scrollHeight + 'px';
});
</script>

<?php include '../../includes/footer.php'; ?>