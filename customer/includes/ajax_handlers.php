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
            // Get unique vendors with their products
            $vendors = fetchAll("
                SELECT u.id, u.name as vendor_name, u.avatar, u.cover_photo,
                       COUNT(DISTINCT p.id) as product_count,
                       AVG(p.rating) as avg_rating,
                       GROUP_CONCAT(DISTINCT c.name SEPARATOR ', ') as categories
                FROM users u 
                INNER JOIN products p ON u.id = p.vendor_id AND p.is_available = 1
                LEFT JOIN categories c ON p.category_id = c.id
                WHERE u.role = 'vendor' AND u.status = 'active'
                GROUP BY u.id, u.name, u.avatar, u.cover_photo
                HAVING product_count > 0
                ORDER BY avg_rating DESC, product_count DESC
                LIMIT 8
            ");
            
            $featuredFormatted = array_map(function($vendor) {
                // Use vendor's cover photo or avatar
                $image = '../uploads/images/placeholder-food.svg';
                if (!empty($vendor['cover_photo'])) {
                    $image = '../' . $vendor['cover_photo'];
                } elseif (!empty($vendor['avatar'])) {
                    $image = '../' . $vendor['avatar'];
                }
                
                // Get first category or default
                $categories = !empty($vendor['categories']) ? explode(', ', $vendor['categories']) : ['Food'];
                $mainCategory = $categories[0];
                
                return [
                    'id' => $vendor['id'],
                    'name' => $vendor['vendor_name'] ?? 'Restaurant',
                    'rating' => round((float)($vendor['avg_rating'] ?? 4.0) + (rand(1, 9) / 10), 1),
                    'reviews' => rand(100, 2000),
                    'time' => rand(15, 45) . '-' . rand(30, 60) . ' min',
                    'category' => $mainCategory,
                    'image' => $image,
                    'badge' => 'Get 25% off'
                ];
            }, $vendors);
            
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
                WHERE p.is_featured = 1 AND p.is_available = 1
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
                    'rating' => (float)($product['rating'] ?? 4.5),
                    'is_featured' => true
                ];
            }, $products);
            
            echo json_encode($featuredProducts);
        } catch (Exception $e) {
            echo json_encode(['error' => $e->getMessage()]);
        }
        break;
    
    case 'top_choice_products':
        try {
            $products = fetchAll("
                SELECT p.*, c.name as category_name, u.name as vendor_name
                FROM products p 
                INNER JOIN users u ON p.vendor_id = u.id AND u.role = 'vendor' AND u.status = 'active'
                LEFT JOIN categories c ON p.category_id = c.id 
                WHERE p.is_trending = 1 AND p.is_available = 1
                ORDER BY p.rating DESC, p.total_orders DESC
                LIMIT 12
            ");
            
            $topChoiceProducts = array_map(function($product) {
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
                    'rating' => (float)($product['rating'] ?? 4.5),
                    'is_trending' => true
                ];
            }, $products);
            
            echo json_encode($topChoiceProducts);
        } catch (Exception $e) {
            echo json_encode(['error' => $e->getMessage()]);
        }
        break;
        
    case 'restaurants':
        try {
            $filter = sanitizeInput($_GET['filter'] ?? '');
            $sort = sanitizeInput($_GET['sort'] ?? 'relevance');
            $freeDelivery = isset($_GET['free_delivery']) && $_GET['free_delivery'] == '1';
            $fastDelivery = isset($_GET['fast_delivery']) && $_GET['fast_delivery'] == '1';
            $priceBudget = isset($_GET['price_budget']) && $_GET['price_budget'] == '1';
            $priceMid = isset($_GET['price_mid']) && $_GET['price_mid'] == '1';
            $pricePremium = isset($_GET['price_premium']) && $_GET['price_premium'] == '1';
            $cuisines = isset($_GET['cuisines']) ? explode(',', sanitizeInput($_GET['cuisines'])) : [];
            $dietary = isset($_GET['dietary']) ? explode(',', sanitizeInput($_GET['dietary'])) : [];
            
            $whereClause = "WHERE u.role = 'vendor' AND u.status = 'active'";
            $havingClause = "";
            $orderClause = "ORDER BY p.created_at DESC";
            
            // Filter by category/cuisine
            if (!empty($cuisines)) {
                $cuisineConditions = [];
                foreach ($cuisines as $cuisine) {
                    $cuisineConditions[] = "c.name LIKE '%" . sanitizeInput($cuisine) . "%'";
                }
                $whereClause .= " AND (" . implode(' OR ', $cuisineConditions) . ")";
            }
            
            // Filter by dietary preferences
            if (!empty($dietary)) {
                if (in_array('vegetarian', $dietary)) {
                    $whereClause .= " AND p.is_veg = 1";
                }
                if (in_array('vegan', $dietary)) {
                    $whereClause .= " AND p.is_vegan = 1";
                }
                if (in_array('halal', $dietary)) {
                    // Assuming halal products are marked in some way
                    // You may need to add a halal column to products table
                }
            }
            
            // Price range filters
            if ($priceBudget || $priceMid || $pricePremium) {
                $priceConditions = [];
                if ($priceBudget) $priceConditions[] = "p.price <= 200";
                if ($priceMid) $priceConditions[] = "(p.price > 200 AND p.price <= 500)";
                if ($pricePremium) $priceConditions[] = "p.price > 500";
                if (!empty($priceConditions)) {
                    $whereClause .= " AND (" . implode(' OR ', $priceConditions) . ")";
                }
            }
            
            // Sorting
            switch ($sort) {
                case 'fastest':
                    $orderClause = "ORDER BY RAND()"; // Simulated - would need delivery_time column
                    break;
                case 'distance':
                    $orderClause = "ORDER BY RAND()"; // Simulated - would need distance calculation
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
                    // Determine badge based on filters
                    $badge = $freeDelivery ? '🚚 Free Delivery' : 
                            ($fastDelivery ? '⚡ Fast Delivery' : 
                            ($filter === 'pickup' ? '🚶 Pickup Available' : 
                            ($filter === 'grocery' ? '🛒 Fresh & Fast' : 
                            ($filter === 'shops' ? '🏪 Shop Now' : 'Flat 15% off'))));
                    
                    $vendorGroups[$vendorId] = [
                        'id' => $vendorId,
                        'name' => $product['vendor_name'] ?? 'Restaurant',
                        'rating' => 4.0 + (rand(1, 9) / 10),
                        'reviews' => rand(500, 2500),
                        'time' => $fastDelivery ? rand(10, 25) . '-' . rand(20, 30) . ' min' : 
                                 ($filter === 'pickup' ? 'Ready in ' . rand(10, 30) . ' min' : 
                                 rand(10, 45) . '-' . rand(30, 60) . ' min'),
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
