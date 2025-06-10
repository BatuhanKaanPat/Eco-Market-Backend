<?php
session_start();
require_once "../config/db.php";

// Check if user is logged in and is a consumer
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'consumer') {
    if (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') {
        // AJAX request
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'Authentication required']);
    } else {
        // Regular request
        header("Location: ../index.php");
    }
    exit;
}

$consumerId = $_SESSION['user_id'];
$errors = [];
$success = false;

// Process purchase
try {
    // Get valid cart items (not expired)
    $today = date('Y-m-d');
    $stmt = $db->prepare("
        SELECT ci.*, p.title, p.stock, p.product_id
        FROM cart_items ci
        JOIN products p ON ci.product_id = p.product_id
        WHERE ci.consumer_id = ? AND p.expiration_date >= ?
    ");
    $stmt->execute([$consumerId, $today]);
    $validItems = $stmt->fetchAll();
    
    // Begin transaction
    $db->beginTransaction();
    
    // Check if there are valid items to purchase
    if (count($validItems) > 0) {
        // Process each item
        foreach ($validItems as $item) {
            // Check if stock is still available
            if ($item['stock'] < $item['quantity']) {
                $errors[] = "Not enough stock for {$item['title']}. Available: {$item['stock']}";
                continue;
            }
            
            // Update product stock (drop products from the system)
            $newStock = $item['stock'] - $item['quantity'];
            $stmt = $db->prepare("UPDATE products SET stock = ? WHERE product_id = ?");
            $stmt->execute([$newStock, $item['product_id']]);
        }
        
        // If there are errors, roll back the transaction
        if (!empty($errors)) {
            $db->rollBack();
        } else {
            // Empty the cart (remove all cart items)
            $stmt = $db->prepare("DELETE FROM cart_items WHERE consumer_id = ?");
            $stmt->execute([$consumerId]);
            
            $success = true;
            $db->commit();
        }
    } else {
        // No valid items to purchase
        $errors[] = "No valid products in cart to purchase";
        $db->rollBack();
    }
} catch (PDOException $e) {
    $db->rollBack();
    $errors[] = "Error processing purchase: " . $e->getMessage();
}

// Return response based on request type
if (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') {
    // AJAX request
    header('Content-Type: application/json');
    if ($success) {
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['success' => false, 'message' => implode(", ", $errors)]);
    }
} else {
    // Regular request
    if ($success) {
        header("Location: purchase_success.php");
    } else {
        $errorString = implode(", ", $errors);
        header("Location: cart.php?status=error&message=" . urlencode($errorString));
    }
}
exit;
?> 