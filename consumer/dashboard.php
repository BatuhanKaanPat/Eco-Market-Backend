<?php
session_start();
require_once "../config/db.php";

// Check if user is logged in and is a consumer
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'consumer') {
    header("Location: ../index.php");
    exit;
}

$consumerId = $_SESSION['user_id'];
$consumerName = $_SESSION['name'];
$consumerCity = "";
$consumerDistrict = "";

// Get consumer info for location-based filtering
$stmt = $db->prepare("SELECT city, district FROM consumers WHERE consumer_id = ?");
$stmt->execute([$consumerId]);
$consumerInfo = $stmt->fetch();

if ($consumerInfo) {
    $consumerCity = $consumerInfo['city'];
    $consumerDistrict = $consumerInfo['district'];
}

// Get products with search
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$page = isset($_GET['page']) && is_numeric($_GET['page']) ? (int)$_GET['page'] : 1;
$limit = 4; // Products per page
$offset = ($page - 1) * $limit;
$today = date('Y-m-d');
$products = [];
$totalProducts = 0;

if (!empty($search)) {
    // Products with search term, not expired, in same city, with preference to same district
    $stmt = $db->prepare("
        SELECT p.*, m.name as market_name, m.city, m.district 
        FROM products p
        JOIN markets m ON p.market_id = m.market_id
        WHERE p.title LIKE ? 
        AND p.expiration_date >= ? 
        AND m.city = ?
        ORDER BY 
            CASE WHEN m.district = ? THEN 0 ELSE 1 END,
            p.created_at DESC
        LIMIT ?, ?
    ");
    $stmt->bindValue(1, '%' . $search . '%');
    $stmt->bindValue(2, $today);
    $stmt->bindValue(3, $consumerCity);
    $stmt->bindValue(4, $consumerDistrict);
    $stmt->bindValue(5, $offset, PDO::PARAM_INT);
    $stmt->bindValue(6, $limit, PDO::PARAM_INT);
    $stmt->execute();
    $products = $stmt->fetchAll();
    
    // Get total count for pagination
    $stmt = $db->prepare("
        SELECT COUNT(*) as total
        FROM products p
        JOIN markets m ON p.market_id = m.market_id
        WHERE p.title LIKE ? 
        AND p.expiration_date >= ? 
        AND m.city = ?
    ");
    $stmt->execute(['%' . $search . '%', $today, $consumerCity]);
    $result = $stmt->fetch();
    $totalProducts = $result['total'];
} else {
    // Recent products, not expired, in same city, with preference to same district
    $stmt = $db->prepare("
        SELECT p.*, m.name as market_name, m.city, m.district 
        FROM products p
        JOIN markets m ON p.market_id = m.market_id
        WHERE p.expiration_date >= ? 
        AND m.city = ?
        ORDER BY 
            CASE WHEN m.district = ? THEN 0 ELSE 1 END,
            p.created_at DESC
        LIMIT ?, ?
    ");
    $stmt->bindValue(1, $today);
    $stmt->bindValue(2, $consumerCity);
    $stmt->bindValue(3, $consumerDistrict);
    $stmt->bindValue(4, $offset, PDO::PARAM_INT);
    $stmt->bindValue(5, $limit, PDO::PARAM_INT);
    $stmt->execute();
    $products = $stmt->fetchAll();
    
    // Get total count for pagination
    $stmt = $db->prepare("
        SELECT COUNT(*) as total
        FROM products p
        JOIN markets m ON p.market_id = m.market_id
        WHERE p.expiration_date >= ? 
        AND m.city = ?
    ");
    $stmt->execute([$today, $consumerCity]);
    $result = $stmt->fetch();
    $totalProducts = $result['total'];
}

// Calculate pagination
$totalPages = ceil($totalProducts / $limit);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Consumer Dashboard - Eco Market</title>
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
        .search-container {
            background-color: #fff;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 0 15px rgba(0, 0, 0, 0.1);
            margin-bottom: 30px;
        }
        .product-card {
            margin-bottom: 20px;
            transition: transform 0.3s;
            height: 100%;
        }
        .product-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 20px rgba(0,0,0,0.1);
        }
        .product-image {
            height: 200px;
            object-fit: cover;
        }
        .discount-badge {
            position: absolute;
            top: 10px;
            right: 10px;
            background-color: #dc3545;
            color: white;
            padding: 5px 10px;
            border-radius: 20px;
            font-weight: bold;
        }
        .pagination-container {
            margin-top: 30px;
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
                        <a class="nav-link active" href="dashboard.php">Home</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="cart.php">
                            <i class="fas fa-shopping-cart"></i> Cart
                            <?php
                            // Get cart count
                            $stmt = $db->prepare("SELECT COUNT(*) as count FROM cart_items WHERE consumer_id = ?");
                            $stmt->execute([$consumerId]);
                            $cartCount = $stmt->fetch()['count'];
                            
                            if ($cartCount > 0) {
                                echo "<span class='badge bg-danger'>$cartCount</span>";
                            }
                            ?>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="profile.php">Profile</a>
                    </li>
                </ul>
                <span class="navbar-text me-3">
                    Hello, <?php echo htmlspecialchars($consumerName); ?>
                </span>
                <a href="../logout.php" class="btn btn-outline-danger">Logout</a>
            </div>
        </div>
    </nav>
    
    <!-- Main Content -->
    <div class="container my-5">
        <!-- Search Form -->
        <div class="search-container">
            <form method="get" action="<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>" class="row g-3">
                <div class="col-md-10">
                    <input type="text" class="form-control form-control-lg" name="search" placeholder="Search for products..." value="<?php echo htmlspecialchars($search); ?>">
                </div>
                <div class="col-md-2">
                    <button type="submit" class="btn btn-success btn-lg w-100">Search</button>
                </div>
            </form>
        </div>
        
        <!-- Results -->
        <div class="row">
            <div class="col-md-12">
                <h2><?php echo empty($search) ? "Products Near You" : "Search Results"; ?></h2>
                <p>Showing products in <?php echo htmlspecialchars($consumerCity); ?></p>
                <hr>
            </div>
        </div>
        
        <div class="row">
            <?php if (count($products) > 0): ?>
                <?php foreach ($products as $product): ?>
                    <?php 
                    // Calculate discount percentage
                    $discountPercent = round(($product['normal_price'] - $product['discounted_price']) / $product['normal_price'] * 100);
                    ?>
                    <div class="col-md-3">
                        <div class="card product-card">
                            <div class="discount-badge"><?php echo $discountPercent; ?>% OFF</div>
                            <img src="<?php echo htmlspecialchars('../' . $product['image_path']); ?>" class="card-img-top product-image" alt="<?php echo htmlspecialchars($product['title']); ?>">
                            <div class="card-body">
                                <h5 class="card-title"><?php echo htmlspecialchars($product['title']); ?></h5>
                                <p class="text-muted mb-1">
                                    <i class="fas fa-store me-1"></i> <?php echo htmlspecialchars($product['market_name']); ?>
                                </p>
                                <p class="text-muted mb-1">
                                    <i class="fas fa-map-marker-alt me-1"></i> 
                                    <?php echo htmlspecialchars($product['district']); ?>, <?php echo htmlspecialchars($product['city']); ?>
                                </p>
                                <div class="d-flex justify-content-between align-items-center mt-2">
                                    <div class="price-section">
                                        <p class="text-muted mb-0"><s>₺<?php echo htmlspecialchars($product['normal_price']); ?></s></p>
                                        <p class="text-success fw-bold">₺<?php echo htmlspecialchars($product['discounted_price']); ?></p>
                                    </div>
                                    <button class="btn btn-sm btn-outline-success add-to-cart-btn" data-product-id="<?php echo $product['product_id']; ?>">
                                        <i class="fas fa-cart-plus"></i> Add to Cart
                                    </button>
                                </div>
                            </div>
                            <div class="card-footer text-muted">
                                <small>Expires on: <?php echo date('M d, Y', strtotime($product['expiration_date'])); ?></small>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="col-12">
                    <div class="alert alert-info">
                        <?php if (!empty($search)): ?>
                            <p class="mb-0">No products found matching "<?php echo htmlspecialchars($search); ?>". Try a different search term.</p>
                        <?php else: ?>
                            <p class="mb-0">No products available in your area at this time. Please check back later.</p>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endif; ?>
        </div>
        
        <!-- Pagination -->
        <?php if ($totalPages > 1): ?>
            <div class="row">
                <div class="col-md-12">
                    <nav aria-label="Page navigation" class="pagination-container">
                        <ul class="pagination justify-content-center">
                            <?php if ($page > 1): ?>
                                <li class="page-item">
                                    <a class="page-link" href="?search=<?php echo urlencode($search); ?>&page=<?php echo $page - 1; ?>" aria-label="Previous">
                                        <span aria-hidden="true">&laquo;</span>
                                    </a>
                                </li>
                            <?php endif; ?>
                            
                            <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                                <li class="page-item <?php echo $i === $page ? 'active' : ''; ?>">
                                    <a class="page-link" href="?search=<?php echo urlencode($search); ?>&page=<?php echo $i; ?>"><?php echo $i; ?></a>
                                </li>
                            <?php endfor; ?>
                            
                            <?php if ($page < $totalPages): ?>
                                <li class="page-item">
                                    <a class="page-link" href="?search=<?php echo urlencode($search); ?>&page=<?php echo $page + 1; ?>" aria-label="Next">
                                        <span aria-hidden="true">&raquo;</span>
                                    </a>
                                </li>
                            <?php endif; ?>
                        </ul>
                    </nav>
                </div>
            </div>
        <?php endif; ?>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script>
        $(document).ready(function() {
            // Set AJAX flag for backend
            <?php $_SESSION['ajax_request'] = true; ?>
            
            // Add to cart with AJAX
            $('.add-to-cart-btn').click(function() {
                const productId = $(this).data('product-id');
                const button = $(this);
                
                // Disable button to prevent multiple clicks
                button.prop('disabled', true);
                
                $.ajax({
                    url: 'add_to_cart.php',
                    type: 'POST',
                    dataType: 'json',
                    data: {
                        product_id: productId,
                        quantity: 1
                    },
                    success: function(response) {
                        if (response.success) {
                            // Show success feedback
                            button.removeClass('btn-outline-success').addClass('btn-success');
                            button.html('<i class="fas fa-check"></i> Added');
                            
                            // Update cart count in navbar
                            let cartCountElement = $('.nav-link .badge');
                            if (cartCountElement.length > 0) {
                                let currentCount = parseInt(cartCountElement.text()) || 0;
                                cartCountElement.text(currentCount + 1);
                            } else {
                                $('.nav-link:contains("Cart")').append('<span class="badge bg-danger">1</span>');
                            }
                            
                            // Reset button after 2 seconds
                            setTimeout(function() {
                                button.removeClass('btn-success').addClass('btn-outline-success');
                                button.html('<i class="fas fa-cart-plus"></i> Add to Cart');
                                button.prop('disabled', false);
                            }, 2000);
                        } else {
                            // Show error
                            alert(response.message);
                            button.prop('disabled', false);
                        }
                    },
                    error: function() {
                        alert('An error occurred. Please try again.');
                        button.prop('disabled', false);
                    }
                });
            });
        });
    </script>
</body>
</html> 