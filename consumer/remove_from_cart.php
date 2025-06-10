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
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['cart_id'])) {
    $cartId = filter_input(INPUT_POST, 'cart_id', FILTER_SANITIZE_NUMBER_INT);
    
    // Validate input
    if (empty($cartId) || !is_numeric($cartId)) {
        $response['message'] = "Invalid cart item";
    } 
    else {
        try {
            // Verify cart item belongs to user
            $stmt = $db->prepare("SELECT * FROM cart_items WHERE cart_id = ? AND consumer_id = ?");
            $stmt->execute([$cartId, $consumerId]);
            
            if ($stmt->rowCount() === 0) {
                $response['message'] = "Cart item not found";
            } else {
                // Delete the cart item
                $stmt = $db->prepare("DELETE FROM cart_items WHERE cart_id = ?");
                $stmt->execute([$cartId]);
                
                $response['success'] = true;
                $response['message'] = "Item removed from cart";
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