<?php
session_start();
require_once "config/db.php";

// Check if already logged in
if (isset($_SESSION['user_id'])) {
    $userType = $_SESSION['user_type'];
    header("Location: " . ($userType === 'market' ? 'market/dashboard.php' : 'consumer/dashboard.php'));
    exit;
}

// Check for remember cookie
if (!isset($_SESSION['user_id']) && isset($_COOKIE['remember'])) {
    // Try to authenticate with cookie
    $token = $_COOKIE['remember'];
    $userType = $_COOKIE['user_type'];
    
    $user = getUserByToken($token, $userType);
    if ($user) {
        // Set session
        $_SESSION['user_id'] = $userType === 'market' ? $user['market_id'] : $user['consumer_id'];
        $_SESSION['user_type'] = $userType;
        $_SESSION['email'] = $user['email'];
        $_SESSION['name'] = $userType === 'market' ? $user['name'] : $user['fullname'];
        
        header("Location: " . ($userType === 'market' ? 'market/dashboard.php' : 'consumer/dashboard.php'));
        exit;
    }
}

$error = "";

// Process login form
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['login'])) {
    $email = filter_input(INPUT_POST, 'email', FILTER_SANITIZE_EMAIL);
    $password = $_POST['password'];
    $userType = $_POST['user_type'];
    $remember = isset($_POST['remember']) ? true : false;
    
    // Validate input
    if (empty($email) || empty($password) || empty($userType)) {
        $error = "All fields are required";
    } else {
        $user = null;
        $authenticated = false;
        
        if ($userType === 'market') {
            $authenticated = checkMarket($email, $password, $user);
        } else {
            $authenticated = checkConsumer($email, $password, $user);
        }
        
        if ($authenticated) {
            // Set session variables
            $_SESSION['user_id'] = $userType === 'market' ? $user['market_id'] : $user['consumer_id'];
            $_SESSION['user_type'] = $userType;
            $_SESSION['email'] = $user['email'];
            $_SESSION['name'] = $userType === 'market' ? $user['name'] : $user['fullname'];
            
            // Handle remember me
            if ($remember) {
                $token = bin2hex(random_bytes(32));
                setTokenByEmail($email, $token, $userType);
                
                // Set cookies
                setcookie('remember', $token, time() + 30 * 24 * 60 * 60, '/');
                setcookie('user_type', $userType, time() + 30 * 24 * 60 * 60, '/');
            }
            
            // Redirect to dashboard
            header("Location: " . ($userType === 'market' ? 'market/dashboard.php' : 'consumer/dashboard.php'));
            exit;
        } else {
            $error = "Invalid email or password";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Eco Market - Sustainable Shopping</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/css/bootstrap.min.css">
    <style>
        body {
            background-color: #f8f9fa;
        }
        .login-container {
            max-width: 450px;
            margin: 5% auto;
            padding: 25px;
            border-radius: 10px;
            box-shadow: 0 0 15px rgba(0, 0, 0, 0.1);
            background-color: white;
        }
        .eco-title {
            color: #2e7d32;
            margin-bottom: 30px;
            text-align: center;
        }
        .user-type-toggle {
            margin-bottom: 20px;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="login-container">
            <h1 class="eco-title">Eco Market</h1>
            <h4 class="mb-4 text-center">Sustainable Shopping Made Easy</h4>
            
            <?php if (!empty($error)): ?>
                <div class="alert alert-danger"><?php echo $error; ?></div>
            <?php endif; ?>
            
            <form method="post" action="<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>">
                <div class="user-type-toggle d-flex justify-content-center">
                    <div class="form-check form-check-inline">
                        <input class="form-check-input" type="radio" name="user_type" id="consumer" value="consumer" checked>
                        <label class="form-check-label" for="consumer">Consumer</label>
                    </div>
                    <div class="form-check form-check-inline">
                        <input class="form-check-input" type="radio" name="user_type" id="market" value="market">
                        <label class="form-check-label" for="market">Market</label>
                    </div>
                </div>
                
                <div class="mb-3">
                    <label for="email" class="form-label">Email address</label>
                    <input type="email" class="form-control" id="email" name="email" required>
                </div>
                <div class="mb-3">
                    <label for="password" class="form-label">Password</label>
                    <input type="password" class="form-control" id="password" name="password" required>
                </div>
                <div class="mb-3 form-check">
                    <input type="checkbox" class="form-check-input" id="remember" name="remember">
                    <label class="form-check-label" for="remember">Remember me</label>
                </div>
                <button type="submit" name="login" class="btn btn-success w-100 mb-3">Login</button>
                <div class="text-center">
                    <span>Don't have an account? Register as a </span>
                    <a href="register.php?type=consumer">Consumer</a> or
                    <a href="register.php?type=market">Market</a>
                </div>
            </form>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html> 