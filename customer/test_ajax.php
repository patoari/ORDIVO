<?php
/**
 * AJAX Endpoint Test Script
 * Tests all AJAX endpoints to verify they're working
 */

require_once '../config/db_connection.php';

echo "<!DOCTYPE html>
<html>
<head>
    <title>AJAX Endpoint Test</title>
    <style>
        body { font-family: Arial, sans-serif; padding: 20px; }
        .test { margin: 20px 0; padding: 15px; border: 1px solid #ddd; border-radius: 5px; }
        .success { background: #d4edda; border-color: #c3e6cb; }
        .error { background: #f8d7da; border-color: #f5c6cb; }
        h2 { margin-top: 0; }
        pre { background: #f5f5f5; padding: 10px; overflow-x: auto; }
    </style>
</head>
<body>
    <h1>AJAX Endpoint Tests</h1>
";

$endpoints = [
    'featured_restaurants',
    'featured_products',
    'top_choice_products',
    'categories',
    'restaurants'
];

foreach ($endpoints as $endpoint) {
    echo "<div class='test'>";
    echo "<h2>Testing: $endpoint</h2>";
    
    try {
        // Simulate the AJAX request
        $_GET['ajax'] = $endpoint;
        
        // Capture output
        ob_start();
        include 'includes/ajax_handlers.php';
        $output = ob_get_clean();
        
        // Try to decode JSON
        $data = json_decode($output, true);
        
        if (json_last_error() === JSON_ERROR_NONE) {
            if (isset($data['error'])) {
                echo "<div class='error'>";
                echo "<strong>Error:</strong> " . htmlspecialchars($data['error']);
                if (isset($data['trace'])) {
                    echo "<pre>" . htmlspecialchars($data['trace']) . "</pre>";
                }
                echo "</div>";
            } else {
                echo "<div class='success'>";
                echo "<strong>Success!</strong> Returned " . count($data) . " items";
                echo "<pre>" . htmlspecialchars(json_encode($data, JSON_PRETTY_PRINT)) . "</pre>";
                echo "</div>";
            }
        } else {
            echo "<div class='error'>";
            echo "<strong>Invalid JSON:</strong><br>";
            echo "<pre>" . htmlspecialchars($output) . "</pre>";
            echo "</div>";
        }
        
    } catch (Exception $e) {
        echo "<div class='error'>";
        echo "<strong>Exception:</strong> " . htmlspecialchars($e->getMessage());
        echo "<pre>" . htmlspecialchars($e->getTraceAsString()) . "</pre>";
        echo "</div>";
    }
    
    echo "</div>";
    
    // Reset for next test
    unset($_GET['ajax']);
}

echo "</body></html>";
?>
