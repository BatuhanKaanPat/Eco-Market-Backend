<?php
session_start();
require_once "config/db.php";

// Check if user is logged in
if (isset($_SESSION['user_id']) && isset($_SESSION['user_type'])) {
    // Clear remember token in database
    if (isset($_SESSION['email'])) {
        $userType = $_SESSION['user_type'];
        $email = $_SESSION['email'];
        $table = ($userType === 'market') ? 'markets' : 'consumers';
        
        // Clear token in database
        $stmt = $db->prepare("UPDATE $table SET remember_token = NULL WHERE email = ?");
        $stmt->execute([$email]);
    }
    
    // Delete cookies
    if (isset($_COOKIE['remember'])) {
        setcookie('remember', '', time() - 3600, '/');
    }
    if (isset($_COOKIE['user_type'])) {
        setcookie('user_type', '', time() - 3600, '/');
    }
    
    // Clear session
    $_SESSION = array();
    
    // Destroy session
    session_destroy();
}

// Redirect to login page
header("Location: index.php");
exit;
?> 