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

// Check if product ID is provided
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header("Location: dashboard.php?status=error&message=" . urlencode("Invalid product ID"));
    exit;
}

$productId = $_GET['id'];
$errors = [];
$values = [
    'title' => '',
    'stock' => '',
    'normal_price' => '',
    'discounted_price' => '',
    'expiration_date' => '',
    'current_image' => ''
];

// Get product details
try {
    $stmt = $db->prepare("SELECT * FROM products WHERE product_id = ? AND market_id = ?");
    $stmt->execute([$productId, $marketId]);
    $product = $stmt->fetch();
    
    if (!$product) {
        header("Location: dashboard.php?status=error&message=" . urlencode("Product not found or you don't have permission"));
        exit;
    }
    
    // Set form values
    $values['title'] = $product['title'];
    $values['stock'] = $product['stock'];
    $values['normal_price'] = $product['normal_price'];
    $values['discounted_price'] = $product['discounted_price'];
    $values['expiration_date'] = $product['expiration_date'];
    $values['current_image'] = $product['image_path'];
    
} catch (PDOException $e) {
    header("Location: dashboard.php?status=error&message=" . urlencode("Database error"));
    exit;
}

// Process form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['edit_product'])) {
    // Get and sanitize input
    $title = htmlspecialchars(trim($_POST['title']));
    $stock = filter_input(INPUT_POST, 'stock', FILTER_SANITIZE_NUMBER_INT);
    $normalPrice = filter_input(INPUT_POST, 'normal_price', FILTER_SANITIZE_NUMBER_FLOAT, FILTER_FLAG_ALLOW_FRACTION);
    $discountedPrice = filter_input(INPUT_POST, 'discounted_price', FILTER_SANITIZE_NUMBER_FLOAT, FILTER_FLAG_ALLOW_FRACTION);
    $expirationDate = htmlspecialchars(trim($_POST['expiration_date']));
    
    // Sticky form
    $values['title'] = $title;
    $values['stock'] = $stock;
    $values['normal_price'] = $normalPrice;
    $values['discounted_price'] = $discountedPrice;
    $values['expiration_date'] = $expirationDate;
    
    // Validate fields
    if (empty($title)) {
        $errors[] = "Product title is required";
    }
    
    if (empty($stock) || !is_numeric($stock) || $stock < 0) {
        $errors[] = "Valid stock quantity is required";
    }
    
    if (empty($normalPrice) || !is_numeric($normalPrice) || $normalPrice <= 0) {
        $errors[] = "Valid normal price is required";
    }
    
    if (empty($discountedPrice) || !is_numeric($discountedPrice) || $discountedPrice <= 0) {
        $errors[] = "Valid discounted price is required";
    }
    
    if ($discountedPrice >= $normalPrice) {
        $errors[] = "Discounted price must be less than normal price";
    }
    
    if (empty($expirationDate)) {
        $errors[] = "Expiration date is required";
    }
    
    // Process image if provided
    $uploadPath = $values['current_image']; // Default to current image
    
    if (!empty($_FILES['product_image']['name'])) {
        $uploadDir = "../uploads/products/";
        
        // Create directory if it doesn't exist
        if (!file_exists($uploadDir)) {
            mkdir($uploadDir, 0777, true);
        }
        
        $fileExtension = strtolower(pathinfo($_FILES['product_image']['name'], PATHINFO_EXTENSION));
        $allowedExtensions = ['jpg', 'jpeg', 'png', 'gif'];
        
        if (!in_array($fileExtension, $allowedExtensions)) {
            $errors[] = "Only JPG, JPEG, PNG, and GIF files are allowed";
        } elseif ($_FILES['product_image']['size'] > 5000000) { // 5MB max
            $errors[] = "File size must be less than 5MB";
        } else {
            $fileName = uniqid() . '.' . $fileExtension;
            $targetFile = $uploadDir . $fileName;
            $uploadPath = "uploads/products/" . $fileName;
            
            // Upload new image
            if (!move_uploaded_file($_FILES['product_image']['tmp_name'], $targetFile)) {
                $errors[] = "Failed to upload image";
            } else {
                // Remove old image if exists and different from default
                $oldImagePath = "../" . $values['current_image'];
                if (file_exists($oldImagePath) && $values['current_image'] != "uploads/products/default.jpg") {
                    @unlink($oldImagePath);
                }
            }
        }
    }
    
    // Update product if no errors
    if (empty($errors)) {
        try {
            $stmt = $db->prepare("UPDATE products SET title = ?, stock = ?, normal_price = ?, discounted_price = ?, expiration_date = ?, image_path = ? WHERE product_id = ?");
            $stmt->execute([$title, $stock, $normalPrice, $discountedPrice, $expirationDate, $uploadPath, $productId]);
            
            // Redirect to dashboard
            header("Location: dashboard.php?status=updated");
            exit;
        } catch (PDOException $e) {
            $errors[] = "Failed to update product: " . $e->getMessage();
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Product - Eco Market</title>
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
        .form-container {
            background-color: #fff;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 0 15px rgba(0, 0, 0, 0.1);
        }
        .current-image {
            max-width: 150px;
            max-height: 150px;
            margin-bottom: 15px;
            border-radius: 5px;
        }
        .preview-image {
            max-width: 100%;
            max-height: 200px;
            display: none;
            margin-top: 10px;
            border-radius: 5px;
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
    
    <!-- Main Content -->
    <div class="container my-5">
        <div class="row">
            <div class="col-lg-8 offset-lg-2">
                <div class="form-container">
                    <h2 class="mb-4">Edit Product</h2>
                    
                    <?php if (!empty($errors)): ?>
                        <div class="alert alert-danger">
                            <ul class="mb-0">
                                <?php foreach ($errors as $error): ?>
                                    <li><?php echo $error; ?></li>
                                <?php endforeach; ?>
                            </ul>
                        </div>
                    <?php endif; ?>
                    
                    <form method="post" action="<?php echo htmlspecialchars($_SERVER['PHP_SELF'] . '?id=' . $productId); ?>" enctype="multipart/form-data">
                        <div class="mb-3">
                            <label for="title" class="form-label">Product Title</label>
                            <input type="text" class="form-control" id="title" name="title" value="<?php echo $values['title']; ?>" required>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-4 mb-3">
                                <label for="stock" class="form-label">Stock Quantity</label>
                                <input type="number" class="form-control" id="stock" name="stock" min="0" value="<?php echo $values['stock']; ?>" required>
                            </div>
                            <div class="col-md-4 mb-3">
                                <label for="normal_price" class="form-label">Normal Price (₺)</label>
                                <input type="number" class="form-control" id="normal_price" name="normal_price" min="0.01" step="0.01" value="<?php echo $values['normal_price']; ?>" required>
                            </div>
                            <div class="col-md-4 mb-3">
                                <label for="discounted_price" class="form-label">Discounted Price (₺)</label>
                                <input type="number" class="form-control" id="discounted_price" name="discounted_price" min="0.01" step="0.01" value="<?php echo $values['discounted_price']; ?>" required>
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label for="expiration_date" class="form-label">Expiration Date</label>
                            <input type="date" class="form-control" id="expiration_date" name="expiration_date" value="<?php echo $values['expiration_date']; ?>" required>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Current Image</label>
                            <div>
                                <img src="<?php echo htmlspecialchars('../' . $values['current_image']); ?>" class="current-image" alt="Current product image">
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label for="product_image" class="form-label">New Product Image (optional)</label>
                            <input type="file" class="form-control" id="product_image" name="product_image" accept="image/*">
                            <small class="text-muted">Max file size: 5MB. Supported formats: JPG, JPEG, PNG, GIF</small>
                            <img id="preview" class="preview-image" alt="Preview">
                        </div>
                        
                        <div class="d-grid gap-2 d-md-flex justify-content-md-end">
                            <a href="dashboard.php" class="btn btn-outline-secondary me-md-2">Cancel</a>
                            <button type="submit" name="edit_product" class="btn btn-success">Update Product</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Show image preview
        document.getElementById('product_image').addEventListener('change', function(e) {
            const preview = document.getElementById('preview');
            const file = e.target.files[0];
            
            if (file) {
                const reader = new FileReader();
                
                reader.onload = function(e) {
                    preview.src = e.target.result;
                    preview.style.display = 'block';
                }
                
                reader.readAsDataURL(file);
            } else {
                preview.style.display = 'none';
            }
        });
    </script>
</body>
</html> 