<?php
/**
 * Database Helper
 * AI-Powered Smart Complaint & Escalation System
 */

require_once __DIR__ . '/../config/config.php';

try {
    $dsn = "mysql:host=" . DB_HOST . ";port=" . DB_PORT . ";dbname=" . DB_NAME . ";charset=utf8mb4";
    $options = [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES   => false,
    ];
    $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
} catch (PDOException $e) {
    // If the database connection fails, log it and terminate with a clean message
    error_log("Database Connection Failure: " . $e->getMessage());
    die("System Configuration Error: Unable to connect to the database. Please ensure MariaDB/MySQL is running.");
}

/**
 * Helper to execute a query with parameters
 * 
 * @param string $sql
 * @param array $params
 * @return PDOStatement
 */
function db_query($sql, $params = []) {
    global $pdo;
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    return $stmt;
}

/**
 * Helper to fetch a single row
 * 
 * @param string $sql
 * @param array $params
 * @return array|false
 */
function db_fetch($sql, $params = []) {
    return db_query($sql, $params)->fetch();
}

/**
 * Helper to fetch all rows
 * 
 * @param string $sql
 * @param array $params
 * @return array
 */
function db_fetch_all($sql, $params = []) {
    return db_query($sql, $params)->fetchAll();
}

/**
 * Helper to get last inserted ID
 * 
 * @return string
 */
function db_last_insert_id() {
    global $pdo;
    return $pdo->lastInsertId();
}
