<?php
session_start();
require_once "../config/db.php";

// Set headers for AJAX response
header('Content-Type: application/json');

// Check if user is logged in and is a consumer
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'consumer') {
    echo json_encode(['success' => false, 'message' => 'Authentication required']);
    exit;
}

$consumerId = $_SESSION['user_id'];
$response = [
    'success' => false,
    'subtotal' => '0.00',
    'total' => '0.00',
    'items' => []
];

try {
    // Get cart items with product details
    $stmt = $db->prepare("
        SELECT ci.*, p.discounted_price, p.expiration_date
        FROM cart_items ci
        JOIN products p ON ci.product_id = p.product_id
        WHERE ci.consumer_id = ?
    ");
    $stmt->execute([$consumerId]);
    $cartItems = $stmt->fetchAll();
    
    $subtotal = 0;
    $today = date('Y-m-d');
    
    // Calculate totals
    foreach ($cartItems as $item) {
        $isExpired = ($item['expiration_date'] < $today);
        $itemSubtotal = $isExpired ? 0 : $item['discounted_price'] * $item['quantity'];
        $subtotal += $itemSubtotal;
        
        // Store item data for response
        $response['items'][$item['cart_id']] = [
            'expired' => $isExpired,
            'subtotal' => number_format($itemSubtotal, 2)
        ];
    }
    
    $response['success'] = true;
    $response['subtotal'] = number_format($subtotal, 2);
    $response['total'] = number_format($subtotal, 2); // Add tax or shipping here if needed
    
} catch (PDOException $e) {
    $response['message'] = "Database error: " . $e->getMessage();
}

// Return response
echo json_encode($response);
exit;
?> 