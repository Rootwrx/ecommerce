<?php
require_once 'config.php';
require_once 'db.php';

// Validate and sanitize input
function sanitize($input) {
    global $db;
    return $db->escapeString(htmlspecialchars(trim($input)));
}

// Redirect to a URL
function redirect($url) {
    header("Location: $url");
    exit();
}

// Check if user is logged in
function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

// Check if user is admin
function isAdmin() {
    return isset($_SESSION['user_type']) && $_SESSION['user_type'] === 'admin';
}

// Display error message
function displayError($message) {
    return "<div class='error-message'>$message</div>";
}

// Display success message
function displaySuccess($message) {
    return "<div class='success-message'>$message</div>";
}

// Get user by ID
function getUserById($userId) {
    global $db;
    $userId = (int)$userId;
    $stmt = $db->prepare("SELECT * FROM users WHERE user_id = ?");
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $result = $stmt->get_result();
    return $result->fetch_assoc();
}

// Get product by ID
function getProductById($productId) {
    global $db;
    $productId = (int)$productId;
    $stmt = $db->prepare("SELECT p.*, c.name as category_name 
                         FROM products p 
                         LEFT JOIN categories c ON p.category_id = c.category_id 
                         WHERE p.product_id = ?");
    $stmt->bind_param("i", $productId);
    $stmt->execute();
    $result = $stmt->get_result();
    return $result->fetch_assoc();
}


// Get all categories
function getCategories() {
    global $db;
    $result = $db->query("SELECT * FROM categories ORDER BY name");
    
    $categories = [];
    while ($row = $result->fetch_assoc()) {
        $categories[] = $row;
    }
    
    return $categories;
}

// Get user's cart
function getUserCart($userId) {
    global $db;
    $userId = (int)$userId;
    
    // First, verify that the user exists
    $stmt = $db->prepare("SELECT user_id FROM users WHERE user_id = ?");
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        // User does not exist, throw an exception or return false
        throw new Exception("User with ID $userId does not exist");
    }
    
    // Check if user has a cart
    $stmt = $db->prepare("SELECT * FROM cart WHERE user_id = ?");
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $result = $stmt->get_result();
    $cart = $result->fetch_assoc();
    
    // If no cart exists, create one
    if (!$cart) {
        $stmt = $db->prepare("INSERT INTO cart (user_id) VALUES (?)");
        $stmt->bind_param("i", $userId);
        
        // Check if the insert was successful
        if (!$stmt->execute()) {
            throw new Exception("Failed to create cart for user ID $userId: " . $stmt->error);
        }
        
        $cartId = $db->lastInsertId();
    } else {
        $cartId = $cart['cart_id'];
    }
    
    // Get cart items
    $stmt = $db->prepare("SELECT ci.*, p.title, p.price, p.image 
                         FROM cart_items ci 
                         JOIN products p ON ci.product_id = p.product_id 
                         WHERE ci.cart_id = ?");
    $stmt->bind_param("i", $cartId);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $cartItems = [];
    $totalPrice = 0;
    
    while ($item = $result->fetch_assoc()) {
        $item['subtotal'] = $item['price'] * $item['quantity'];
        $totalPrice += $item['subtotal'];
        $cartItems[] = $item;
    }
    
    return [
        'cart_id' => $cartId,
        'items' => $cartItems,
        'total_price' => $totalPrice,
        'item_count' => count($cartItems)
    ];
}

// Add product to cart
function addToCart($userId, $productId, $quantity = 1) {
    global $db;
    $userId = (int)$userId;
    $productId = (int)$productId;
    $quantity = (int)$quantity;
    
    // Get user's cart
    $cart = getUserCart($userId);
    $cartId = $cart['cart_id'];
    
    // Check if product already in cart
    $stmt = $db->prepare("SELECT * FROM cart_items WHERE cart_id = ? AND product_id = ?");
    $stmt->bind_param("ii", $cartId, $productId);
    $stmt->execute();
    $result = $stmt->get_result();
    $cartItem = $result->fetch_assoc();
    
    if ($cartItem) {
        // Update quantity
        $newQuantity = $cartItem['quantity'] + $quantity;
        $stmt = $db->prepare("UPDATE cart_items SET quantity = ? WHERE cart_item_id = ?");
        $stmt->bind_param("ii", $newQuantity, $cartItem['cart_item_id']);
        return $stmt->execute();
    } else {
        // Add new item
        $stmt = $db->prepare("INSERT INTO cart_items (cart_id, product_id, quantity) VALUES (?, ?, ?)");
        $stmt->bind_param("iii", $cartId, $productId, $quantity);
        return $stmt->execute();
    }
}

// Update cart item quantity
function updateCartItem($cartItemId, $quantity) {
    global $db;
    $cartItemId = (int)$cartItemId;
    $quantity = (int)$quantity;
    
    if ($quantity <= 0) {
        // Remove item if quantity is 0 or negative
        $stmt = $db->prepare("DELETE FROM cart_items WHERE cart_item_id = ?");
        $stmt->bind_param("i", $cartItemId);
    } else {
        // Update quantity
        $stmt = $db->prepare("UPDATE cart_items SET quantity = ? WHERE cart_item_id = ?");
        $stmt->bind_param("ii", $quantity, $cartItemId);
    }
    
    return $stmt->execute();
}

// Remove item from cart
function removeCartItem($cartItemId) {
    global $db;
    $cartItemId = (int)$cartItemId;
    
    $stmt = $db->prepare("DELETE FROM cart_items WHERE cart_item_id = ?");
    $stmt->bind_param("i", $cartItemId);
    return $stmt->execute();
}

// Create order from cart
function createOrder($userId, $shippingAddress, $paymentMethod) {
    global $db;
    $userId = (int)$userId;
    
    // Get user's cart
    $cart = getUserCart($userId);
    
    if (empty($cart['items'])) {
        return false; // Cart is empty
    }
    
    // Start transaction
    $db->getConnection()->begin_transaction();
    
    try {
        // Create order
        $stmt = $db->prepare("INSERT INTO orders (user_id, total_amount, shipping_address, payment_method) 
                             VALUES (?, ?, ?, ?)");
        $stmt->bind_param("idss", $userId, $cart['total_price'], $shippingAddress, $paymentMethod);
        $stmt->execute();
        $orderId = $db->lastInsertId();
        
        // Add order items
        foreach ($cart['items'] as $item) {
            $stmt = $db->prepare("INSERT INTO order_items (order_id, product_id, quantity, price) 
                                 VALUES (?, ?, ?, ?)");
            $stmt->bind_param("iiid", $orderId, $item['product_id'], $item['quantity'], $item['price']);
            $stmt->execute();
        }
        
        // Clear cart
        $stmt = $db->prepare("DELETE FROM cart_items WHERE cart_id = ?");
        $stmt->bind_param("i", $cart['cart_id']);
        $stmt->execute();
        
        // Commit transaction
        $db->getConnection()->commit();
        
        return $orderId;
    } catch (Exception $e) {
        // Rollback transaction on error
        $db->getConnection()->rollback();
        return false;
    }
}

// Import products from JSON file
function importProductsFromJson($jsonFile) {
    global $db;
    
    if (!file_exists($jsonFile)) {
        return false;
    }
    
    $jsonData = file_get_contents($jsonFile);
    $products = json_decode($jsonData, true);
    
    if (!$products) {
        return false;
    }
    
    $db->getConnection()->begin_transaction();
    
    try {
        foreach ($products as $product) {
            // Get category ID
            $categoryName = $product['category'];
            $stmt = $db->prepare("SELECT category_id FROM categories WHERE name = ?");
            $stmt->bind_param("s", $categoryName);
            $stmt->execute();
            $result = $stmt->get_result();
            $category = $result->fetch_assoc();
            
            if (!$category) {
                // Create category if it doesn't exist
                $stmt = $db->prepare("INSERT INTO categories (name) VALUES (?)");
                $stmt->bind_param("s", $categoryName);
                $stmt->execute();
                $categoryId = $db->lastInsertId();
            } else {
                $categoryId = $category['category_id'];
            }
            
            // Check if product already exists
            $stmt = $db->prepare("SELECT product_id FROM products WHERE title = ?");
            $stmt->bind_param("s", $product['title']);
            $stmt->execute();
            $result = $stmt->get_result();
            
            if ($result->num_rows === 0) {
                // Insert product
                $stmt = $db->prepare("INSERT INTO products (title, description, price, category_id, image, rating_rate, rating_count) 
                                     VALUES (?, ?, ?, ?, ?, ?, ?)");
                $stmt->bind_param("sssissi", 
                    $product['title'], 
                    $product['description'], 
                    $product['price'], 
                    $categoryId, 
                    $product['image'], 
                    $product['rating']['rate'], 
                    $product['rating']['count']
                );
                $stmt->execute();
            }
        }
        
        $db->getConnection()->commit();
        return true;
    } catch (Exception $e) {
        $db->getConnection()->rollback();
        return false;
    }
}


// Get all products with optional filtering
function getProducts($category = null, $search = null, $sort = null, $limit = null, $offset = 0)
{
    global $db;

    $sql = "SELECT p.*, c.name as category_name 
            FROM products p 
            LEFT JOIN categories c ON p.category_id = c.category_id 
            WHERE 1=1";

    $params = [];
    $types = "";

    if ($category) {
        // Make sure category is a string
        $cat_value = is_array($category) ? $category[0] : $category;
        $sql .= " AND c.name = ?";
        $params[] = $cat_value;
        $types .= "s";
    }

    if ($search) {
        $sql .= " AND (p.title LIKE ? OR p.description LIKE ?)";
        $searchTerm = "%$search%";
        $params[] = $searchTerm;
        $params[] = $searchTerm;
        $types .= "ss";
    }

    if ($sort) {
        switch ($sort) {
            case 'price_asc':
                $sql .= " ORDER BY p.price ASC";
                break;
            case 'price_desc':
                $sql .= " ORDER BY p.price DESC";
                break;
            case 'name_asc':
                $sql .= " ORDER BY p.title ASC";
                break;
            case 'name_desc':
                $sql .= " ORDER BY p.title DESC";
                break;
            case 'rating':
                $sql .= " ORDER BY p.rating_rate DESC";
                break;
            default:
                $sql .= " ORDER BY p.product_id DESC";
        }
    } else {
        $sql .= " ORDER BY p.product_id DESC";
    }

    if ($limit) {
        $sql .= " LIMIT ?, ?";
        $params[] = $offset;
        $params[] = $limit;
        $types .= "ii";
    }

    $stmt = $db->prepare($sql);

    if (!empty($params)) {
        $stmt->bind_param($types, ...$params);
    }

    $stmt->execute();
    $result = $stmt->get_result();

    $products = [];
    while ($row = $result->fetch_assoc()) {
        $products[] = $row;
    }

    return $products;
}

?>