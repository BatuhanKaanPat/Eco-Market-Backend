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
$errors = [];
$success = false;

// Get market details
try {
    $stmt = $db->prepare("SELECT * FROM markets WHERE market_id = ?");
    $stmt->execute([$marketId]);
    $market = $stmt->fetch();
    
    if (!$market) {
        header("Location: ../logout.php");
        exit;
    }
} catch (PDOException $e) {
    $errors[] = "Failed to load market profile: " . $e->getMessage();
}

// Process profile update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_profile'])) {
    $name = htmlspecialchars(trim($_POST['name']));
    $city = htmlspecialchars(trim($_POST['city']));
    $district = htmlspecialchars(trim($_POST['district']));
    $currentPassword = $_POST['current_password'];
    $newPassword = $_POST['new_password'];
    $confirmPassword = $_POST['confirm_password'];
    
    // Validate required fields
    if (empty($name)) {
        $errors[] = "Market name is required";
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
        } else if (!password_verify($currentPassword, $market['password'])) {
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
                $stmt = $db->prepare("UPDATE markets SET name = ?, city = ?, district = ?, password = ? WHERE market_id = ?");
                $stmt->execute([$name, $city, $district, $hashedPassword, $marketId]);
            } else {
                $stmt = $db->prepare("UPDATE markets SET name = ?, city = ?, district = ? WHERE market_id = ?");
                $stmt->execute([$name, $city, $district, $marketId]);
            }
            
            // Update session data
            $_SESSION['name'] = $name;
            
            $success = true;
            
            // Refresh market data
            $stmt = $db->prepare("SELECT * FROM markets WHERE market_id = ?");
            $stmt->execute([$marketId]);
            $market = $stmt->fetch();
        } catch (PDOException $e) {
            $errors[] = "Failed to update profile: " . $e->getMessage();
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Market Profile - Eco Market</title>
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
                        <a class="nav-link active" href="profile.php">Profile</a>
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
                <div class="profile-container">
                    <div class="profile-header">
                        <i class="fas fa-store profile-icon"></i>
                        <h2>Market Profile</h2>
                        <p class="text-muted">Update your market information</p>
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
                            <input type="email" class="form-control" id="email" value="<?php echo htmlspecialchars($market['email']); ?>" readonly>
                            <div class="form-text">Email cannot be changed.</div>
                        </div>
                        
                        <div class="mb-3">
                            <label for="name" class="form-label">Market Name</label>
                            <input type="text" class="form-control" id="name" name="name" value="<?php echo htmlspecialchars($market['name']); ?>" required>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="city" class="form-label">City</label>
                                <input type="text" class="form-control" id="city" name="city" value="<?php echo htmlspecialchars($market['city']); ?>" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="district" class="form-label">District</label>
                                <input type="text" class="form-control" id="district" name="district" value="<?php echo htmlspecialchars($market['district']); ?>" required>
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