<?php
/**
 * Test page to verify vendor information is loading correctly
 * Access: http://localhost/ordivo/vendor/test_vendor_info.php
 */

require_once '../config/db_connection.php';

// Check if user is logged in
if (!isset($_SESSION['logged_in']) || $_SESSION['user_role'] !== 'vendor') {
    die('Please login as vendor first');
}

$vendorId = $_SESSION['user_id'];

echo "<h2>Vendor Information Test</h2>";
echo "<p>User ID (owner_id): $vendorId</p>";
echo "<hr>";

// Test 1: Direct query
global $pdo;
$stmt = $pdo->prepare("SELECT * FROM vendors WHERE owner_id = ?");
$stmt->execute([$vendorId]);
$vendor = $stmt->fetch(PDO::FETCH_ASSOC);

if ($vendor) {
    echo "<h3>✅ Vendor Found in Database</h3>";
    echo "<table border='1' cellpadding='10'>";
    echo "<tr><th>Field</th><th>Value</th></tr>";
    echo "<tr><td>ID</td><td>{$vendor['id']}</td></tr>";
    echo "<tr><td>Business Name</td><td><strong style='color: blue; font-size: 18px;'>{$vendor['name']}</strong></td></tr>";
    echo "<tr><td>Logo Path</td><td>{$vendor['logo']}</td></tr>";
    echo "<tr><td>Owner ID</td><td>{$vendor['owner_id']}</td></tr>";
    
    if (!empty($vendor['logo'])) {
        $logoPath = '../' . $vendor['logo'];
        echo "<tr><td>Logo Preview</td><td><img src='$logoPath' style='max-width: 200px; max-height: 200px;' onerror=\"this.src=''; this.alt='Logo not found'\"></td></tr>";
    }
    
    echo "</table>";
} else {
    echo "<h3>❌ No Vendor Found</h3>";
    echo "<p>No vendor record found with owner_id = $vendorId</p>";
    
    // Check if user exists
    $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
    $stmt->execute([$vendorId]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($user) {
        echo "<h4>User Information:</h4>";
        echo "<pre>";
        print_r([
            'id' => $user['id'],
            'name' => $user['name'],
            'email' => $user['email'],
            'role' => $user['role']
        ]);
        echo "</pre>";
    }
}

echo "<hr>";
echo "<h3>All Vendors in Database:</h3>";
$stmt = $pdo->query("SELECT id, owner_id, name, logo FROM vendors ORDER BY id");
$allVendors = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo "<table border='1' cellpadding='10'>";
echo "<tr><th>ID</th><th>Owner ID</th><th>Business Name</th><th>Logo</th></tr>";
foreach ($allVendors as $v) {
    $highlight = ($v['owner_id'] == $vendorId) ? "style='background: yellow;'" : "";
    echo "<tr $highlight>";
    echo "<td>{$v['id']}</td>";
    echo "<td>{$v['owner_id']}</td>";
    echo "<td><strong>{$v['name']}</strong></td>";
    echo "<td>{$v['logo']}</td>";
    echo "</tr>";
}
echo "</table>";

echo "<hr>";
echo "<p><a href='dashboard.php'>← Back to Dashboard</a></p>";
echo "<p><strong>Note:</strong> If you changed the business name, make sure you're updating the 'vendors' table, not the 'users' table.</p>";
?>
