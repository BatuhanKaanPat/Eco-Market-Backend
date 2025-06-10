<?php
session_start();
require_once "../config/db.php";

// Check if user is logged in and is a market
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'market') {
    header("Location: ../index.php");
    exit;
}

$marketId = $_SESSION['user_id'];
$marketName = $_SESSION['name'];

// Get market products
$stmt = $db->prepare("SELECT * FROM products WHERE market_id = ? ORDER BY created_at DESC");
$stmt->execute([$marketId]);
$products = $stmt->fetchAll();

// Count expired products
$today = date('Y-m-d');
$expiredCount = 0;

foreach ($products as $product) {
    if ($product['expiration_date'] < $today) {
        $expiredCount++;
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Market Dashboard - Eco Market</title>
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
        .card {
            margin-bottom: 20px;
            transition: transform 0.3s;
        }
        .card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 20px rgba(0,0,0,0.1);
        }
        .expired {
            position: relative;
        }
        .expired::before {
            content: "EXPIRED";
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%) rotate(-45deg);
            color: #dc3545;
            font-weight: bold;
            font-size: 1.5rem;
            border: 2px solid #dc3545;
            padding: 5px 10px;
            z-index: 1;
        }
        .expired img {
            opacity: 0.7;
        }
        .product-image {
            height: 200px;
            object-fit: cover;
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
                        <a class="nav-link active" href="dashboard.php">Dashboard</a>
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
    
    <!-- Main Content -->
    <div class="container my-5">
        <div class="row mb-4">
            <div class="col-md-12">
                <div class="d-flex justify-content-between align-items-center">
                    <h2>Your Products</h2>
                    <a href="add_product.php" class="btn btn-success">
                        <i class="fas fa-plus"></i> Add New Product
                    </a>
                </div>
                <hr>
            </div>
        </div>
        
        <!-- Summary Cards -->
        <div class="row mb-4">
            <div class="col-md-4">
                <div class="card bg-success text-white">
                    <div class="card-body">
                        <h5 class="card-title">Total Products</h5>
                        <p class="card-text display-4"><?php echo count($products); ?></p>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card bg-danger text-white">
                    <div class="card-body">
                        <h5 class="card-title">Expired Products</h5>
                        <p class="card-text display-4"><?php echo $expiredCount; ?></p>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card bg-info text-white">
                    <div class="card-body">
                        <h5 class="card-title">Active Products</h5>
                        <p class="card-text display-4"><?php echo count($products) - $expiredCount; ?></p>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Products -->
        <div class="row">
            <?php if (count($products) > 0): ?>
                <?php foreach ($products as $product): ?>
                    <?php $isExpired = ($product['expiration_date'] < $today); ?>
                    <div class="col-md-3">
                        <div class="card h-100 <?php echo $isExpired ? 'expired' : ''; ?>">
                            <img src="<?php echo htmlspecialchars('../' . $product['image_path']); ?>" class="card-img-top product-image" alt="<?php echo htmlspecialchars($product['title']); ?>">
                            <div class="card-body">
                                <h5 class="card-title"><?php echo strip_tags($product['title'], '<b><i><u><strong><em>'); ?></h5>
                                <div class="d-flex justify-content-between">
                                    <div class="price-section">
                                        <p class="text-muted mb-1"><s>₺<?php echo htmlspecialchars($product['normal_price']); ?></s></p>
                                        <p class="text-success fw-bold">₺<?php echo htmlspecialchars($product['discounted_price']); ?></p>
                                    </div>
                                    <div class="stock-section">
                                        <p class="text-muted mb-1">Stock</p>
                                        <p class="fw-bold"><?php echo htmlspecialchars($product['stock']); ?></p>
                                    </div>
                                </div>
                                <p class="text-muted mb-1">Expires: <?php echo htmlspecialchars($product['expiration_date']); ?></p>
                            </div>
                            <div class="card-footer d-flex justify-content-between">
                                <a href="edit_product.php?id=<?php echo $product['product_id']; ?>" class="btn btn-sm btn-outline-primary">Edit</a>
                                <a href="delete_product.php?id=<?php echo $product['product_id']; ?>" class="btn btn-sm btn-outline-danger" onclick="return confirm('Are you sure you want to delete this product?')">Delete</a>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="col-12">
                    <div class="alert alert-info">
                        <p class="mb-0">You don't have any products yet. <a href="add_product.php">Add your first product</a>.</p>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html> 