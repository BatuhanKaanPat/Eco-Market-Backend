<?php
session_start();
require_once "config/db.php";

// Check if already logged in
if (isset($_SESSION['user_id'])) {
    $userType = $_SESSION['user_type'];
    header("Location: " . ($userType === 'market' ? 'market/dashboard.php' : 'consumer/dashboard.php'));
    exit;
}

// Get user type from URL parameter
$userType = isset($_GET['type']) && in_array($_GET['type'], ['market', 'consumer']) ? $_GET['type'] : 'consumer';

$errors = [];
$values = [
    'email' => '',
    'name' => '',
    'fullname' => '',
    'city' => '',
    'district' => '',
    'password' => '',
    'confirm_password' => ''
];

// Process registration form
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['register'])) {
    // Get and sanitize input
    $email = filter_input(INPUT_POST, 'email', FILTER_SANITIZE_EMAIL);
    $password = $_POST['password'];
    $confirmPassword = $_POST['confirm_password'];
    $city = htmlspecialchars(trim($_POST['city']));
    $district = htmlspecialchars(trim($_POST['district']));
    $userType = $_POST['user_type'];
    
    // Sticky form
    $values['email'] = $email;
    $values['city'] = $city;
    $values['district'] = $district;
    $values['password'] = $password;
    $values['confirm_password'] = $confirmPassword;
    
    if ($userType === 'market') {
        $name = htmlspecialchars(trim($_POST['name']));
        $values['name'] = $name;
    } else {
        $fullname = htmlspecialchars(trim($_POST['fullname']));
        $values['fullname'] = $fullname;
    }
    
    // Validate email
    if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = "Valid email address is required";
    }
    
    // Validate name fields
    if ($userType === 'market') {
        if (empty($name)) {
            $errors[] = "Market name is required";
        }
    } else {
        if (empty($fullname)) {
            $errors[] = "Full name is required";
        }
    }
    
    // Validate other fields
    if (empty($city)) {
        $errors[] = "City is required";
    }
    if (empty($district)) {
        $errors[] = "District is required";
    }
    
    // Validate password
    if (empty($password)) {
        $errors[] = "Password is required";
    } elseif (strlen($password) < 6) {
        $errors[] = "Password must be at least 6 characters";
    }
    
    // Confirm password
    if ($password !== $confirmPassword) {
        $errors[] = "Passwords do not match";
    }
    
    // Check if email already exists
    if (empty($errors)) {
        $table = ($userType === 'market') ? 'markets' : 'consumers';
        $stmt = $db->prepare("SELECT email FROM $table WHERE email = ?");
        $stmt->execute([$email]);
        
        if ($stmt->rowCount() > 0) {
            $errors[] = "Email already exists";
        }
    }
    
    // Register user if no errors
    if (empty($errors)) {
        $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
        $verificationCode = generateVerificationCode();
        
        try {
            if ($userType === 'market') {
                $stmt = $db->prepare("INSERT INTO markets (email, name, password, city, district, verification_code) VALUES (?, ?, ?, ?, ?, ?)");
                $stmt->execute([$email, $name, $hashedPassword, $city, $district, $verificationCode]);
            } else {
                $stmt = $db->prepare("INSERT INTO consumers (email, fullname, password, city, district, verification_code) VALUES (?, ?, ?, ?, ?, ?)");
                $stmt->execute([$email, $fullname, $hashedPassword, $city, $district, $verificationCode]);
            }
            
            // Send verification email
            if (sendVerificationEmail($email, $verificationCode)) {
                // Store data in session for verification page
                $_SESSION['temp_email'] = $email;
                $_SESSION['temp_user_type'] = $userType;
                
                // Redirect to verification page
                header("Location: verify.php");
                exit;
            } else {
                $errors[] = "Failed to send verification email";
            }
        } catch (PDOException $e) {
            $errors[] = "Registration failed: " . $e->getMessage();
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register - Eco Market</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/css/bootstrap.min.css">
    <style>
        body {
            background-color: #f8f9fa;
        }
        .register-container {
            max-width: 550px;
            margin: 3% auto;
            padding: 25px;
            border-radius: 10px;
            box-shadow: 0 0 15px rgba(0, 0, 0, 0.1);
            background-color: white;
        }
        .eco-title {
            color: #2e7d32;
            margin-bottom: 20px;
            text-align: center;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="register-container">
            <h1 class="eco-title">Eco Market</h1>
            <h4 class="mb-4 text-center">Register as a <?php echo ucfirst($userType); ?></h4>
            
            <?php if (!empty($errors)): ?>
                <div class="alert alert-danger">
                    <ul class="mb-0">
                        <?php foreach ($errors as $error): ?>
                            <li><?php echo $error; ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php endif; ?>
            
            <form method="post" action="<?php echo htmlspecialchars($_SERVER['PHP_SELF'] . '?type=' . $userType); ?>">
                <input type="hidden" name="user_type" value="<?php echo $userType; ?>">
                
                <div class="mb-3">
                    <label for="email" class="form-label">Email address</label>
                    <input type="email" class="form-control" id="email" name="email" value="<?php echo $values['email']; ?>" required>
                </div>
                
                <?php if ($userType === 'market'): ?>
                    <div class="mb-3">
                        <label for="name" class="form-label">Market Name</label>
                        <input type="text" class="form-control" id="name" name="name" value="<?php echo $values['name']; ?>" required>
                    </div>
                <?php else: ?>
                    <div class="mb-3">
                        <label for="fullname" class="form-label">Full Name</label>
                        <input type="text" class="form-control" id="fullname" name="fullname" value="<?php echo $values['fullname']; ?>" required>
                    </div>
                <?php endif; ?>
                
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label for="city" class="form-label">City</label>
                        <input type="text" class="form-control" id="city" name="city" value="<?php echo $values['city']; ?>" required>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label for="district" class="form-label">District</label>
                        <input type="text" class="form-control" id="district" name="district" value="<?php echo $values['district']; ?>" required>
                    </div>
                </div>
                
                <div class="mb-3">
                    <label for="password" class="form-label">Password</label>
                    <input type="password" class="form-control" id="password" name="password" required>
                </div>
                
                <div class="mb-3">
                    <label for="confirm_password" class="form-label">Confirm Password</label>
                    <input type="password" class="form-control" id="confirm_password" name="confirm_password" required>
                </div>
                
                <button type="submit" name="register" class="btn btn-success w-100 mb-3">Register</button>
                
                <div class="text-center">
                    <p>Already have an account? <a href="index.php">Login</a></p>
                    <?php if ($userType === 'market'): ?>
                        <p>Are you a consumer? <a href="register.php?type=consumer">Register as a Consumer</a></p>
                    <?php else: ?>
                        <p>Are you a market? <a href="register.php?type=market">Register as a Market</a></p>
                    <?php endif; ?>
                </div>
            </form>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html> 