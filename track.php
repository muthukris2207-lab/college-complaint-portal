<?php
/**
 * Complaint Tracking Portal
 * AI-Powered Smart Complaint & Escalation System
 */

$pageTitle = "Track Complaint";
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/header.php';

$searchId = trim($_GET['id'] ?? '');
$complaint = null;
$notFound = false;
$escalations = [];

if (!empty($searchId)) {
    // Fetch complaint details
    $complaint = db_fetch("
        SELECT c.*, d.name as department_name 
        FROM complaints c 
        JOIN departments d ON c.department_id = d.id 
        WHERE c.complaint_id = ?
    ", [$searchId]);

    if (!$complaint) {
        $notFound = true;
    } else {
        // Fetch escalation logs if any
        $escalations = db_fetch_all("
            SELECT * FROM escalation_log 
            WHERE complaint_id = ? 
            ORDER BY escalated_at ASC
        ", [$searchId]);
    }
}
?>

<div class="app-container">
    <div class="hero">
        <h1>Track Your Complaint</h1>
        <p>Enter your unique Complaint ID below to check the real-time processing status and resolution notes.</p>
    </div>

    <div style="max-width: 700px; margin: 0 auto;">
        
        <!-- Search Form -->
        <div class="card" style="padding: 1.5rem;">
            <form action="track.php" method="GET" class="flex" style="gap: 1rem; flex-wrap: wrap;">
                <div style="flex-grow: 1; min-width: 250px;">
                    <input type="text" id="id" name="id" placeholder="Enter Complaint ID (e.g. CMP-2026-00001)" value="<?php echo htmlspecialchars($searchId); ?>" required style="text-transform: uppercase;">
                </div>
                <button type="submit" class="btn btn-primary flex" style="min-width: 140px;">
                    <i data-lucide="search"></i> Track Status
                </button>
            </form>
        </div>

        <?php if ($notFound): ?>
            <div class="alert alert-danger flex" style="animation: fadeIn 0.4s ease;">
                <i data-lucide="x-circle" style="margin-right: 0.5rem;"></i>
                Complaint ID <strong><?php echo htmlspecialchars($searchId); ?></strong> was not found in our database. Please check and try again.
            </div>
        <?php elseif ($complaint): ?>
            
            <!-- Complaint Details Card -->
            <div class="card" style="animation: fadeIn 0.4s ease;">
                <div class="flex-between" style="border-bottom: 1px solid var(--border); padding-bottom: 1rem; margin-bottom: 1.5rem; flex-wrap: wrap; gap: 1rem;">
                    <div>
                        <span style="font-size: 0.85rem; color: var(--text-muted); text-transform: uppercase; font-weight: 600;">Tracking ID</span>
                        <h2 style="font-family: monospace; color: var(--primary); font-size: 1.5rem; margin-top: 0.25rem;"><?php echo htmlspecialchars($complaint['complaint_id']); ?></h2>
                    </div>
                    <div class="flex" style="gap: 0.5rem;">
                        <!-- Priority Badge -->
                        <?php
                        $priorityClass = 'badge-priority-low';
                        if ($complaint['priority'] === 'High') {
                            $priorityClass = 'badge-priority-high';
                        } elseif ($complaint['priority'] === 'Medium') {
                            $priorityClass = 'badge-priority-medium';
                        }
                        ?>
                        <span class="badge <?php echo $priorityClass; ?>"><?php echo htmlspecialchars($complaint['priority']); ?> Priority</span>
                        
                        <!-- Status Badge -->
                        <?php
                        $statusClass = 'badge-status-submitted';
                        if ($complaint['status'] === 'In Progress') {
                            $statusClass = 'badge-status-inprogress';
                        } elseif ($complaint['status'] === 'Escalated') {
                            $statusClass = 'badge-status-escalated';
                        } elseif ($complaint['status'] === 'Resolved') {
                            $statusClass = 'badge-status-resolved';
                        }
                        ?>
                        <span class="badge <?php echo $statusClass; ?>"><?php echo htmlspecialchars($complaint['status']); ?></span>
                    </div>
                </div>

                <div style="margin-bottom: 1.5rem;">
                    <h3 style="font-size: 0.875rem; color: var(--text-muted); text-transform: uppercase; margin-bottom: 0.5rem;">Complaint Summary</h3>
                    <p style="font-weight: 500; font-size: 1.05rem;"><?php echo htmlspecialchars($complaint['summary']); ?></p>
                </div>

                <div style="margin-bottom: 1.5rem; background-color: var(--bg-main); border: 1px solid var(--border); padding: 1rem; border-radius: var(--radius-sm);">
                    <h3 style="font-size: 0.85rem; color: var(--text-muted); text-transform: uppercase; margin-bottom: 0.25rem;">Full Text Content</h3>
                    <p style="white-space: pre-wrap; font-size: 0.95rem;"><?php echo htmlspecialchars($complaint['text_content']); ?></p>
                </div>

                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem; margin-bottom: 1.5rem;">
                    <div>
                        <span style="font-size: 0.8rem; color: var(--text-muted); text-transform: uppercase; display: block;">Assigned Department</span>
                        <strong><?php echo htmlspecialchars($complaint['department_name']); ?></strong>
                    </div>
                    <div>
                        <span style="font-size: 0.8rem; color: var(--text-muted); text-transform: uppercase; display: block;">Date Submitted</span>
                        <strong><?php echo date('M d, Y h:i A', strtotime($complaint['created_at'])); ?></strong>
                    </div>
                </div>

                <?php if ($complaint['is_anonymous']): ?>
                    <div class="flex" style="gap: 0.5rem; font-size: 0.85rem; color: var(--text-muted);">
                        <i data-lucide="eye-off" style="width: 16px; height: 16px;"></i> Filed anonymously. Student details hidden.
                    </div>
                <?php endif; ?>
            </div>

            <!-- Lifecycle Timeline -->
            <div class="card">
                <h2 class="card-title"><i data-lucide="git-commit" style="vertical-align: middle; margin-right: 0.5rem; color: var(--primary);"></i>Resolution Timeline</h2>
                
                <div class="timeline">
                    <!-- Step 1: Submission -->
                    <div class="timeline-item active">
                        <div class="timeline-dot"></div>
                        <div class="timeline-content">
                            <div class="timeline-title">Complaint Submitted Successfully</div>
                            <div class="timeline-date"><?php echo date('M d, Y h:i A', strtotime($complaint['created_at'])); ?></div>
                            <p style="font-size: 0.9rem; color: var(--text-muted);">Complaint registered and stored with AI evaluation of classification.</p>
                        </div>
                    </div>

                    <!-- Step 2: Routing -->
                    <div class="timeline-item active">
                        <div class="timeline-dot"></div>
                        <div class="timeline-content">
                            <div class="timeline-title">Assigned to <?php echo htmlspecialchars($complaint['department_name']); ?> Department</div>
                            <div class="timeline-date"><?php echo date('M d, Y h:i A', strtotime($complaint['created_at'])); ?></div>
                            <p style="font-size: 0.9rem; color: var(--text-muted);">
                                AI routed this complaint automatically based on description classification.
                            </p>
                        </div>
                    </div>

                    <!-- Step 3: Investigation / Escalations -->
                    <?php if ($complaint['status'] === 'In Progress' || $complaint['status'] === 'Escalated' || $complaint['status'] === 'Resolved' || count($escalations) > 0): ?>
                        
                        <?php 
                        // Show active investigation state
                        $investigationActive = ($complaint['status'] === 'In Progress' || $complaint['status'] === 'Escalated' || $complaint['status'] === 'Resolved');
                        ?>
                        <div class="timeline-item <?php echo $investigationActive ? 'active' : ''; ?>">
                            <div class="timeline-dot"></div>
                            <div class="timeline-content">
                                <div class="timeline-title">Investigation Initiated</div>
                                <p style="font-size: 0.9rem; color: var(--text-muted);">Department staff has acknowledged the issue and is reviewing options.</p>
                            </div>
                        </div>

                        <!-- Show Escalations if logged -->
                        <?php foreach ($escalations as $esc): ?>
                            <div class="timeline-item active">
                                <div class="timeline-dot" style="background-color: #a21caf;"></div>
                                <div class="timeline-content" style="border-color: rgba(162, 28, 175, 0.2);">
                                    <div class="timeline-title" style="color: #a21caf; font-weight: 600;">
                                        Escalated to HOD / Management
                                    </div>
                                    <div class="timeline-date"><?php echo date('M d, Y h:i A', strtotime($esc['escalated_at'])); ?></div>
                                    <p style="font-size: 0.9rem; color: var(--text-muted);">
                                        Complaint escalated from <strong><?php echo htmlspecialchars(strtoupper($esc['from_role'])); ?></strong> to <strong><?php echo htmlspecialchars(strtoupper($esc['to_role'])); ?></strong> due to SLA resolution delay.
                                    </p>
                                </div>
                            </div>
                        <?php endforeach; ?>

                    <?php endif; ?>

                    <!-- Step 4: Resolution -->
                    <?php if ($complaint['status'] === 'Resolved'): ?>
                        <div class="timeline-item resolved">
                            <div class="timeline-dot"></div>
                            <div class="timeline-content" style="border-color: rgba(16, 185, 129, 0.2);">
                                <div class="timeline-title" style="color: var(--success); font-weight: 700;">Complaint Resolved</div>
                                <div class="timeline-date"><?php echo date('M d, Y h:i A', strtotime($complaint['updated_at'])); ?></div>
                                <div style="margin-top: 0.5rem; background-color: var(--success-light); padding: 0.75rem; border-radius: var(--radius-sm); border: 1px solid rgba(16, 185, 129, 0.1);">
                                    <strong style="font-size: 0.85rem; color: #065f46; display: block; margin-bottom: 0.25rem;">Resolution Notes:</strong>
                                    <p style="font-size: 0.95rem; color: #065f46; white-space: pre-wrap;"><?php echo htmlspecialchars($complaint['resolution_notes'] ?: 'No notes provided by staff.'); ?></p>
                                </div>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

        <?php endif; ?>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
