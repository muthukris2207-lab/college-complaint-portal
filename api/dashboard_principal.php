<?php
/**
 * Principal Dashboard
 * AI-Powered Smart Complaint & Escalation System
 */

$pageTitle = "Principal Dashboard";
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/db.php';

// Access check: only principal
auth_require_roles(['principal']);
$user = auth_current_user();

$successMessage = "";
$errorMessage = "";

// Handle Form Submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $complaintId = $_POST['complaint_id'] ?? '';

    if ($action === 'update_status' && !empty($complaintId)) {
        $status = $_POST['status'] ?? '';
        $notes = trim($_POST['resolution_notes'] ?? '');

        if (!in_array($status, ['In Progress', 'Resolved'])) {
            $errorMessage = "Invalid status selected.";
        } elseif ($status === 'Resolved' && empty($notes)) {
            $errorMessage = "Resolution notes are required to resolve a complaint.";
        } else {
            db_query("
                UPDATE complaints 
                SET status = ?, resolution_notes = ?, updated_at = NOW() 
                WHERE id = ? AND current_handler_role = 'principal'
            ", [$status, $notes, $complaintId]);
            $successMessage = "Complaint status updated successfully.";
        }
    } elseif ($action === 'admin_override' && !empty($complaintId)) {
        $category = $_POST['category'] ?? '';
        $priority = $_POST['priority'] ?? '';

        $validCategories = ['Academic', 'Infrastructure', 'Hostel', 'Transport', 'Harassment'];
        $validPriorities = ['High', 'Medium', 'Low'];

        if (!in_array($category, $validCategories) || !in_array($priority, $validPriorities)) {
            $errorMessage = "Invalid override parameters.";
        } else {
            // Resolve new department
            $newDept = db_fetch("SELECT id FROM departments WHERE name = ?", [$category]);
            if ($newDept) {
                $newDeptId = $newDept['id'];
                
                // When re-routing to a different department, reset handler role to 'staff' 
                // and status to 'Submitted' so the new department staff can handle it.
                db_query("
                    UPDATE complaints 
                    SET category = ?, priority = ?, department_id = ?, 
                        current_handler_role = 'staff', status = 'Submitted', 
                        escalated_at = NULL, updated_at = NOW() 
                    WHERE id = ? AND current_handler_role = 'principal'
                ", [$category, $priority, $newDeptId, $complaintId]);
                
                $successMessage = "AI classification overridden. Complaint re-routed to $category Department Staff.";
            } else {
                $errorMessage = "Unable to resolve department.";
            }
        }
    }
}

// Fetch System Stats (all complaints)
$totalComplaints = db_fetch("SELECT COUNT(*) as count FROM complaints")['count'];
$pendingComplaints = db_fetch("SELECT COUNT(*) as count FROM complaints WHERE status IN ('Submitted', 'In Progress', 'Escalated')")['count'];
$escalatedToPrincipal = db_fetch("SELECT COUNT(*) as count FROM complaints WHERE current_handler_role = 'principal' AND status = 'Escalated'")['count'];
$resolvedComplaints = db_fetch("SELECT COUNT(*) as count FROM complaints WHERE status = 'Resolved'")['count'];

// Fetch department breakdown stats
$deptStats = db_fetch_all("
    SELECT d.name, COUNT(c.id) as count, SUM(CASE WHEN c.status = 'Resolved' THEN 1 ELSE 0 END) as resolved
    FROM departments d
    LEFT JOIN complaints c ON c.department_id = d.id
    GROUP BY d.id
");

// Fetch Escalated Complaints (Action Required)
$escalatedComplaints = db_fetch_all("
    SELECT c.*, d.name as department_name 
    FROM complaints c
    JOIN departments d ON c.department_id = d.id
    WHERE c.current_handler_role = 'principal'
    ORDER BY c.created_at DESC
");

// Fetch All Complaints in System
$allComplaints = db_fetch_all("
    SELECT c.*, d.name as department_name 
    FROM complaints c
    JOIN departments d ON c.department_id = d.id
    ORDER BY c.created_at DESC
");

require_once __DIR__ . '/includes/header.php';
?>

<div class="app-container">
    <div style="margin-bottom: 2rem; display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 1rem;">
        <div>
            <span style="font-size: 0.85rem; color: var(--text-muted); text-transform: uppercase; font-weight: 600;">Executive Admin Management</span>
            <h1 style="font-size: 2rem; font-weight: 800; color: var(--text-main);">Principal Portal</h1>
        </div>
        <div style="background-color: var(--primary-light); color: var(--primary); padding: 0.5rem 1rem; border-radius: var(--radius-sm); font-size: 0.9rem; font-weight: 600;" class="flex">
            <i data-lucide="crown" style="width: 16px; height: 16px; margin-right: 0.5rem;"></i> System Administrator
        </div>
    </div>

    <!-- Alert Messages -->
    <?php if (!empty($successMessage)): ?>
        <div class="alert alert-success flex">
            <i data-lucide="check-circle" style="margin-right: 0.5rem;"></i>
            <?php echo htmlspecialchars($successMessage); ?>
        </div>
    <?php endif; ?>
    <?php if (!empty($errorMessage)): ?>
        <div class="alert alert-danger flex">
            <i data-lucide="alert-circle" style="margin-right: 0.5rem;"></i>
            <?php echo htmlspecialchars($errorMessage); ?>
        </div>
    <?php endif; ?>

    <!-- Stat Grid -->
    <div class="dashboard-grid">
        <div class="stat-card">
            <div class="stat-icon"><i data-lucide="network"></i></div>
            <div class="stat-details">
                <h3>Total Complaints</h3>
                <p><?php echo $totalComplaints; ?></p>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-icon" style="background-color: #fde8e8; color: var(--danger);"><i data-lucide="alert-triangle"></i></div>
            <div class="stat-details">
                <h3>Escalated to Principal</h3>
                <p><?php echo $escalatedToPrincipal; ?></p>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-icon" style="background-color: var(--warning-light); color: var(--warning);"><i data-lucide="clock"></i></div>
            <div class="stat-details">
                <h3>Total Active Pending</h3>
                <p><?php echo $pendingComplaints; ?></p>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-icon" style="background-color: var(--success-light); color: var(--success);"><i data-lucide="check-square"></i></div>
            <div class="stat-details">
                <h3>Total Resolved</h3>
                <p><?php echo $resolvedComplaints; ?></p>
            </div>
        </div>
    </div>

    <!-- Department Summary Grid -->
    <div class="card" style="padding: 1.5rem; margin-bottom: 2rem;">
        <h2 class="card-title" style="margin-bottom: 1.25rem;"><i data-lucide="bar-chart-3" style="vertical-align: middle; margin-right: 0.5rem; color: var(--primary);"></i>Department-wise Breakdown</h2>
        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(180px, 1fr)); gap: 1rem;">
            <?php foreach ($deptStats as $ds): ?>
                <div style="background-color: var(--bg-main); border: 1px solid var(--border); padding: 1rem; border-radius: var(--radius-sm); text-align: center;">
                    <strong style="display: block; font-size: 0.95rem; margin-bottom: 0.25rem;"><?php echo htmlspecialchars($ds['name']); ?></strong>
                    <span style="font-size: 1.5rem; font-weight: 700; color: var(--primary); display: block;"><?php echo $ds['count'] ?: 0; ?></span>
                    <span style="font-size: 0.75rem; color: var(--text-muted);">Resolved: <?php echo $ds['resolved'] ?: 0; ?></span>
                </div>
            <?php endforeach; ?>
        </div>
    </div>

    <!-- Section 1: Principal Level Escalations -->
    <div class="card" style="padding: 1.5rem; border: 1px solid rgba(239, 68, 68, 0.2); background: linear-gradient(180deg, rgba(239, 68, 68, 0.03) 0%, rgba(255, 255, 255, 0) 100%);">
        <h2 class="card-title" style="color: var(--danger);"><i data-lucide="alert-octagon" style="vertical-align: middle; margin-right: 0.5rem; color: var(--danger);"></i>Action Required: Critical Escalations to Principal</h2>
        
        <?php if (empty($escalatedComplaints)): ?>
            <div class="text-center" style="padding: 2.5rem 1rem; color: var(--text-muted);">
                <i data-lucide="shield-check" style="width: 40px; height: 40px; stroke-width: 1.5; margin-bottom: 0.75rem; color: var(--success);"></i>
                <p>No critical complaints currently escalated to the Principal level.</p>
            </div>
        <?php else: ?>
            <div class="complaints-table-wrapper" style="border-color: rgba(239, 68, 68, 0.15);">
                <table>
                    <thead>
                        <tr>
                            <th>Complaint ID</th>
                            <th>Dept / Student</th>
                            <th>Priority</th>
                            <th>Summary</th>
                            <th>Escalated Date</th>
                            <th style="text-align: right;">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($escalatedComplaints as $c): ?>
                            <tr style="background-color: rgba(239, 68, 68, 0.05);">
                                <td style="font-family: monospace; font-weight: 700; color: var(--danger);">
                                    <?php echo htmlspecialchars($c['complaint_id']); ?>
                                </td>
                                <td>
                                    <strong style="font-size: 0.9rem; display: block; color: var(--text-main);"><?php echo htmlspecialchars($c['department_name']); ?></strong>
                                    <?php if ($c['is_anonymous']): ?>
                                        <span style="color: var(--text-muted); font-size: 0.8rem; font-style: italic;">Anonymous Student</span>
                                    <?php else: ?>
                                        <span style="font-size: 0.8rem; color: var(--text-muted);"><?php echo htmlspecialchars($c['student_name']); ?></span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php
                                    $pClass = 'badge-priority-low';
                                    if ($c['priority'] === 'High') $pClass = 'badge-priority-high';
                                    elseif ($c['priority'] === 'Medium') $pClass = 'badge-priority-medium';
                                    ?>
                                    <span class="badge <?php echo $pClass; ?>"><?php echo htmlspecialchars($c['priority']); ?></span>
                                </td>
                                <td style="max-width: 250px;">
                                    <div style="font-weight: 500; font-size: 0.9rem;"><?php echo htmlspecialchars($c['summary']); ?></div>
                                    <div style="font-size: 0.75rem; color: var(--text-muted); margin-top: 0.25rem;">Filed: <?php echo date('M d, Y h:i A', strtotime($c['created_at'])); ?></div>
                                </td>
                                <td>
                                    <span class="badge badge-status-escalated">Principal Level</span>
                                    <div style="font-size: 0.7rem; color: var(--text-muted); margin-top: 0.25rem; font-weight: 500;">
                                        Since: <?php echo date('M d, Y', strtotime($c['escalated_at'])); ?>
                                    </div>
                                </td>
                                <td style="text-align: right;">
                                    <div style="display: flex; gap: 0.5rem; justify-content: flex-end;">
                                        <button onclick="openActionModal(<?php echo htmlspecialchars(json_encode($c)); ?>)" class="btn btn-danger flex" style="padding: 0.4rem 0.75rem; font-size: 0.8rem;">
                                            <i data-lucide="edit-3" style="width: 14px; height: 14px;"></i> Update
                                        </button>
                                        <button onclick="openOverrideModal(<?php echo htmlspecialchars(json_encode($c)); ?>)" class="btn btn-secondary flex" style="padding: 0.4rem 0.75rem; font-size: 0.8rem;">
                                            <i data-lucide="settings" style="width: 14px; height: 14px;"></i> Override
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>

    <!-- Section 2: Full System Log -->
    <div class="card" style="padding: 1.5rem;">
        <h2 class="card-title"><i data-lucide="activity" style="vertical-align: middle; margin-right: 0.5rem; color: var(--primary);"></i>College-Wide Overview Log</h2>
        
        <?php if (empty($allComplaints)): ?>
            <div class="text-center" style="padding: 3rem 1rem; color: var(--text-muted);">
                <i data-lucide="inbox" style="width: 48px; height: 48px; stroke-width: 1.5; margin-bottom: 1rem;"></i>
                <p>No complaints submitted to the system yet.</p>
            </div>
        <?php else: ?>
            <div class="complaints-table-wrapper">
                <table>
                    <thead>
                        <tr>
                            <th>Complaint ID</th>
                            <th>Department</th>
                            <th>Student details</th>
                            <th>Summary</th>
                            <th>Handler / Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($allComplaints as $c): ?>
                            <tr>
                                <td style="font-family: monospace; font-weight: 700; color: var(--primary);">
                                    <?php echo htmlspecialchars($c['complaint_id']); ?>
                                </td>
                                <td>
                                    <strong><?php echo htmlspecialchars($c['department_name']); ?></strong>
                                </td>
                                <td>
                                    <?php if ($c['is_anonymous']): ?>
                                        <span style="color: var(--text-muted); font-size: 0.85rem; font-style: italic;">Anonymous</span>
                                    <?php else: ?>
                                        <strong style="font-size: 0.9rem;"><?php echo htmlspecialchars($c['student_name']); ?></strong>
                                    <?php endif; ?>
                                </td>
                                <td style="max-width: 250px;">
                                    <div style="font-size: 0.85rem; font-weight: 500;"><?php echo htmlspecialchars($c['summary']); ?></div>
                                    <div style="font-size: 0.7rem; color: var(--text-muted); margin-top: 0.25rem;">Date: <?php echo date('M d, Y', strtotime($c['created_at'])); ?></div>
                                </td>
                                <td>
                                    <div style="display: flex; flex-direction: column; gap: 0.25rem; align-items: flex-start;">
                                        <span style="font-size: 0.75rem; font-weight: 600; text-transform: uppercase; color: var(--text-muted);">
                                            Handler: <?php echo htmlspecialchars($c['current_handler_role']); ?>
                                        </span>
                                        <?php
                                        $sClass = 'badge-status-submitted';
                                        if ($c['status'] === 'In Progress') $sClass = 'badge-status-inprogress';
                                        elseif ($c['status'] === 'Escalated') $sClass = 'badge-status-escalated';
                                        elseif ($c['status'] === 'Resolved') $sClass = 'badge-status-resolved';
                                        ?>
                                        <span class="badge <?php echo $sClass; ?>" style="font-size: 0.65rem; padding: 0.15rem 0.5rem;"><?php echo htmlspecialchars($c['status']); ?></span>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</div>

<!-- Modal 1: Update Status (Principal) -->
<div id="statusModal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h2 style="font-size: 1.25rem; font-weight: 700; color: var(--text-main);">Resolve Complaint (Principal)</h2>
            <button onclick="closeModal('statusModal')" class="modal-close-btn">&times;</button>
        </div>
        <form action="dashboard_principal.php" method="POST">
            <input type="hidden" name="action" value="update_status">
            <input type="hidden" id="status-complaint-id" name="complaint_id" value="">
            
            <div class="form-group">
                <label for="status-select">Status</label>
                <select id="status-select" name="status" onchange="toggleNotesRequired(this.value)">
                    <option value="In Progress">In Progress</option>
                    <option value="Resolved">Resolved</option>
                </select>
            </div>
            
            <div class="form-group">
                <label for="resolution_notes">Executive Resolution Notes *</label>
                <textarea id="resolution_notes" name="resolution_notes" placeholder="Provide final decision or resolution notes. Required if status is Resolved."></textarea>
            </div>
            
            <div class="flex" style="justify-content: flex-end; gap: 0.75rem; margin-top: 1.5rem;">
                <button type="button" onclick="closeModal('statusModal')" class="btn btn-secondary">Cancel</button>
                <button type="submit" class="btn btn-danger">Resolve</button>
            </div>
        </form>
    </div>
</div>

<!-- Modal 2: Admin Override (AI Correction - Principal) -->
<div id="overrideModal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h2 style="font-size: 1.25rem; font-weight: 700; color: var(--text-main);">Admin Override (Re-Route)</h2>
            <button onclick="closeModal('overrideModal')" class="modal-close-btn">&times;</button>
        </div>
        <form action="dashboard_principal.php" method="POST">
            <input type="hidden" name="action" value="admin_override">
            <input type="hidden" id="override-complaint-id" name="complaint_id" value="">
            
            <div class="form-group">
                <label for="override-category">Reroute Category</label>
                <select id="override-category" name="category">
                    <option value="Academic">Academic</option>
                    <option value="Infrastructure">Infrastructure</option>
                    <option value="Hostel">Hostel</option>
                    <option value="Transport">Transport</option>
                    <option value="Harassment">Harassment</option>
                </select>
                <small style="color: var(--text-muted); display: block; margin-top: 0.25rem;">
                    Re-routing from Principal level will reset the handler to Staff level in the selected department.
                </small>
            </div>

            <div class="form-group">
                <label for="override-priority">Correct Priority</label>
                <select id="override-priority" name="priority">
                    <option value="Low">Low</option>
                    <option value="Medium">Medium</option>
                    <option value="High">High</option>
                </select>
            </div>
            
            <div class="flex" style="justify-content: flex-end; gap: 0.75rem; margin-top: 1.5rem;">
                <button type="button" onclick="closeModal('overrideModal')" class="btn btn-secondary">Cancel</button>
                <button type="submit" class="btn btn-danger">Re-Route & Override</button>
            </div>
        </form>
    </div>
</div>

<script>
    function openActionModal(complaint) {
        document.getElementById('status-complaint-id').value = complaint.id;
        document.getElementById('status-select').value = complaint.status === 'Submitted' ? 'In Progress' : complaint.status;
        document.getElementById('resolution_notes').value = complaint.resolution_notes || '';
        toggleNotesRequired(document.getElementById('status-select').value);
        document.getElementById('statusModal').classList.add('active');
    }

    function openOverrideModal(complaint) {
        document.getElementById('override-complaint-id').value = complaint.id;
        document.getElementById('override-category').value = complaint.category;
        document.getElementById('override-priority').value = complaint.priority;
        document.getElementById('overrideModal').classList.add('active');
    }

    function closeModal(modalId) {
        document.getElementById(modalId).classList.remove('active');
    }

    function toggleNotesRequired(status) {
        const textarea = document.getElementById('resolution_notes');
        if (status === 'Resolved') {
            textarea.setAttribute('required', '');
        } else {
            textarea.removeAttribute('required');
        }
    }
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
