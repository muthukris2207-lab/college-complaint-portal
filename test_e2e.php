<?php
/**
 * Programmatic End-to-End Test Script (Rigorous Database Validation)
 * AI-Powered Smart Complaint & Escalation System
 */

require_once __DIR__ . '/includes/db.php';

$baseUrl = "http://localhost:8000";
echo "=== Beginning Smart Complaint System Rigorous E2E Test ===\n";

// Helper function to send HTTP requests
function make_request($url, $postFields = null, $cookieFile = null) {
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    
    if ($postFields !== null) {
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($postFields));
    }
    
    if ($cookieFile !== null) {
        curl_setopt($ch, CURLOPT_COOKIEJAR, $cookieFile);
        curl_setopt($ch, CURLOPT_COOKIEFILE, $cookieFile);
    }
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    
    if (curl_errno($ch)) {
        echo "[!] cURL Error: " . curl_error($ch) . "\n";
        exit(1);
    }
    
    curl_close($ch);
    return ['code' => $httpCode, 'body' => $response];
}

// 1. Submit a complaint as a student
echo "[Step 1] Submitting a complaint from student portal...\n";
$submitData = [
    'complaint_text' => 'The professor for Advanced Mathematics is not uploading lecture slides, and the course materials are missing. We have our exams in a week.',
    'student_name' => 'Alice Cooper',
    'student_email' => 'alice@college.edu',
    'student_roll' => 'AC-2024-009'
];

$res = make_request("$baseUrl/index.php", $submitData);
if ($res['code'] !== 200) {
    echo "[!] Failed to load index.php. Server might not be running.\n";
    exit(1);
}

// Extract Complaint ID
$complaintId = "";
if (preg_match('/(CMP-\d{4}-\d{5})/', $res['body'], $matches)) {
    $complaintId = $matches[1];
    echo "[+] Complaint submitted successfully! Generated ID: $complaintId\n";
} else {
    echo "[!] FAILED: Complaint ID not found in HTML response.\n";
    exit(1);
}

// Verify Initial Database State
$complaint = db_fetch("SELECT * FROM complaints WHERE complaint_id = ?", [$complaintId]);
if ($complaint && $complaint['current_handler_role'] === 'staff' && $complaint['status'] === 'Submitted') {
    echo "[+] Initial Database State verified: Handler = Staff, Status = Submitted.\n";
} else {
    echo "[!] FAILED: Initial database state is incorrect.\n";
    exit(1);
}

// 2. Track complaint status
echo "[Step 2] Tracking complaint status of $complaintId...\n";
$res = make_request("$baseUrl/track.php?id=$complaintId");
if (strpos($res['body'], 'Academic') !== false && strpos($res['body'], 'Submitted') !== false) {
    echo "[+] Student track page matches expected initial state.\n";
} else {
    echo "[!] FAILED: Tracking info does not match expected initial state.\n";
    exit(1);
}

// 3. Simulate Staff SLA delay (> 24 seconds in test mode)
echo "[Step 3] Simulating SLA delay at Staff level (updating created_at to 30 seconds ago)...\n";
db_query("UPDATE complaints SET created_at = DATE_SUB(NOW(), INTERVAL 30 SECOND) WHERE complaint_id = ?", [$complaintId]);

// Trigger Escalation Run 1
echo "[+] Triggering Auto-Escalation Engine (Run 1)...\n";
$res1 = make_request("$baseUrl/cron/escalate.php?test=1");
echo "    Engine Response: " . trim(strip_tags($res1['body'])) . "\n";

// Verify HOD Escalation in Database
$complaint = db_fetch("SELECT * FROM complaints WHERE complaint_id = ?", [$complaintId]);
if ($complaint && $complaint['current_handler_role'] === 'hod' && $complaint['status'] === 'Escalated') {
    echo "[+] Database Escalation verified: Handler updated to HOD, Status updated to Escalated.\n";
} else {
    echo "[!] FAILED: Escalation to HOD did not occur in database.\n";
    exit(1);
}

// Verify on HOD Dashboard
echo "[+] Verifying HOD dashboard access and content...\n";
$hodCookie = tempnam(sys_get_temp_dir(), 'cookie_hod');
$hodLoginData = ['username' => 'hod_academic', 'password' => 'hodacad123'];
make_request("$baseUrl/login.php", $hodLoginData, $hodCookie);
$resHOD = make_request("$baseUrl/dashboard_hod.php", null, $hodCookie);

// Rigorous check: verify the complaint ID appears inside the "Action Required: Escalated to HOD" block
if (strpos($resHOD['body'], $complaintId) !== false && strpos($resHOD['body'], 'Action Required: Escalated to HOD') !== false) {
    echo "[+] Complaint is visible in HOD dashboard under Action Required.\n";
} else {
    echo "[!] FAILED: Complaint not found under HOD Dashboard escalated queue.\n";
    exit(1);
}

// 4. Simulate HOD SLA delay (> 24 seconds in test mode)
echo "[Step 4] Simulating SLA delay at HOD level (updating escalated_at to 30 seconds ago)...\n";
db_query("UPDATE complaints SET escalated_at = DATE_SUB(NOW(), INTERVAL 30 SECOND) WHERE complaint_id = ?", [$complaintId]);

// Trigger Escalation Run 2
echo "[+] Triggering Auto-Escalation Engine (Run 2)...\n";
$res2 = make_request("$baseUrl/cron/escalate.php?test=1");
echo "    Engine Response: " . trim(strip_tags($res2['body'])) . "\n";

// Verify Principal Escalation in Database
$complaint = db_fetch("SELECT * FROM complaints WHERE complaint_id = ?", [$complaintId]);
if ($complaint && $complaint['current_handler_role'] === 'principal' && $complaint['status'] === 'Escalated') {
    echo "[+] Database Escalation verified: Handler updated to Principal, Status is Escalated.\n";
} else {
    echo "[!] FAILED: Escalation to Principal did not occur in database.\n";
    exit(1);
}

// Verify on Principal Dashboard
echo "[+] Verifying Principal dashboard access and content...\n";
$principalCookie = tempnam(sys_get_temp_dir(), 'cookie_principal');
$principalLoginData = ['username' => 'principal', 'password' => 'principal123'];
make_request("$baseUrl/login.php", $principalLoginData, $principalCookie);
$resPrincipal = make_request("$baseUrl/dashboard_principal.php", null, $principalCookie);

if (strpos($resPrincipal['body'], $complaintId) !== false && strpos($resPrincipal['body'], 'Action Required: Critical Escalations to Principal') !== false) {
    echo "[+] Complaint is visible in Principal dashboard under Critical Escalations!\n";
} else {
    echo "[!] FAILED: Complaint not found under Principal Dashboard escalated queue.\n";
    exit(1);
}

// Cleanup
@unlink($hodCookie);
@unlink($principalCookie);

echo "\n*** SUCCESS: All Rigorous End-to-End test steps passed! ***\n";
exit(0);
