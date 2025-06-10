<?php
session_start();
require_once "../config/db.php";

// Check if user is logged in and is a consumer
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'consumer') {
    header("Location: ../index.php");
    exit;
}

$consumerId = $_SESSION['user_id'];
$response = [
    'success' => false,
    'message' => ''
];

// Process add to cart
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['product_id'])) {
    $productId = filter_input(INPUT_POST, 'product_id', FILTER_SANITIZE_NUMBER_INT);
    $quantity = isset($_POST['quantity']) ? filter_input(INPUT_POST, 'quantity', FILTER_SANITIZE_NUMBER_INT) : 1;
    
    // Validate product ID
    if (empty($productId) || !is_numeric($productId)) {
        $response['message'] = "Invalid product";
    } 
    // Validate quantity
    elseif (empty($quantity) || !is_numeric($quantity) || $quantity < 1) {
        $response['message'] = "Invalid quantity";
    } 
    else {
        try {
            // Check if product exists and is not expired
            $today = date('Y-m-d');
            $stmt = $db->prepare("
                SELECT p.*, m.city 
                FROM products p
                JOIN markets m ON p.market_id = m.market_id
                WHERE p.product_id = ? AND p.expiration_date >= ?
            ");
            $stmt->execute([$productId, $today]);
            $product = $stmt->fetch();
            
            if (!$product) {
                $response['message'] = "Product not found or expired";
            } 
            // Check if product is in stock
            elseif ($product['stock'] < $quantity) {
                $response['message'] = "Not enough stock available";
            } 
            // Check if consumer city matches market city
            elseif (!isset($_SESSION['ajax_request'])) {
                // Get consumer city
                $stmt = $db->prepare("SELECT city FROM consumers WHERE consumer_id = ?");
                $stmt->execute([$consumerId]);
                $consumer = $stmt->fetch();
                
                if ($consumer['city'] !== $product['city']) {
                    $response['message'] = "This product is not available in your city";
                } else {
                    // Add to cart
                    addToCart($db, $consumerId, $productId, $quantity, $response);
                }
            } else {
                // Add to cart (AJAX request)
                addToCart($db, $consumerId, $productId, $quantity, $response);
            }
        } catch (PDOException $e) {
            $response['message'] = "Database error: " . $e->getMessage();
        }
    }
    
    // Handle AJAX request
    if (isset($_SESSION['ajax_request'])) {
        header('Content-Type: application/json');
        echo json_encode($response);
        exit;
    } else {
        // Redirect with status
        $status = $response['success'] ? 'added' : 'error';
        $message = $response['message'];
        header("Location: " . (isset($_POST['return_to_cart']) ? "cart.php" : "dashboard.php") . "?status=$status&message=" . urlencode($message));
        exit;
    }
} else {
    // Redirect to dashboard if accessed directly
    header("Location: dashboard.php");
    exit;
}

// Function to add product to cart
function addToCart($db, $consumerId, $productId, $quantity, &$response) {
    // Check if item already exists in cart
    $stmt = $db->prepare("SELECT * FROM cart_items WHERE consumer_id = ? AND product_id = ?");
    $stmt->execute([$consumerId, $productId]);
    $cartItem = $stmt->fetch();
    
    if ($cartItem) {
        // Update quantity
        $newQuantity = $cartItem['quantity'] + $quantity;
        $stmt = $db->prepare("UPDATE cart_items SET quantity = ? WHERE cart_id = ?");
        $stmt->execute([$newQuantity, $cartItem['cart_id']]);
        
        $response['success'] = true;
        $response['message'] = "Product quantity updated in cart";
    } else {
        // Insert new cart item
        $stmt = $db->prepare("INSERT INTO cart_items (consumer_id, product_id, quantity) VALUES (?, ?, ?)");
        $stmt->execute([$consumerId, $productId, $quantity]);
        
        $response['success'] = true;
        $response['message'] = "Product added to cart";
    }
}
?> 