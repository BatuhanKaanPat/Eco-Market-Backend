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

// Get cart items with product details
$stmt = $db->prepare("
    SELECT ci.*, p.title, p.normal_price, p.discounted_price, p.stock, p.image_path, p.expiration_date, m.name as market_name
    FROM cart_items ci
    JOIN products p ON ci.product_id = p.product_id
    JOIN markets m ON p.market_id = m.market_id
    WHERE ci.consumer_id = ?
    ORDER BY ci.added_at DESC
");
$stmt->execute([$consumerId]);
$cartItems = $stmt->fetchAll();

// Calculate total
$total = 0;
$today = date('Y-m-d');
$hasExpiredItems = false;

foreach ($cartItems as $item) {
    if ($item['expiration_date'] >= $today) {
        $total += $item['discounted_price'] * $item['quantity'];
    } else {
        $hasExpiredItems = true;
    }
}

// Handle status messages
$status = isset($_GET['status']) ? $_GET['status'] : '';
$message = isset($_GET['message']) ? $_GET['message'] : '';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Shopping Cart - Eco Market</title>
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
        .cart-container {
            background-color: #fff;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 0 15px rgba(0, 0, 0, 0.1);
            margin-bottom: 30px;
        }
        .product-image {
            width: 80px;
            height: 80px;
            object-fit: cover;
            border-radius: 5px;
        }
        .quantity-control {
            display: flex;
            align-items: center;
        }
        .quantity-control button {
            width: 30px;
            height: 30px;
            padding: 0;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .quantity-control input {
            width: 50px;
            text-align: center;
            margin: 0 5px;
        }
        .expired-item {
            background-color: #f8d7da;
        }
        .expired-item td {
            text-decoration: line-through;
            color: #842029;
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
                        <a class="nav-link" href="dashboard.php">Home</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link active" href="cart.php">
                            <i class="fas fa-shopping-cart"></i> Cart
                            <span class="badge bg-danger"><?php echo count($cartItems); ?></span>
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
        <div class="row">
            <div class="col-lg-8">
                <div class="cart-container">
                    <h2 class="mb-4">Shopping Cart</h2>
                    
                    <!-- Status Messages -->
                    <?php if (!empty($status)): ?>
                        <div class="alert alert-<?php echo $status === 'added' || $status === 'updated' ? 'success' : 'danger'; ?> alert-dismissible fade show">
                            <?php echo htmlspecialchars($message); ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                        </div>
                    <?php endif; ?>
                    
                    <?php if ($hasExpiredItems): ?>
                        <div class="alert alert-warning">
                            <i class="fas fa-exclamation-triangle me-2"></i>
                            Some items in your cart have expired and will be removed when you purchase.
                        </div>
                    <?php endif; ?>
                    
                    <?php if (count($cartItems) > 0): ?>
                        <div class="table-responsive">
                            <table class="table table-hover" id="cart-table">
                                <thead>
                                    <tr>
                                        <th>Product</th>
                                        <th>Price</th>
                                        <th>Quantity</th>
                                        <th>Subtotal</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($cartItems as $item): ?>
                                        <?php $isExpired = ($item['expiration_date'] < $today); ?>
                                        <tr id="cart-item-<?php echo $item['cart_id']; ?>" class="<?php echo $isExpired ? 'expired-item' : ''; ?>">
                                            <td class="d-flex align-items-center">
                                                <img src="<?php echo htmlspecialchars('../' . $item['image_path']); ?>" alt="<?php echo htmlspecialchars($item['title']); ?>" class="product-image me-3">
                                                <div>
                                                    <h6 class="mb-0"><?php echo htmlspecialchars($item['title']); ?></h6>
                                                    <small class="text-muted">From: <?php echo htmlspecialchars($item['market_name']); ?></small>
                                                    <?php if ($isExpired): ?>
                                                        <div class="text-danger"><small>Expired</small></div>
                                                    <?php else: ?>
                                                        <small class="text-muted">Expires: <?php echo date('M d, Y', strtotime($item['expiration_date'])); ?></small>
                                                    <?php endif; ?>
                                                </div>
                                            </td>
                                            <td>₺<?php echo htmlspecialchars($item['discounted_price']); ?></td>
                                            <td>
                                                <div class="quantity-control">
                                                    <button class="btn btn-sm btn-outline-secondary decrement-btn" data-cart-id="<?php echo $item['cart_id']; ?>" <?php echo $isExpired ? 'disabled' : ''; ?>>
                                                        <i class="fas fa-minus"></i>
                                                    </button>
                                                    <input type="number" class="form-control quantity-input" value="<?php echo $item['quantity']; ?>" min="1" max="<?php echo $item['stock']; ?>" data-cart-id="<?php echo $item['cart_id']; ?>" data-product-id="<?php echo $item['product_id']; ?>" <?php echo $isExpired ? 'disabled' : ''; ?>>
                                                    <button class="btn btn-sm btn-outline-secondary increment-btn" data-cart-id="<?php echo $item['cart_id']; ?>" <?php echo $isExpired ? 'disabled' : ''; ?>>
                                                        <i class="fas fa-plus"></i>
                                                    </button>
                                                </div>
                                            </td>
                                            <td class="item-subtotal">
                                                <?php if (!$isExpired): ?>
                                                    ₺<?php echo number_format($item['discounted_price'] * $item['quantity'], 2); ?>
                                                <?php else: ?>
                                                    <span class="text-danger">Expired</span>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <button class="btn btn-sm btn-outline-danger remove-btn" data-cart-id="<?php echo $item['cart_id']; ?>">
                                                    <i class="fas fa-trash"></i>
                                                </button>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php else: ?>
                        <div class="text-center py-5">
                            <i class="fas fa-shopping-cart fa-4x text-muted mb-3"></i>
                            <h4>Your cart is empty</h4>
                            <p class="text-muted">Looks like you haven't added any products to your cart yet.</p>
                            <a href="dashboard.php" class="btn btn-success mt-3">Browse Products</a>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
            
            <div class="col-lg-4">
                <div class="cart-container">
                    <h4 class="mb-4">Order Summary</h4>
                    <div class="d-flex justify-content-between mb-2">
                        <span>Subtotal:</span>
                        <span id="cart-subtotal">₺<?php echo number_format($total, 2); ?></span>
                    </div>
                    <hr>
                    <div class="d-flex justify-content-between mb-3">
                        <strong>Total:</strong>
                        <strong id="cart-total">₺<?php echo number_format($total, 2); ?></strong>
                    </div>
                    
                    <?php if (count($cartItems) > 0): ?>
                        <button type="button" class="btn btn-success w-100 mb-2" id="purchase-btn" <?php echo $total <= 0 ? 'disabled' : ''; ?>>
                            Purchase
                        </button>
                        <p class="text-muted small text-center mt-2">
                            By clicking Purchase, you confirm that you want to buy these products.
                        </p>
                    <?php endif; ?>
                    
                    <div class="text-center mt-3">
                        <a href="dashboard.php" class="btn btn-outline-secondary">Continue Shopping</a>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script>
        $(document).ready(function() {
            // Set AJAX flag for backend
            <?php $_SESSION['ajax_request'] = true; ?>
            
            // Update quantity
            function updateQuantity(cartId, quantity) {
                $.ajax({
                    url: 'update_cart.php',
                    type: 'POST',
                    dataType: 'json',
                    data: {
                        cart_id: cartId,
                        quantity: quantity
                    },
                    success: function(response) {
                        if (response.success) {
                            // Update subtotal and total
                            updateCartTotals();
                        } else {
                            alert(response.message);
                        }
                    },
                    error: function() {
                        alert('An error occurred during the request');
                    }
                });
            }
            
            // Remove item from cart
            $('.remove-btn').click(function() {
                const cartId = $(this).data('cart-id');
                
                $.ajax({
                    url: 'remove_from_cart.php',
                    type: 'POST',
                    dataType: 'json',
                    data: {
                        cart_id: cartId
                    },
                    success: function(response) {
                        if (response.success) {
                            // Remove item from table
                            $('#cart-item-' + cartId).fadeOut(300, function() {
                                $(this).remove();
                                
                                // Update cart counts
                                const cartCount = $('#cart-table tbody tr').length;
                                $('.badge').text(cartCount);
                                
                                if (cartCount === 0) {
                                    // Show empty cart message
                                    $('#cart-table').replaceWith(`
                                        <div class="text-center py-5">
                                            <i class="fas fa-shopping-cart fa-4x text-muted mb-3"></i>
                                            <h4>Your cart is empty</h4>
                                            <p class="text-muted">Looks like you haven't added any products to your cart yet.</p>
                                            <a href="dashboard.php" class="btn btn-success mt-3">Browse Products</a>
                                        </div>
                                    `);
                                    
                                    // Disable purchase button
                                    $('#purchase-btn').prop('disabled', true);
                                }
                                
                                // Update totals
                                updateCartTotals();
                            });
                        } else {
                            alert(response.message);
                        }
                    },
                    error: function() {
                        alert('An error occurred during the request');
                    }
                });
            });
            
            // Increment quantity
            $('.increment-btn').click(function() {
                const cartId = $(this).data('cart-id');
                const inputField = $(this).siblings('.quantity-input');
                const currentQuantity = parseInt(inputField.val());
                const maxQuantity = parseInt(inputField.attr('max'));
                
                if (currentQuantity < maxQuantity) {
                    inputField.val(currentQuantity + 1);
                    updateQuantity(cartId, currentQuantity + 1);
                }
            });
            
            // Decrement quantity
            $('.decrement-btn').click(function() {
                const cartId = $(this).data('cart-id');
                const inputField = $(this).siblings('.quantity-input');
                const currentQuantity = parseInt(inputField.val());
                
                if (currentQuantity > 1) {
                    inputField.val(currentQuantity - 1);
                    updateQuantity(cartId, currentQuantity - 1);
                }
            });
            
            // Manual quantity change
            $('.quantity-input').change(function() {
                const cartId = $(this).data('cart-id');
                let quantity = parseInt($(this).val());
                const maxQuantity = parseInt($(this).attr('max'));
                
                // Ensure quantity is within limits
                if (isNaN(quantity) || quantity < 1) {
                    quantity = 1;
                    $(this).val(1);
                } else if (quantity > maxQuantity) {
                    quantity = maxQuantity;
                    $(this).val(maxQuantity);
                }
                
                updateQuantity(cartId, quantity);
            });
            
            // Update cart totals
            function updateCartTotals() {
                $.ajax({
                    url: 'get_cart_total.php',
                    type: 'GET',
                    dataType: 'json',
                    success: function(response) {
                        if (response.success) {
                            // Update subtotal and total
                            $('#cart-subtotal').text('₺' + response.subtotal);
                            $('#cart-total').text('₺' + response.total);
                            
                            // Enable/disable purchase button
                            $('#purchase-btn').prop('disabled', parseFloat(response.total) <= 0);
                            
                            // Update item subtotals
                            if (response.items) {
                                $.each(response.items, function(cartId, itemData) {
                                    if (!itemData.expired) {
                                        $('#cart-item-' + cartId + ' .item-subtotal').text('₺' + itemData.subtotal);
                                    }
                                });
                            }
                        }
                    }
                });
            }
            
            // Handle purchase button
            $('#purchase-btn').click(function() {
                const button = $(this);
                button.prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> Processing...');
                
                $.ajax({
                    url: 'process_purchase.php',
                    type: 'POST',
                    dataType: 'json',
                    success: function(response) {
                        if (response.success) {
                            // Replace cart content with success message
                            $('.cart-container:first').html(`
                                <div class="text-center py-5">
                                    <i class="fas fa-check-circle fa-4x text-success mb-3"></i>
                                    <h4>Purchase Successful!</h4>
                                    <p class="text-muted">Your cart has been emptied and the products have been purchased.</p>
                                    <a href="dashboard.php" class="btn btn-success mt-3">Continue Shopping</a>
                                </div>
                            `);
                            
                            // Update summary section
                            $('.cart-container:last').html(`
                                <h4 class="mb-4">Order Summary</h4>
                                <div class="d-flex justify-content-between mb-2">
                                    <span>Subtotal:</span>
                                    <span id="cart-subtotal">₺0.00</span>
                                </div>
                                <hr>
                                <div class="d-flex justify-content-between mb-3">
                                    <strong>Total:</strong>
                                    <strong id="cart-total">₺0.00</strong>
                                </div>
                                <div class="text-center mt-3">
                                    <a href="dashboard.php" class="btn btn-outline-secondary">Continue Shopping</a>
                                </div>
                            `);
                            
                            // Update cart count in navbar
                            $('.nav-link .badge').text('0');
                        } else {
                            alert(response.message || 'An error occurred during purchase.');
                            button.prop('disabled', false).html('Purchase');
                        }
                    },
                    error: function() {
                        alert('An error occurred. Please try again.');
                        button.prop('disabled', false).html('Purchase');
                    }
                });
            });
        });
    </script>
</body>
</html> 