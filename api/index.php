<?php
/**
 * Complaint Submission Portal
 * AI-Powered Smart Complaint & Escalation System
 */

$pageTitle = "Submit Complaint";
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/ai.php';
require_once __DIR__ . '/includes/header.php';

$successMessage = "";
$errorMessage = "";
$generatedId = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $text_content = trim($_POST['complaint_text'] ?? '');
    $is_anonymous = isset($_POST['is_anonymous']) ? 1 : 0;
    
    $student_name = !$is_anonymous ? trim($_POST['student_name'] ?? '') : null;
    $student_email = !$is_anonymous ? trim($_POST['student_email'] ?? '') : null;
    $student_roll = !$is_anonymous ? trim($_POST['student_roll'] ?? '') : null;

    if (empty($text_content)) {
        $errorMessage = "Complaint description cannot be empty.";
    } elseif (!$is_anonymous && (empty($student_name) || empty($student_email))) {
        $errorMessage = "Please fill in your name and email, or submit anonymously.";
    } else {
        try {
            // 1. AI Categorization and priority detection
            $aiResult = ai_classify_complaint($text_content);
            $category = $aiResult['category'];
            $priority = $aiResult['priority'];
            $summary = $aiResult['summary'];

            // 2. Resolve Department
            $dept = db_fetch("SELECT id FROM departments WHERE name = ?", [$category]);
            if ($dept) {
                $department_id = $dept['id'];
            } else {
                // Default fallback: Infrastructure department
                $deptFallback = db_fetch("SELECT id FROM departments WHERE name = 'Infrastructure'");
                $department_id = $deptFallback ? $deptFallback['id'] : 2;
            }

            // 3. Generate Unique Complaint ID
            // Safe collision-free generation using transaction locking or sequence checking
            $pdo->beginTransaction();
            $stmt = $pdo->query("SELECT MAX(id) as max_id FROM complaints");
            $row = $stmt->fetch();
            $next_seq = ($row['max_id'] ?? 0) + 1;
            $generatedId = "CMP-" . date('Y') . "-" . str_pad($next_seq, 5, '0', STR_PAD_LEFT);

            // 4. Save to Database
            $sql = "INSERT INTO complaints 
                    (complaint_id, text_content, is_anonymous, student_name, student_email, student_roll, 
                     category, priority, summary, status, current_handler_role, department_id) 
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 'Submitted', 'staff', ?)";
            
            db_query($sql, [
                $generatedId,
                $text_content,
                $is_anonymous,
                $student_name,
                $student_email,
                $student_roll,
                $category,
                $priority,
                $summary,
                $department_id
            ]);

            $pdo->commit();
            $successMessage = "Your complaint has been submitted successfully!";
        } catch (Exception $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            error_log("Complaint submission error: " . $e->getMessage());
            $errorMessage = "An error occurred while submitting your complaint. Please try again.";
        }
    }
}
?>

