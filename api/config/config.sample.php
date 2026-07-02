<?php
/**
 * Sample Configuration File
 * Copy this file to config.php and fill in your details.
 */

// Database Configuration
define('DB_HOST', '127.0.0.1');
define('DB_PORT', '3306');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'smart_complaint_db');

// Anthropic Claude API Configuration
// Read from environment variable CLAUDE_API_KEY, or insert key below.
define('CLAUDE_API_KEY', getenv('CLAUDE_API_KEY') ?: 'YOUR_CLAUDE_API_KEY_HERE');

// Application Settings
define('APP_URL', 'http://localhost:8000');
define('COMPLAINT_PREFIX', 'CMP-' . date('Y') . '-');
