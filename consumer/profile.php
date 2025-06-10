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
$errors = [];
$success = false;

// Get consumer details
try {
    $stmt = $db->prepare("SELECT * FROM consumers WHERE consumer_id = ?");
    $stmt->execute([$consumerId]);
    $consumer = $stmt->fetch();
    
    if (!$consumer) {
        header("Location: ../logout.php");
        exit;
    }
} catch (PDOException $e) {
    $errors[] = "Failed to load consumer profile: " . $e->getMessage();
}

// Process profile update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_profile'])) {
    $fullname = htmlspecialchars(trim($_POST['fullname']));
    $city = htmlspecialchars(trim($_POST['city']));
    $district = htmlspecialchars(trim($_POST['district']));
    $currentPassword = $_POST['current_password'];
    $newPassword = $_POST['new_password'];
    $confirmPassword = $_POST['confirm_password'];
    
    // Validate required fields
    if (empty($fullname)) {
        $errors[] = "Full name is required";
    }
    
    if (empty($city)) {
        $errors[] = "City is required";
    }
    
    if (empty($district)) {
        $errors[] = "District is required";
    }
    
    // Check if password should be updated
    $updatePassword = false;
    if (!empty($currentPassword) || !empty($newPassword) || !empty($confirmPassword)) {
        if (empty($currentPassword)) {
            $errors[] = "Current password is required to change password";
        } else if (!password_verify($currentPassword, $consumer['password'])) {
            $errors[] = "Current password is incorrect";
        }
        
        if (empty($newPassword)) {
            $errors[] = "New password is required";
        } else if (strlen($newPassword) < 6) {
            $errors[] = "New password must be at least 6 characters";
        }
        
        if ($newPassword !== $confirmPassword) {
            $errors[] = "New passwords do not match";
        }
        
        $updatePassword = empty($errors);
    }
    
    // Update profile if no errors
    if (empty($errors)) {
        try {
            if ($updatePassword) {
                $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);
                $stmt = $db->prepare("UPDATE consumers SET fullname = ?, city = ?, district = ?, password = ? WHERE consumer_id = ?");
                $stmt->execute([$fullname, $city, $district, $hashedPassword, $consumerId]);
            } else {
                $stmt = $db->prepare("UPDATE consumers SET fullname = ?, city = ?, district = ? WHERE consumer_id = ?");
                $stmt->execute([$fullname, $city, $district, $consumerId]);
            }
            
            // Update session data
            $_SESSION['name'] = $fullname;
            
            $success = true;
            
            // Refresh consumer data
            $stmt = $db->prepare("SELECT * FROM consumers WHERE consumer_id = ?");
            $stmt->execute([$consumerId]);
            $consumer = $stmt->fetch();
        } catch (PDOException $e) {
            $errors[] = "Failed to update profile: " . $e->getMessage();
        }
    }
}

