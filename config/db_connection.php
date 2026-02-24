<?php

// Database Configuration
$host = 'localhost';
$dbname = 'ordivo';
$username = 'root';
$password = '';
$charset = 'utf8mb4';

// PDO Options for security and performance
$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => false,
    PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci"
];

try {
    // Create PDO connection
    $dsn = "mysql:host=$host;dbname=$dbname;charset=$charset";
    $pdo = new PDO($dsn, $username, $password, $options);
    
    // Set timezone
    $pdo->exec("SET time_zone = '+06:00'"); // Bangladesh timezone
    
} catch (PDOException $e) {
    // Log error and show user-friendly message
    error_log("Database Connection Error: " . $e->getMessage());
    
    // In production, show generic error message
    if (defined('ENVIRONMENT') && ENVIRONMENT === 'production') {
        die('Database connection failed. Please try again later.');
    } else {
        // In development, show detailed error
        die('Database Connection Error: ' . $e->getMessage());
    }
}

/**
 * Database Helper Functions
 */

/**
 * Execute a prepared statement with parameters
 * @param string $sql SQL query with placeholders
 * @param array $params Parameters to bind
 * @return PDOStatement
 */
function executeQuery($sql, $params = []) {
    global $pdo;
    try {
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt;
    } catch (PDOException $e) {
        error_log("Query Error: " . $e->getMessage() . " | SQL: " . $sql);
        throw $e;
    }
}

/**
 * Get single row from database
 * @param string $sql SQL query
 * @param array $params Parameters
 * @return array|false
 */
function fetchRow($sql, $params = []) {
    $stmt = executeQuery($sql, $params);
    return $stmt->fetch();
}

/**
 * Get all rows from database
 * @param string $sql SQL query
 * @param array $params Parameters
 * @return array
 */
function fetchAll($sql, $params = []) {
    $stmt = executeQuery($sql, $params);
    return $stmt->fetchAll();
}

/**
 * Get single value from database
 * @param string $sql SQL query
 * @param array $params Parameters
 * @return mixed
 */
function fetchValue($sql, $params = []) {
    $stmt = executeQuery($sql, $params);
    return $stmt->fetchColumn();
}

/**
 * Insert data and return last insert ID
 * @param string $table Table name
 * @param array $data Associative array of data
 * @return string Last insert ID
 */
function insertData($table, $data) {
    global $pdo;
    
    $columns = implode(',', array_keys($data));
    $placeholders = ':' . implode(', :', array_keys($data));
    
    $sql = "INSERT INTO $table ($columns) VALUES ($placeholders)";
    executeQuery($sql, $data);
    
    return $pdo->lastInsertId();
}

/**
 * Update data in database
 * @param string $table Table name
 * @param array $data Data to update
 * @param string $where WHERE clause
 * @param array $whereParams WHERE parameters
 * @return int Number of affected rows
 */
function updateData($table, $data, $where, $whereParams = []) {
    $setParts = [];
    foreach (array_keys($data) as $key) {
        $setParts[] = "$key = :$key";
    }
    $setClause = implode(', ', $setParts);
    
    // Convert positional parameters to named parameters
    $namedWhereParams = [];
    $whereClause = $where;
    $paramIndex = 0;
    
    // Replace ? with named parameters
    while (strpos($whereClause, '?') !== false) {
        $paramName = 'where_param_' . $paramIndex;
        $whereClause = preg_replace('/\?/', ':' . $paramName, $whereClause, 1);
        $namedWhereParams[$paramName] = $whereParams[$paramIndex];
        $paramIndex++;
    }
    
    $sql = "UPDATE $table SET $setClause WHERE $whereClause";
    $params = array_merge($data, $namedWhereParams);
    
    $stmt = executeQuery($sql, $params);
    return $stmt->rowCount();
}

/**
 * Delete data from database
 * @param string $table Table name
 * @param string $where WHERE clause
 * @param array $params WHERE parameters
 * @return int Number of affected rows
 */
function deleteData($table, $where, $params = []) {
    $sql = "DELETE FROM $table WHERE $where";
    $stmt = executeQuery($sql, $params);
    return $stmt->rowCount();
}

/**
 * Check if database connection is working
 * @return bool
 */
function testConnection() {
    global $pdo;
    try {
        $pdo->query('SELECT 1');
        return true;
    } catch (PDOException $e) {
        return false;
    }
}

/**
 * Get database version
 * @return string
 */
function getDatabaseVersion() {
    return fetchValue("SELECT VERSION()");
}

/**
 * Security Functions
 */

/**
 * Sanitize input data
 * @param mixed $data Input data
 * @return mixed Sanitized data
 */
function sanitizeInput($data) {
    if (is_array($data)) {
        return array_map('sanitizeInput', $data);
    }
    
    return htmlspecialchars(strip_tags(trim($data)), ENT_QUOTES, 'UTF-8');
}

/**
 * Validate email address
 * @param string $email Email to validate
 * @return bool
 */
function validateEmail($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
}

/**
 * Generate secure random token
 * @param int $length Token length
 * @return string
 */
function generateToken($length = 32) {
    return bin2hex(random_bytes($length));
}

// Environment Configuration
define('ENVIRONMENT', 'development'); // Change to 'production' for live site

// Application Constants
define('APP_NAME', 'ORDIVO');
define('APP_VERSION', '1.0.0');
define('APP_URL', 'http://localhost/ordivo/');
define('UPLOAD_PATH', 'uploads/');
define('MAX_FILE_SIZE', 5 * 1024 * 1024); // 5MB

// Start output buffering to prevent session warnings
if (!ob_get_level()) {
    ob_start();
}

// Session Configuration - Must be set BEFORE session_start()
if (session_status() === PHP_SESSION_NONE) {
    ini_set('session.cookie_httponly', 1);
    ini_set('session.cookie_secure', 0); // Set to 1 for HTTPS
    ini_set('session.use_strict_mode', 1);
    ini_set('session.cookie_samesite', 'Strict');
    
    // Start session
    @session_start(); // @ suppresses warnings
}

// Regenerate session ID periodically for security
if (!isset($_SESSION['last_regeneration'])) {
    $_SESSION['last_regeneration'] = time();
} elseif (time() - $_SESSION['last_regeneration'] > 300) { // 5 minutes
    session_regenerate_id(true);
    $_SESSION['last_regeneration'] = time();
}

/**
 * CSRF Protection
 */
function generateCSRFToken() {
    if (!isset($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = generateToken();
    }
    return $_SESSION['csrf_token'];
}

function validateCSRFToken($token) {
    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}

/**
 * Rate Limiting
 */
function checkRateLimit($action, $limit = 5, $window = 300) {
    $key = $action . '_' . $_SERVER['REMOTE_ADDR'];
    
    if (!isset($_SESSION['rate_limit'][$key])) {
        $_SESSION['rate_limit'][$key] = ['count' => 0, 'reset' => time() + $window];
    }
    
    $data = $_SESSION['rate_limit'][$key];
    
    if (time() > $data['reset']) {
        $_SESSION['rate_limit'][$key] = ['count' => 1, 'reset' => time() + $window];
        return true;
    }
    
    if ($data['count'] >= $limit) {
        return false;
    }
    
    $_SESSION['rate_limit'][$key]['count']++;
    return true;
}

// Success message - Database connection established
if (ENVIRONMENT === 'development' && testConnection()) {
    // Only show in development mode
    // echo "<!-- ORDIVO Database Connection: SUCCESS -->\n";
}
?>