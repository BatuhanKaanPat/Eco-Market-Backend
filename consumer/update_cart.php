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
    'message' => ''
];

// Process AJAX request
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['cart_id']) && isset($_POST['quantity'])) {
    $cartId = filter_input(INPUT_POST, 'cart_id', FILTER_SANITIZE_NUMBER_INT);
    $quantity = filter_input(INPUT_POST, 'quantity', FILTER_SANITIZE_NUMBER_INT);
    
    // Validate input
    if (empty($cartId) || !is_numeric($cartId)) {
        $response['message'] = "Invalid cart item";
    } 
    elseif (empty($quantity) || !is_numeric($quantity) || $quantity < 1) {
        $response['message'] = "Quantity must be at least 1";
    } 
    else {
        try {
            // Verify cart item belongs to user
            $stmt = $db->prepare("SELECT ci.*, p.stock, p.expiration_date 
                                  FROM cart_items ci 
                                  JOIN products p ON ci.product_id = p.product_id
                                  WHERE ci.cart_id = ? AND ci.consumer_id = ?");
            $stmt->execute([$cartId, $consumerId]);
            $cartItem = $stmt->fetch();
            
            if (!$cartItem) {
                $response['message'] = "Cart item not found";
            }
            // Check if product is expired
            elseif ($cartItem['expiration_date'] < date('Y-m-d')) {
                $response['message'] = "Cannot update expired product";
            }
            // Check if quantity exceeds stock
            elseif ($quantity > $cartItem['stock']) {
                $response['message'] = "Requested quantity exceeds available stock";
            }
            else {
                // Update quantity
                $stmt = $db->prepare("UPDATE cart_items SET quantity = ? WHERE cart_id = ?");
                $stmt->execute([$quantity, $cartId]);
                
                $response['success'] = true;
                $response['message'] = "Cart updated successfully";
            }
        } catch (PDOException $e) {
            $response['message'] = "Database error: " . $e->getMessage();
        }
    }
} else {
    $response['message'] = "Invalid request";
}

// Return response
echo json_encode($response);
exit;
?> 