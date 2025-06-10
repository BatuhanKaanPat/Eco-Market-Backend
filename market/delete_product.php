<?php
session_start();
require_once "../config/db.php";

// Check if user is logged in and is a market
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'market') {
    header("Location: ../index.php");
    exit;
}

$marketId = $_SESSION['user_id'];

// Check if product ID is provided
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header("Location: dashboard.php?status=error&message=" . urlencode("Invalid product ID"));
    exit;
}

$productId = $_GET['id'];
$status = 'error';
$message = 'Failed to delete product';

// Check for confirmation or show confirmation page
if (isset($_GET['confirm']) && $_GET['confirm'] == 1) {
    try {
        // Get product details
        $stmt = $db->prepare("SELECT * FROM products WHERE product_id = ? AND market_id = ?");
        $stmt->execute([$productId, $marketId]);
        $product = $stmt->fetch();
        
        if (!$product) {
            $message = "Product not found or you don't have permission";
        } else {
            // Delete associated cart items first (foreign key constraint)
            $stmt = $db->prepare("DELETE FROM cart_items WHERE product_id = ?");
            $stmt->execute([$productId]);
            
            // Delete the product
            $stmt = $db->prepare("DELETE FROM products WHERE product_id = ? AND market_id = ?");
            $result = $stmt->execute([$productId, $marketId]);
            
            if ($result) {
                // Remove product image if exists and not default
                $imagePath = "../" . $product['image_path'];
                if (file_exists($imagePath) && $product['image_path'] != "uploads/products/default.jpg") {
                    @unlink($imagePath);
                }
                
                $status = 'success';
                $message = 'Product deleted successfully';
            }
        }
    } catch (PDOException $e) {
        $message = "Database error: " . $e->getMessage();
    }
    
    header("Location: dashboard.php?status=$status&message=" . urlencode($message));
    exit;
} else {
    // Get product details for confirmation
    try {
        $stmt = $db->prepare("SELECT * FROM products WHERE product_id = ? AND market_id = ?");
        $stmt->execute([$productId, $marketId]);
        $product = $stmt->fetch();
        
        if (!$product) {
            header("Location: dashboard.php?status=error&message=" . urlencode("Product not found or you don't have permission"));
            exit;
        }
    } catch (PDOException $e) {
        header("Location: dashboard.php?status=error&message=" . urlencode("Database error"));
        exit;
    }
}

$marketName = $_SESSION['name'];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Delete Product - Eco Market</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        body {
            background-color: #f8f9fa;
        }
        .navbar-brand {
            color: #2e7d32;
            font-weight: bold;
        }
        .confirm-container {
            background-color: #fff;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 0 15px rgba(0, 0, 0, 0.1);
            margin-top: 50px;
        }
        .product-image {
            max-width: 150px;
            max-height: 150px;
            border-radius: 5px;
            margin-bottom: 20px;
        }
    </style>
</head>
<body>
    <!-- Navbar -->
    <nav class="navbar navbar-expand-lg navbar-light bg-light shadow-sm">
        <div class="container">
            <a class="navbar-brand" href="dashboard.php">Eco Market</a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav" aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav me-auto">
                    <li class="nav-item">
                        <a class="nav-link" href="dashboard.php">Dashboard</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="add_product.php">Add Product</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="profile.php">Profile</a>
                    </li>
                </ul>
                <span class="navbar-text me-3">
                    Hello, <?php echo htmlspecialchars($marketName); ?>
                </span>
                <a href="../logout.php" class="btn btn-outline-danger">Logout</a>
            </div>
        </div>
    </nav>
    
    <!-- Confirmation Dialog -->
    <div class="container">
        <div class="row">
            <div class="col-md-6 offset-md-3">
                <div class="confirm-container text-center">
                    <div class="text-danger mb-4">
                        <i class="fas fa-exclamation-triangle fa-4x"></i>
                    </div>
                    
                    <h2 class="mb-4">Delete Product</h2>
                    
                    <div class="text-center mb-4">
                        <img src="<?php echo htmlspecialchars('../' . $product['image_path']); ?>" class="product-image" alt="<?php echo htmlspecialchars($product['title']); ?>">
                    </div>
                    
                    <p class="mb-1"><strong>Product:</strong> <?php echo htmlspecialchars($product['title']); ?></p>
                    <p class="mb-1"><strong>Price:</strong> ₺<?php echo htmlspecialchars($product['normal_price']); ?> / ₺<?php echo htmlspecialchars($product['discounted_price']); ?></p>
                    <p class="mb-4"><strong>Stock:</strong> <?php echo htmlspecialchars($product['stock']); ?> units</p>
                    
                    <div class="alert alert-danger">
                        <p class="mb-0">Are you sure you want to delete this product? This action cannot be undone!</p>
                    </div>
                    
                    <div class="d-flex justify-content-center mt-4">
                        <a href="dashboard.php" class="btn btn-secondary me-3">Cancel</a>
                        <a href="delete_product.php?id=<?php echo $productId; ?>&confirm=1" class="btn btn-danger">Yes, Delete Product</a>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html> 