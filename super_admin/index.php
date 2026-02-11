<?php
/**
 * ORDIVO - Super Admin Dashboard Quick Links
 */

require_once '../config/db_connection.php';

if (!isset($_SESSION['logged_in']) || $_SESSION['user_role'] !== 'super_admin') {
    header('Location: ../auth/login.php');
    exit;
}

// Redirect to main dashboard
header('Location: dashboard.php');
exit;
?>
