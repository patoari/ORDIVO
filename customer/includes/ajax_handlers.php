<?php
/**
 * ORDIVO - Homepage AJAX Handlers
 * Handles all AJAX requests for the homepage
 */

if (!isset($_GET['ajax'])) {
    exit('Direct access not allowed');
}

switch ($_GET['ajax']) {
    case 'featured_restaurants':
        try {
            $featured = fetchAll("
                SELECT p.*, c.name as category_name, u.name as vendor_name
                FROM products p 
                INNER JOIN users u ON p.vendor_id = u.id AND u.role = 'vendor' AND u.status = 'active'
                LEFT JOIN categories c ON p.category_id = c.id 
                ORDER BY p.created_at DESC 
                LIMIT 8
            ");
            
            $featuredFormatted = array_map(function($product) {
                return [
                    'id' => $product['id'],
                    'name' => $product['vendor_name'] ?? 'Restaurant',
                    'rating' => 4.5,
                    'reviews' => rand(100, 2000),
                    'time' => '15-45 min',
                    'category' => $product['category_name'] ?? 'Food',
                    'image' => !empty($product['image']) ? 
                        (strpos($product['image'], 'http') === 0 ? $product['image'] : '../uploads/images/' . $product['image']) : 
                        '../uploads/images/placeholder-food.svg',
                    'badge' => 'Get 25% off'
                ];
            }, $featured);
            
            echo json_encode($featuredFormatted);
        } catch (Exception $e) {
            echo json_encode([]);
        }
        break;
        
    case 'featured_products':
        try {
            $products = fetchAll("
                SELECT p.*, c.name as category_name, u.name as vendor_name
                FROM products p 
                INNER JOIN users u ON p.vendor_id = u.id AND u.role = 'vendor' AND u.status = 'active'
                LEFT JOIN categories c ON p.category_id = c.id 
                WHERE p.is_featured = 1
                ORDER BY p.created_at DESC
                LIMIT 12
            ");
            
            $featuredProducts = array_map(function($product) {
                return [
                    'id' => $product['id'],
                    'name' => $product['name'],
                    'description' => $product['description'] ?? $product['short_description'] ?? '',
                    'price' => (float)$product['price'],
                    'image' => !empty($product['image']) ? 
                        (strpos($product['image'], 'http') === 0 ? $product['image'] : '../uploads/images/' . $product['image']) : 
                        '../uploads/images/placeholder-food.svg',
                    'category' => $product['category_name'] ?? 'Food',
                    'vendor_name' => $product['vendor_name'] ?? 'Restaurant',
                    'rating' => (float)($product['rating'] ?? 4.0) + (rand(1, 9) / 10),
                    'is_featured' => true
                ];
            }, $products);
            
            echo json_encode($featuredProducts);
        } catch (Exception $e) {
            echo json_encode(['error' => $e->getMessage()]);
        }
        break;
        
    case 'restaurants':
        try {
            $filter = sanitizeInput($_GET['filter'] ?? '');
            $sort = sanitizeInput($_GET['sort'] ?? 'relevance');
            
            $whereClause = "WHERE u.role = 'vendor' AND u.status = 'active'";
            $orderClause = "ORDER BY p.created_at DESC";
            
            switch ($filter) {
                case 'grocery':
                    $whereClause .= " AND (c.name LIKE '%grocery%' OR c.name LIKE '%mart%' OR c.name LIKE '%store%')";
                    break;
            }
            
            switch ($sort) {
                case 'fastest':
                case 'distance':
                    $orderClause = "ORDER BY RAND()";
                    break;
                case 'top-rated':
                    $orderClause = "ORDER BY p.rating DESC";
                    break;
                default:
                    $orderClause = "ORDER BY p.is_featured DESC, p.rating DESC";
                    break;
            }
            
            try {
                $products = fetchAll("
                    SELECT p.*, c.name as category_name, u.name as vendor_name, u.avatar as vendor_logo, u.cover_photo as vendor_banner
                    FROM products p 
                    INNER JOIN users u ON p.vendor_id = u.id
                    LEFT JOIN categories c ON p.category_id = c.id 
                    $whereClause
                    $orderClause
                    LIMIT 50
                ");
            } catch (Exception $e) {
                error_log("Products query failed: " . $e->getMessage());
                $products = [];
            }
            
            if (empty($products)) {
                echo json_encode([]);
                exit;
            }
            
            $vendorGroups = [];
            foreach ($products as $product) {
                $vendorId = $product['vendor_id'];
                if (!isset($vendorGroups[$vendorId])) {
                    $badge = $filter === 'pickup' ? '🚶 Pickup Available' : 
                            ($filter === 'grocery' ? '🛒 Fresh & Fast' : 
                            ($filter === 'shops' ? '🏪 Shop Now' : 'Flat 15% off'));
                    
                    $vendorGroups[$vendorId] = [
                        'id' => $vendorId,
                        'name' => $product['vendor_name'] ?? 'Restaurant',
                        'rating' => 4.0 + (rand(1, 9) / 10),
                        'reviews' => rand(500, 2500),
                        'time' => $filter === 'pickup' ? 'Ready in ' . rand(10, 30) . ' min' : rand(10, 45) . '-' . rand(30, 60) . ' min',
                        'category' => $product['category_name'] ?? 'Food',
                        'image' => !empty($product['vendor_banner']) ? '../' . $product['vendor_banner'] : 
                                  (!empty($product['image']) ? '../uploads/images/' . $product['image'] : '../uploads/images/placeholder-food.svg'),
                        'logo' => !empty($product['vendor_logo']) ? '../' . $product['vendor_logo'] : null,
                        'badge' => $badge,
                        'offer' => 'Valid for first order',
                        'products' => []
                    ];
                }
                $vendorGroups[$vendorId]['products'][] = $product;
            }
            
            echo json_encode(array_values($vendorGroups));
        } catch (Exception $e) {
            error_log("Homepage restaurants AJAX error: " . $e->getMessage());
            echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
        }
        break;
        
    case 'categories':
        try {
            $statusColumnExists = fetchValue("SHOW COLUMNS FROM categories LIKE 'status'");
            
            if ($statusColumnExists) {
                $categories = fetchAll("
                    SELECT id, name, image, description, icon
                    FROM categories 
                    WHERE status = 'active'
                    ORDER BY name ASC
                ");
            } else {
                $categories = fetchAll("
                    SELECT id, name, image, description, icon
                    FROM categories 
                    ORDER BY name ASC
                ");
            }
            
            foreach ($categories as &$category) {
                if (!empty($category['image']) && strpos($category['image'], 'uploads/') === 0) {
                    $category['image'] = '../' . $category['image'];
                }
            }
            
            echo json_encode($categories);
        } catch (Exception $e) {
            echo json_encode(['error' => $e->getMessage()]);
        }
        break;
        
    default:
        echo json_encode(['error' => 'Invalid AJAX action']);
        break;
}
?>
