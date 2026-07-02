<?php
/**
 * Auto-Escalation Engine Script
 * AI-Powered Smart Complaint & Escalation System
 * Designed to run via CLI cron job or HTTP request.
 */

require_once __DIR__ . '/../includes/db.php';

// Check if running in test mode (accelerates escalation interval to 24 seconds instead of 24 hours)
$isTest = isset($_GET['test']) || (php_sapi_name() === 'cli' && in_array('--test', $argv ?? []));
$intervalSeconds = $isTest ? 24 : 86400; // 24 seconds vs 24 hours (86400s)

if (php_sapi_name() !== 'cli') {
    echo "<!DOCTYPE html><html><head><title>Escalation Engine</title><style>body { font-family: monospace; background: #0f172a; color: #38bdf8; padding: 2rem; } h2 { color: #f43f5e; }</style></head><body>";
    echo "<h1>Escalation Engine Active</h1>";
    echo $isTest ? "<p style='color: #a855f7;'><strong>TEST MODE: Enabled (Escalation SLA set to 24 seconds)</strong></p>" : "<p>SLA Interval: 24 Hours</p>";
} else {
    echo "=== Escalation Engine Running ===" . PHP_EOL;
    echo $isTest ? "TEST MODE: SLA Interval is 24 seconds." . PHP_EOL : "SLA Interval: 24 Hours." . PHP_EOL;
}

try {
    $pdo->beginTransaction();
    $escalationCount = 0;

    // 1. Escalate from Staff to HOD (handler = 'staff', unresolved for > intervalSeconds)
    // Query complaints created more than $intervalSeconds ago (calculated relative to DB server NOW())
    $staffComplaints = db_fetch_all("
        SELECT id, complaint_id, created_at 
        FROM complaints 
        WHERE current_handler_role = 'staff' 
          AND status IN ('Submitted', 'In Progress') 
          AND created_at <= DATE_SUB(NOW(), INTERVAL ? SECOND)
    ", [$intervalSeconds]);

    foreach ($staffComplaints as $sc) {
        $complaint_id = $sc['complaint_id'];
        
        // Update complaint
        db_query("
            UPDATE complaints 
            SET current_handler_role = 'hod', status = 'Escalated', escalated_at = NOW(), updated_at = NOW() 
            WHERE id = ?
        ", [$sc['id']]);

        // Log escalation
        db_query("
            INSERT INTO escalation_log (complaint_id, from_role, to_role, escalated_at) 
            VALUES (?, 'staff', 'hod', NOW())
        ", [$complaint_id]);

        $msg = "Escalated complaint $complaint_id from STAFF to HOD.";
        echo (php_sapi_name() === 'cli') ? "[+] $msg" . PHP_EOL : "<p style='color: #fb923c;'>[+] $msg</p>";
        $escalationCount++;
    }

    // 2. Escalate from HOD to Principal (handler = 'hod', escalated_at OR created_at unresolved for > another intervalSeconds)
    // HOD to Principal: unresolved for another 24 hours (calculated relative to DB server NOW())
    $hodComplaints = db_fetch_all("
        SELECT id, complaint_id, escalated_at 
        FROM complaints 
        WHERE current_handler_role = 'hod' 
          AND status = 'Escalated' 
          AND escalated_at <= DATE_SUB(NOW(), INTERVAL ? SECOND)
    ", [$intervalSeconds]);

    foreach ($hodComplaints as $hc) {
        $complaint_id = $hc['complaint_id'];

        // Update complaint
        db_query("
            UPDATE complaints 
            SET current_handler_role = 'principal', status = 'Escalated', escalated_at = NOW(), updated_at = NOW() 
            WHERE id = ?
        ", [$hc['id']]);

        // Log escalation
        db_query("
            INSERT INTO escalation_log (complaint_id, from_role, to_role, escalated_at) 
            VALUES (?, 'hod', 'principal', NOW())
        ", [$complaint_id]);

        $msg = "Escalated complaint $complaint_id from HOD to PRINCIPAL.";
        echo (php_sapi_name() === 'cli') ? "[+] $msg" . PHP_EOL : "<p style='color: #ef4444;'>[+] $msg</p>";
        $escalationCount++;
    }

    $pdo->commit();
    
    $summary = "Process complete. Total escalations processed: $escalationCount.";
    echo (php_sapi_name() === 'cli') ? "$summary" . PHP_EOL : "<h3>$summary</h3></body></html>";

} catch (Exception $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    $err = "Escalation failed: " . $e->getMessage();
    echo (php_sapi_name() === 'cli') ? "[!] $err" . PHP_EOL : "<h2>[!] $err</h2></body></html>";
}
