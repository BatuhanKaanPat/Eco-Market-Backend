<?php
session_start();
require_once "config/db.php";

// Check if user has temporary email set
if (!isset($_SESSION['temp_email']) || !isset($_SESSION['temp_user_type'])) {
    header("Location: index.php");
    exit;
}

$email = $_SESSION['temp_email'];
$userType = $_SESSION['temp_user_type'];
$table = ($userType === 'market') ? 'markets' : 'consumers';
$errors = [];
$success = false;

// Process verification form
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['verify'])) {
    $code = $_POST['verification_code'];
    
    // Validate code
    if (empty($code)) {
        $errors[] = "Verification code is required";
    } elseif (strlen($code) !== 6 || !ctype_digit($code)) {
        $errors[] = "Invalid verification code format";
    } else {
        // Check if the code matches
        $stmt = $db->prepare("SELECT * FROM $table WHERE email = ? AND verification_code = ?");
        $stmt->execute([$email, $code]);
        $user = $stmt->fetch();
        
        if ($user) {
            // Update user as verified
            $stmt = $db->prepare("UPDATE $table SET verified = TRUE, verification_code = NULL WHERE email = ?");
            $stmt->execute([$email]);
            
            // Set success message
            $success = true;
            
            // Clear temporary session data
            unset($_SESSION['temp_email']);
            unset($_SESSION['temp_user_type']);
        } else {
            $errors[] = "Invalid verification code";
        }
    }
}

// Resend verification code
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['resend'])) {
    // Generate new verification code
    $newCode = generateVerificationCode();
    
    // Update verification code in database
    $stmt = $db->prepare("UPDATE $table SET verification_code = ? WHERE email = ?");
    $stmt->execute([$newCode, $email]);
    
    // Send new verification email
    if (sendVerificationEmail($email, $newCode)) {
        $success = "A new verification code has been sent to your email";
    } else {
        $errors[] = "Failed to send verification email";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Email Verification - Eco Market</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/css/bootstrap.min.css">
    <style>
        body {
            background-color: #f8f9fa;
        }
        .verify-container {
            max-width: 450px;
            margin: 10% auto;
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
        <div class="verify-container">
            <h1 class="eco-title">Eco Market</h1>
            <h4 class="mb-4 text-center">Email Verification</h4>
            
            <?php if ($success === true): ?>
                <div class="alert alert-success text-center">
                    <h5 class="mb-3">Email Verified Successfully!</h5>
                    <p>Your account has been verified. You can now login to your account.</p>
                    <a href="index.php" class="btn btn-success mt-2">Go to Login</a>
                </div>
            <?php elseif (is_string($success)): ?>
                <div class="alert alert-success"><?php echo $success; ?></div>
            <?php else: ?>
                <?php if (!empty($errors)): ?>
                    <div class="alert alert-danger">
                        <ul class="mb-0">
                            <?php foreach ($errors as $error): ?>
                                <li><?php echo $error; ?></li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                <?php endif; ?>
                
                <div class="text-center mb-4">
                    <p>A verification code has been sent to <strong><?php echo htmlspecialchars($email); ?></strong></p>
                    <p>Please enter the 6-digit code to verify your account.</p>
                </div>
                
                <form method="post" action="<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>">
                    <div class="mb-3">
                        <label for="verification_code" class="form-label">Verification Code</label>
                        <input type="text" class="form-control" id="verification_code" name="verification_code" 
                               placeholder="Enter 6-digit code" maxlength="6" required>
                    </div>
                    
                    <button type="submit" name="verify" class="btn btn-success w-100 mb-3">Verify Email</button>
                </form>
                
                <div class="text-center mt-3">
                    <p>Didn't receive the code?</p>
                    <form method="post" action="<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>">
                        <button type="submit" name="resend" class="btn btn-outline-success">Resend Code</button>
                    </form>
                </div>
            <?php endif; ?>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html> 