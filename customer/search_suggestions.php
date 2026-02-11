<?php
/**
 * ORDIVO - Search Suggestions API
 * Provides autocomplete suggestions for search functionality
 */

require_once '../config/db_connection.php';

// Set JSON header
header('Content-Type: application/json');

// Check if query parameter exists
if (!isset($_GET['q']) || empty(trim($_GET['q']))) {
    echo json_encode([]);
    exit;
}

$query = trim($_GET['q']);
$limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 10;

// Sanitize input
$query = htmlspecialchars($query, ENT_QUOTES, 'UTF-8');
$searchTerm = "%$query%";
$exactSearchTerm = "$query%";

$suggestions = [];

try {
    // Search in products (prioritize exact matches)
    $products = fetchAll("
        SELECT DISTINCT 
            p.name as text,
            'product' as type,
            p.id,
            v.name as vendor_name,
            p.price,
            CASE 
                WHEN p.name LIKE ? THEN 1 
                ELSE 2 
            END as priority
        FROM products p 
        INNER JOIN users v ON p.vendor_id = v.id AND v.role = 'vendor' AND v.status = 'active'
        WHERE p.name LIKE ? 
        AND p.is_available = 1
        ORDER BY priority ASC, p.name ASC 
        LIMIT ?
    ", [$exactSearchTerm, $searchTerm, $limit]);

    foreach ($products as $product) {
        $priceText = '৳' . number_format($product['price'], 0);
        $suggestions[] = [
            'text' => $product['text'],
            'type' => 'product',
            'icon' => 'fas fa-utensils',
            'subtitle' => ($product['vendor_name'] ? 'from ' . $product['vendor_name'] : '') . ' • ' . $priceText,
            'id' => $product['id']
        ];
    }

    // Search in categories
    $categories = fetchAll("
        SELECT DISTINCT 
            name as text,
            'category' as type,
            id,
            CASE 
                WHEN name LIKE ? THEN 1 
                ELSE 2 
            END as priority
        FROM categories 
        WHERE name LIKE ? 
        AND is_active = 1
        ORDER BY priority ASC, name ASC 
        LIMIT ?
    ", [$exactSearchTerm, $searchTerm, 5]);

    foreach ($categories as $category) {
        $suggestions[] = [
            'text' => $category['text'],
            'type' => 'category',
            'icon' => 'fas fa-tags',
            'subtitle' => 'Category',
            'id' => $category['id']
        ];
    }

    // Search in vendors/restaurants
    $vendors = fetchAll("
        SELECT DISTINCT 
            name as text,
            'vendor' as type,
            id,
            CASE 
                WHEN name LIKE ? THEN 1 
                ELSE 2 
            END as priority
        FROM users 
        WHERE role = 'vendor' 
        AND status = 'active'
        AND name LIKE ? 
        ORDER BY priority ASC, name ASC 
        LIMIT ?
    ", [$exactSearchTerm, $searchTerm, 5]);

    foreach ($vendors as $vendor) {
        $suggestions[] = [
            'text' => $vendor['text'],
            'type' => 'vendor',
            'icon' => 'fas fa-store',
            'subtitle' => 'Restaurant',
            'id' => $vendor['id']
        ];
    }

    // Popular search terms (enhanced with Bangladeshi food items)
    $popularTerms = [
        // Main dishes
        'Biryani', 'Kacchi Biryani', 'Chicken Biryani', 'Mutton Biryani',
        'Rice', 'Fried Rice', 'Chicken Rice', 'Beef Rice',
        'Burger', 'Chicken Burger', 'Beef Burger', 'Fish Burger',
        'Pizza', 'Chicken Pizza', 'Beef Pizza', 'Vegetable Pizza',
        'Pasta', 'Chicken Pasta', 'Beef Pasta',
        'Noodles', 'Chicken Noodles', 'Beef Noodles', 'Vegetable Noodles',
        
        // Bangladeshi dishes
        'Bhuna Khichuri', 'Tehari', 'Polao', 'Khichuri',
        'Chicken Curry', 'Beef Curry', 'Fish Curry', 'Mutton Curry',
        'Dal', 'Chicken Roast', 'Beef Roast',
        'Hilsa Fish', 'Rui Fish', 'Katla Fish',
        'Vorta', 'Bhaji', 'Shutki',
        
        // Snacks & Fast Food
        'Sandwich', 'Club Sandwich', 'Chicken Sandwich',
        'Roll', 'Chicken Roll', 'Beef Roll', 'Egg Roll',
        'Paratha', 'Chicken Paratha', 'Beef Paratha',
        'Samosa', 'Singara', 'Pitha',
        'Fuchka', 'Chotpoti', 'Jhalmuri',
        
        // Drinks & Desserts
        'Tea', 'Coffee', 'Lassi', 'Borhani',
        'Juice', 'Mango Juice', 'Orange Juice',
        'Ice Cream', 'Kulfi', 'Faluda',
        'Cake', 'Pastry', 'Cookies',
        'Sweets', 'Rasgulla', 'Sandesh', 'Mishti',
        
        // Meal times
        'Breakfast', 'Lunch', 'Dinner', 'Snacks',
        'Healthy Food', 'Fast Food', 'Street Food'
    ];

    // Add popular terms if query matches
    foreach ($popularTerms as $term) {
        if (stripos($term, $query) !== false && count($suggestions) < $limit) {
            // Check if it's an exact match at the beginning
            $isExactMatch = stripos($term, $query) === 0;
            
            $suggestions[] = [
                'text' => $term,
                'type' => 'popular',
                'icon' => $isExactMatch ? 'fas fa-fire' : 'fas fa-search',
                'subtitle' => $isExactMatch ? 'Popular search' : 'Suggested search',
                'id' => null,
                'priority' => $isExactMatch ? 1 : 3
            ];
        }
    }

    // Limit total suggestions
    $suggestions = array_slice($suggestions, 0, $limit);

    // Sort suggestions by relevance (exact matches first, then by type priority)
    usort($suggestions, function($a, $b) use ($query) {
        // Check for exact matches at the beginning
        $aExact = (stripos($a['text'], $query) === 0) ? 0 : 1;
        $bExact = (stripos($b['text'], $query) === 0) ? 0 : 1;
        
        if ($aExact !== $bExact) {
            return $aExact - $bExact;
        }
        
        // Type priority: products > categories > vendors > popular
        $typePriority = [
            'product' => 1,
            'category' => 2,
            'vendor' => 3,
            'popular' => 4
        ];
        
        $aPriority = $typePriority[$a['type']] ?? 5;
        $bPriority = $typePriority[$b['type']] ?? 5;
        
        if ($aPriority !== $bPriority) {
            return $aPriority - $bPriority;
        }
        
        return strcasecmp($a['text'], $b['text']);
    });

} catch (Exception $e) {
    error_log("Search suggestions error: " . $e->getMessage());
    // Return empty array on error
    $suggestions = [];
}

echo json_encode($suggestions);
?>