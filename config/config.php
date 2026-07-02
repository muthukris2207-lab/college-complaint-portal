<?php
/**
 * Configuration File
 * AI-Powered Smart Complaint & Escalation System
 */

// Database Configuration - Reads from Environment Variables for Cloud Deployment,
// falls back to local values for development.
define('DB_HOST', getenv('DB_HOST') ?: '127.0.0.1');
define('DB_PORT', getenv('DB_PORT') ?: '3306');
define('DB_USER', getenv('DB_USER') ?: 'root');
define('DB_PASS', getenv('DB_PASS') !== false ? getenv('DB_PASS') : '');
define('DB_NAME', getenv('DB_NAME') ?: 'smart_complaint_db');

// Anthropic Claude API Configuration
define('CLAUDE_API_KEY', getenv('CLAUDE_API_KEY') ?: 'YOUR_CLAUDE_API_KEY_HERE');

// Application Settings
define('APP_URL', getenv('APP_URL') ?: 'http://localhost:8000');
define('COMPLAINT_PREFIX', 'CMP-' . date('Y') . '-');