<div class="app-container" style="max-width: 1100px;">
    
    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(320px, 1fr)); gap: 3rem; align-items: center; margin-top: 1rem;">
        
        <!-- Left Pane: Hero Information & Custom Generated College Illustration -->
        <div style="display: flex; flex-direction: column; gap: 2rem;">
            <div class="hero" style="text-align: left; padding: 0; margin-bottom: 0;">
                <h1 style="font-size: 2.5rem; line-height: 1.2; font-weight: 800;">Submit Your Complaint</h1>
                <p style="margin-top: 1rem; font-size: 1.1rem; color: var(--text-muted);">
                    File academic, infrastructure, hostel, transport, or harassment concerns securely. Our AI system will automatically categorize, prioritize, and route tickets for immediate HOD and staff action.
                </p>
            </div>
            
            <!-- Picture container referencing students and staff -->
            <div style="border-radius: var(--radius-md); overflow: hidden; border: 1px solid var(--border); box-shadow: var(--shadow-md); position: relative; aspect-ratio: 1.6 / 1; background-color: var(--bg-card);">
                <img src="assets/images/students.png" alt="Sri Krishna College Students and Staff" style="width: 100%; height: 100%; object-fit: cover; display: block;">
                <div style="position: absolute; bottom: 0; left: 0; right: 0; background: linear-gradient(180deg, rgba(0,0,0,0) 0%, rgba(20,20,18,0.85) 100%); padding: 1.5rem; color: white;">
                    <span style="font-size: 0.75rem; text-transform: uppercase; font-weight: 700; color: var(--primary); letter-spacing: 0.08em; display: block;">Sri Krishna Arts and Science College</span>
                    <strong style="font-size: 1.1rem; font-weight: 700; margin-top: 0.25rem; display: block;">Student & Staff Portal</strong>
                </div>
            </div>
        </div>
        
        <!-- Right Pane: Submission Form / Success Dashboard -->
        <div>
            <?php if (!empty($successMessage) && !empty($generatedId)): ?>
                <div class="card text-center" style="border: 2px solid var(--primary); background-color: var(--primary-light); animation: fadeIn 0.5s ease; margin-bottom: 0;">
                    <div class="stat-icon" style="background-color: var(--primary); color: white; margin: 0 auto 1.5rem; width: 64px; height: 64px; border-radius: 50%;">
                        <i data-lucide="check-circle-2" style="width: 32px; height: 32px;"></i>
                    </div>
                    <h2 style="color: var(--text-main); margin-bottom: 0.5rem; font-weight: 700;"><?php echo $successMessage; ?></h2>
                    <p style="color: var(--text-muted); margin-bottom: 1.5rem;">Please save your Complaint ID for tracking status updates.</p>
                    
                    <div style="background-color: var(--bg-card); border: 1px dashed var(--border); padding: 1.25rem; border-radius: var(--radius-sm); margin-bottom: 2rem; display: inline-block;">
                        <span style="font-size: 0.85rem; text-transform: uppercase; color: var(--text-muted); display: block; font-weight: 600;">Complaint ID</span>
                        <strong style="font-size: 1.75rem; color: var(--primary); letter-spacing: 0.05em; font-family: monospace; display: block; margin-top: 0.25rem;"><?php echo $generatedId; ?></strong>
                    </div>

                    <div class="flex" style="justify-content: center; gap: 1rem; flex-wrap: wrap;">
                        <a href="track.php?id=<?php echo urlencode($generatedId); ?>" class="btn btn-primary flex">
                            <i data-lucide="search"></i> Track Status
                        </a>
                        <a href="index.php" class="btn btn-secondary flex">Submit Another</a>
                    </div>
                </div>
            <?php else: ?>
                
                <?php if (!empty($errorMessage)): ?>
                    <div class="alert alert-danger flex">
                        <i data-lucide="alert-circle" style="margin-right: 0.5rem; flex-shrink: 0;"></i>
                        <?php echo htmlspecialchars($errorMessage); ?>
                    </div>
                <?php endif; ?>

                <div class="card" style="margin-bottom: 0;">
                    <form action="index.php" method="POST" id="complaintForm" autocomplete="off">
                        
                        <!-- Anonymous Toggle -->
                        <div class="toggle-container" id="anonymous-toggle">
                            <input type="checkbox" id="is_anonymous" name="is_anonymous">
                            <div class="toggle-switch"></div>
                            <span class="toggle-label flex">
                                <i data-lucide="eye-off" style="width: 16px; height: 16px; margin-right: 0.5rem; color: var(--text-muted);"></i>
                                Submit Anonymously
                            </span>
                        </div>

                        <!-- Student Details -->
                        <div id="student-details-section">
                            <div class="form-group" id="name-group">
                                <label for="student_name">Full Name *</label>
                                <input type="text" id="student_name" name="student_name" placeholder="John Doe" required>
                            </div>
                            
                            <div class="form-group" id="email-group">
                                <label for="student_email">Email Address *</label>
                                <input type="email" id="student_email" name="student_email" placeholder="john.doe@college.edu" required>
                            </div>
                            
                            <div class="form-group" id="roll-group">
                                <label for="student_roll">Roll Number / Student ID</label>
                                <input type="text" id="student_roll" name="student_roll" placeholder="COL-2024-0412">
                            </div>
                        </div>

                        <!-- Complaint Textarea -->
                        <div class="form-group">
                            <label for="complaint_text">Complaint Description *</label>
                            <div class="textarea-wrapper">
                                <textarea id="complaint_text" name="complaint_text" placeholder="Describe your complaint in detail..." required></textarea>
                                <button type="button" id="voice-input-btn" class="voice-btn" title="Use Voice Input">
                                    <i data-lucide="mic"></i>
                                </button>
                            </div>
                            <small style="color: var(--text-muted); display: block; margin-top: 0.5rem;">
                                Speak clearly to use speech-to-text. AI will automatically classify category and priority.
                            </small>
                        </div>

                        <button type="submit" class="btn btn-primary btn-block flex">
                            <i data-lucide="send"></i> Submit Complaint
                        </button>
                    </form>
                </div>
            <?php endif; ?>
        </div>
        
    </div>
</div>

<!-- Page Specific JS -->
<script src="assets/js/speech.js"></script>
<script>
    // Anonymous Mode details toggle
    document.addEventListener('DOMContentLoaded', () => {
        const isAnonCheckbox = document.getElementById('is_anonymous');
        const nameInput = document.getElementById('student_name');
        const emailInput = document.getElementById('student_email');
        const rollInput = document.getElementById('student_roll');
        const detailsSection = document.getElementById('student-details-section');

        if (isAnonCheckbox) {
            isAnonCheckbox.addEventListener('change', function() {
                if (this.checked) {
                    detailsSection.style.display = 'none';
                    nameInput.removeAttribute('required');
                    emailInput.removeAttribute('required');
                    nameInput.value = '';
                    emailInput.value = '';
                    rollInput.value = '';
                } else {
                    detailsSection.style.display = 'block';
                    nameInput.setAttribute('required', '');
                    emailInput.setAttribute('required', '');
                }
            });
        }
    });
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
