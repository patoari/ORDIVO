<?php
/**
 * Test page to debug image loading
 */

require_once '../config/db_connection.php';

if (!isset($_SESSION['logged_in']) || $_SESSION['user_role'] !== 'vendor') {
    die('Please login as vendor');
}

$vendorId = $_SESSION['user_id'];

// Get vendor info
$vendor = fetchRow("SELECT * FROM users WHERE id = ?", [$vendorId]);

// Get vendor business info
global $pdo;
$stmt = $pdo->prepare("SELECT v.name, v.logo, v.banner_image FROM vendors v WHERE v.owner_id = ? LIMIT 1");
$stmt->execute([$vendorId]);
$vendorInfo = $stmt->fetch(PDO::FETCH_ASSOC);

echo "<h2>Debug: Image Loading Test</h2>";
echo "<hr>";

echo "<h3>User Data (\$vendor):</h3>";
echo "<pre>";
print_r([
    'id' => $vendor['id'] ?? 'NOT SET',
    'name' => $vendor['name'] ?? 'NOT SET',
    'avatar' => $vendor['avatar'] ?? 'NOT SET',
]);
echo "</pre>";

echo "<h3>Vendor Info (\$vendorInfo):</h3>";
echo "<pre>";
print_r($vendorInfo);
echo "</pre>";

// Load images using helper
require_once 'components/load_vendor_images.php';

echo "<h3>After load_vendor_images.php:</h3>";
echo "<pre>";
echo "vendorCover: " . ($vendorCover ?? 'NOT SET') . "\n";
echo "userAvatar: " . ($userAvatar ?? 'NOT SET') . "\n";
echo "</pre>";

echo "<hr>";
echo "<h3>Visual Test:</h3>";

if (!empty($vendorCover)) {
    echo "<p><strong>Cover Photo:</strong></p>";
    echo "<img src='" . htmlspecialchars($vendorCover) . "' style='max-width: 400px; border: 2px solid green;' onerror='this.style.border=\"2px solid red\"; this.alt=\"FAILED TO LOAD: " . htmlspecialchars($vendorCover) . "\"'>";
} else {
    echo "<p><strong>Cover Photo:</strong> NOT SET</p>";
}

echo "<br><br>";

if (!empty($userAvatar)) {
    echo "<p><strong>Profile Picture:</strong></p>";
    echo "<img src='" . htmlspecialchars($userAvatar) . "' style='max-width: 200px; border: 2px solid green;' onerror='this.style.border=\"2px solid red\"; this.alt=\"FAILED TO LOAD: " . htmlspecialchars($userAvatar) . "\"'>";
} else {
    echo "<p><strong>Profile Picture:</strong> NOT SET</p>";
}

echo "<hr>";
echo "<p><a href='dashboard.php'>← Back to Dashboard</a></p>";
?>