// Get cart statistics
try {
    // Get active cart items count
    $today = date('Y-m-d');
    $stmt = $db->prepare("
        SELECT COUNT(*) as active_items
        FROM cart_items ci
        JOIN products p ON ci.product_id = p.product_id
        WHERE ci.consumer_id = ? AND p.expiration_date >= ?
    ");
    $stmt->execute([$consumerId, $today]);
    $activeItems = $stmt->fetch()['active_items'];
    
    // Get total cart value
    $stmt = $db->prepare("
        SELECT SUM(p.discounted_price * ci.quantity) as total_value
        FROM cart_items ci
        JOIN products p ON ci.product_id = p.product_id
        WHERE ci.consumer_id = ? AND p.expiration_date >= ?
    ");
    $stmt->execute([$consumerId, $today]);
    $cartValue = $stmt->fetch()['total_value'] ?: 0;
} catch (PDOException $e) {
    // Ignore cart statistics errors
    $activeItems = 0;
    $cartValue = 0;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Consumer Profile - Eco Market</title>
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
        .profile-container {
            background-color: #fff;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 0 15px rgba(0, 0, 0, 0.1);
        }
        .profile-header {
            text-align: center;
            margin-bottom: 30px;
        }
        .profile-icon {
            font-size: 80px;
            color: #2e7d32;
            margin-bottom: 20px;
        }
        .stats-card {
            padding: 20px;
            margin-bottom: 30px;
            border-radius: 10px;
            background-color: #f8f9fa;
            text-align: center;
        }
        .stats-icon {
            font-size: 40px;
            margin-bottom: 15px;
            color: #2e7d32;
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
                        <a class="nav-link" href="cart.php">
                            <i class="fas fa-shopping-cart"></i> Cart
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link active" href="profile.php">Profile</a>
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
            <!-- Stats Cards -->
            <div class="col-md-4">
                <div class="stats-card">
                    <i class="fas fa-shopping-cart stats-icon"></i>
                    <h3><?php echo $activeItems; ?></h3>
                    <p class="text-muted mb-0">Items in Cart</p>
                </div>
            </div>
            <div class="col-md-4">
                <div class="stats-card">
                    <i class="fas fa-tags stats-icon"></i>
                    <h3>₺<?php echo number_format($cartValue, 2); ?></h3>
                    <p class="text-muted mb-0">Cart Value</p>
                </div>
            </div>
            <div class="col-md-4">
                <div class="stats-card">
                    <i class="fas fa-map-marker-alt stats-icon"></i>
                    <h3><?php echo htmlspecialchars($consumer['city']); ?></h3>
                    <p class="text-muted mb-0">Your Location</p>
                </div>
            </div>
        </div>
        
        <div class="row mt-4">
            <div class="col-lg-8 offset-lg-2">
                <div class="profile-container">
                    <div class="profile-header">
                        <i class="fas fa-user-circle profile-icon"></i>
                        <h2>Your Profile</h2>
                        <p class="text-muted">Update your personal information</p>
                    </div>
                    
                    <?php if ($success): ?>
                        <div class="alert alert-success alert-dismissible fade show">
                            <strong>Success!</strong> Your profile has been updated.
                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                        </div>
                    <?php endif; ?>
                    
                    <?php if (!empty($errors)): ?>
                        <div class="alert alert-danger">
                            <ul class="mb-0">
                                <?php foreach ($errors as $error): ?>
                                    <li><?php echo $error; ?></li>
                                <?php endforeach; ?>
                            </ul>
                        </div>
                    <?php endif; ?>
                    
                    <form method="post" action="<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>">
                        <div class="mb-3">
                            <label for="email" class="form-label">Email address</label>
                            <input type="email" class="form-control" id="email" value="<?php echo htmlspecialchars($consumer['email']); ?>" readonly>
                            <div class="form-text">Email cannot be changed.</div>
                        </div>
                        
                        <div class="mb-3">
                            <label for="fullname" class="form-label">Full Name</label>
                            <input type="text" class="form-control" id="fullname" name="fullname" value="<?php echo htmlspecialchars($consumer['fullname']); ?>" required>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="city" class="form-label">City</label>
                                <input type="text" class="form-control" id="city" name="city" value="<?php echo htmlspecialchars($consumer['city']); ?>" required>
                                <div class="form-text">Changing your city will affect product availability.</div>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="district" class="form-label">District</label>
                                <input type="text" class="form-control" id="district" name="district" value="<?php echo htmlspecialchars($consumer['district']); ?>" required>
                            </div>
                        </div>
                        
                        <hr class="my-4">
                        <h5>Change Password (Optional)</h5>
                        
                        <div class="mb-3">
                            <label for="current_password" class="form-label">Current Password</label>
                            <input type="password" class="form-control" id="current_password" name="current_password">
                        </div>
                        
                        <div class="mb-3">
                            <label for="new_password" class="form-label">New Password</label>
                            <input type="password" class="form-control" id="new_password" name="new_password">
                            <div class="form-text">Password must be at least 6 characters long.</div>
                        </div>
                        
                        <div class="mb-3">
                            <label for="confirm_password" class="form-label">Confirm New Password</label>
                            <input type="password" class="form-control" id="confirm_password" name="confirm_password">
                        </div>
                        
                        <div class="d-grid gap-2 d-md-flex justify-content-md-end">
                            <a href="dashboard.php" class="btn btn-outline-secondary me-md-2">Cancel</a>
                            <button type="submit" name="update_profile" class="btn btn-success">Update Profile</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html> 