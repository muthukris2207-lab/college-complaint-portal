<?php
/**
 * Department Staff Dashboard
 * AI-Powered Smart Complaint & Escalation System
 */

$pageTitle = "Staff Dashboard";
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/db.php';

// Access check: only staff
auth_require_roles(['staff']);
$user = auth_current_user();
$deptId = $user['department_id'];

// Get Department details
$dept = db_fetch("SELECT * FROM departments WHERE id = ?", [$deptId]);
$deptName = $dept ? $dept['name'] : 'Unknown';

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
                WHERE id = ? AND department_id = ? AND current_handler_role = 'staff'
            ", [$status, $notes, $complaintId, $deptId]);
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
            $newDeptId = $newDept ? $newDept['id'] : $deptId;

            db_query("
                UPDATE complaints 
                SET category = ?, priority = ?, department_id = ?, updated_at = NOW() 
                WHERE id = ? AND department_id = ? AND current_handler_role = 'staff'
            ", [$category, $priority, $newDeptId, $complaintId, $deptId]);

            $successMessage = "AI classification overridden successfully. " . 
                ($newDeptId != $deptId ? "Complaint has been rerouted to $category department." : "");
        }
    }
}

// Fetch Stats for current department (only complaints at staff level)
$totalComplaints = db_fetch("
    SELECT COUNT(*) as count FROM complaints 
    WHERE department_id = ? AND current_handler_role = 'staff'
", [$deptId])['count'];

$pendingComplaints = db_fetch("
    SELECT COUNT(*) as count FROM complaints 
    WHERE department_id = ? AND current_handler_role = 'staff' AND status IN ('Submitted', 'In Progress')
", [$deptId])['count'];

$resolvedComplaints = db_fetch("
    SELECT COUNT(*) as count FROM complaints 
    WHERE department_id = ? AND current_handler_role = 'staff' AND status = 'Resolved'
", [$deptId])['count'];

// Fetch Complaint List
$complaints = db_fetch_all("
    SELECT * FROM complaints 
    WHERE department_id = ? AND current_handler_role = 'staff'
    ORDER BY FIELD(status, 'Submitted', 'In Progress', 'Resolved'), created_at DESC
", [$deptId]);

require_once __DIR__ . '/includes/header.php';
?>

<div class="app-container">
    <div style="margin-bottom: 2rem; display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 1rem;">
        <div>
            <span style="font-size: 0.85rem; color: var(--text-muted); text-transform: uppercase; font-weight: 600;">College Department Staff</span>
            <h1 style="font-size: 2rem; font-weight: 800; color: var(--text-main);"><?php echo htmlspecialchars($deptName); ?> Portal</h1>
        </div>
        <div style="background-color: var(--primary-light); color: var(--primary); padding: 0.5rem 1rem; border-radius: var(--radius-sm); font-size: 0.9rem; font-weight: 600;" class="flex">
            <i data-lucide="building-2" style="width: 16px; height: 16px; margin-right: 0.5rem;"></i> Active Department
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
            <div class="stat-icon"><i data-lucide="folder-open"></i></div>
            <div class="stat-details">
                <h3>Total Assigned</h3>
                <p><?php echo $totalComplaints; ?></p>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-icon" style="background-color: var(--warning-light); color: var(--warning);"><i data-lucide="clock"></i></div>
            <div class="stat-details">
                <h3>Pending Action</h3>
                <p><?php echo $pendingComplaints; ?></p>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-icon" style="background-color: var(--success-light); color: var(--success);"><i data-lucide="check-square"></i></div>
            <div class="stat-details">
                <h3>Resolved</h3>
                <p><?php echo $resolvedComplaints; ?></p>
            </div>
        </div>
    </div>

    <!-- Complaint Table / Card View -->
    <div class="card" style="padding: 1.5rem;">
        <h2 class="card-title" style="margin-bottom: 1rem;"><i data-lucide="list-todo" style="vertical-align: middle; margin-right: 0.5rem; color: var(--primary);"></i>Complaints Under Review</h2>
        
        <?php if (empty($complaints)): ?>
            <div class="text-center" style="padding: 3rem 1rem; color: var(--text-muted);">
                <i data-lucide="inbox" style="width: 48px; height: 48px; stroke-width: 1.5; margin-bottom: 1rem;"></i>
                <p>No complaints found for your department at staff level.</p>
            </div>
        <?php else: ?>
            <div class="complaints-table-wrapper">
                <table>
                    <thead>
                        <tr>
                            <th>Complaint ID</th>
                            <th>Student Details</th>
                            <th>Priority / Category</th>
                            <th>Summary</th>
                            <th>Status</th>
                            <th style="text-align: right;">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($complaints as $c): ?>
                            <tr>
                                <td style="font-family: monospace; font-weight: 700; color: var(--primary);">
                                    <?php echo htmlspecialchars($c['complaint_id']); ?>
                                </td>
                                <td>
                                    <?php if ($c['is_anonymous']): ?>
                                        <span style="color: var(--text-muted); font-size: 0.9rem; font-style: italic;">
                                            <i data-lucide="eye-off" style="width: 12px; height: 12px; vertical-align: middle;"></i> Anonymous
                                        </span>
                                    <?php else: ?>
                                        <strong style="font-size: 0.95rem;"><?php echo htmlspecialchars($c['student_name']); ?></strong>
                                        <span style="display: block; font-size: 0.8rem; color: var(--text-muted);"><?php echo htmlspecialchars($c['student_email']); ?></span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <!-- Priority Badge -->
                                    <?php
                                    $pClass = 'badge-priority-low';
                                    if ($c['priority'] === 'High') $pClass = 'badge-priority-high';
                                    elseif ($c['priority'] === 'Medium') $pClass = 'badge-priority-medium';
                                    ?>
                                    <span class="badge <?php echo $pClass; ?>" style="font-size: 0.7rem;"><?php echo htmlspecialchars($c['priority']); ?></span>
                                    
                                    <!-- Category Badge -->
                                    <span class="badge" style="background-color: var(--bg-main); color: var(--text-main); font-size: 0.7rem; margin-top: 0.25rem; display: inline-block;">
                                        <?php echo htmlspecialchars($c['category']); ?>
                                    </span>
                                </td>
                                <td style="max-width: 250px;">
                                    <div style="font-weight: 500; font-size: 0.9rem;"><?php echo htmlspecialchars($c['summary']); ?></div>
                                    <div style="font-size: 0.75rem; color: var(--text-muted); margin-top: 0.25rem;">Submitted: <?php echo date('M d, Y h:i A', strtotime($c['created_at'])); ?></div>
                                </td>
                                <td>
                                    <?php
                                    $sClass = 'badge-status-submitted';
                                    if ($c['status'] === 'In Progress') $sClass = 'badge-status-inprogress';
                                    elseif ($c['status'] === 'Resolved') $sClass = 'badge-status-resolved';
                                    ?>
                                    <span class="badge <?php echo $sClass; ?>"><?php echo htmlspecialchars($c['status']); ?></span>
                                </td>
                                <td style="text-align: right;">
                                    <div style="display: flex; gap: 0.5rem; justify-content: flex-end;">
                                        <button onclick="openActionModal(<?php echo htmlspecialchars(json_encode($c)); ?>)" class="btn btn-secondary flex" style="padding: 0.4rem 0.75rem; font-size: 0.8rem;" title="Resolve / Update Status">
                                            <i data-lucide="edit-3" style="width: 14px; height: 14px;"></i> Update
                                        </button>
                                        <button onclick="openOverrideModal(<?php echo htmlspecialchars(json_encode($c)); ?>)" class="btn btn-secondary flex" style="padding: 0.4rem 0.75rem; font-size: 0.8rem;" title="AI Override">
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
</div>

<!-- Modal 1: Update Status / Resolution notes -->
<div id="statusModal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h2 id="modal-title" style="font-size: 1.25rem; font-weight: 700; color: var(--text-main);">Update Status</h2>
            <button onclick="closeModal('statusModal')" class="modal-close-btn">&times;</button>
        </div>
        <form action="dashboard_staff.php" method="POST">
            <input type="hidden" name="action" value="update_status">
            <input type="hidden" id="status-complaint-id" name="complaint_id" value="">
            
            <div class="form-group">
                <label for="status-select">Status</label>
                <select id="status-select" name="status" onchange="toggleNotesRequired(this.value)">
                    <option value="In Progress">In Progress</option>
                    <option value="Resolved">Resolved</option>
                </select>
            </div>
            
            <div class="form-group" id="notes-group">
                <label for="resolution_notes">Resolution / Action Notes *</label>
                <textarea id="resolution_notes" name="resolution_notes" placeholder="Detail the actions taken or final resolution. Required if status is Resolved."></textarea>
            </div>
            
            <div class="flex" style="justify-content: flex-end; gap: 0.75rem; margin-top: 1.5rem;">
                <button type="button" onclick="closeModal('statusModal')" class="btn btn-secondary">Cancel</button>
                <button type="submit" class="btn btn-primary">Save Changes</button>
            </div>
        </form>
    </div>
</div>

<!-- Modal 2: Admin Override (AI Correction) -->
<div id="overrideModal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h2 style="font-size: 1.25rem; font-weight: 700; color: var(--text-main);">Admin Override (AI Correction)</h2>
            <button onclick="closeModal('overrideModal')" class="modal-close-btn">&times;</button>
        </div>
        <form action="dashboard_staff.php" method="POST">
            <input type="hidden" name="action" value="admin_override">
            <input type="hidden" id="override-complaint-id" name="complaint_id" value="">
            
            <div class="form-group">
                <label for="override-category">Correct Category</label>
                <select id="override-category" name="category">
                    <option value="Academic">Academic</option>
                    <option value="Infrastructure">Infrastructure</option>
                    <option value="Hostel">Hostel</option>
                    <option value="Transport">Transport</option>
                    <option value="Harassment">Harassment</option>
                </select>
                <small style="color: var(--text-muted); display: block; margin-top: 0.25rem;">
                    Changing category will automatically reroute the complaint to the corresponding department.
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
                <button type="submit" class="btn btn-primary">Apply Override</button>
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
