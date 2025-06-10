<?php
// Database connection
const DSN = "mysql:host=localhost;dbname=eco_market;charset=utf8mb4";
const USER = "root";
const PASSWORD = "";

try {
    $db = new PDO(DSN, USER, PASSWORD);
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    // Redirect to error page
    header("Location: ../error.php");
    exit;
}

// Generate verification code
function generateVerificationCode() {
    return str_pad(rand(0, 999999), 6, '0', STR_PAD_LEFT);
}

// Include mail utility
require_once __DIR__ . "/mail.php";

// Check user credentials for market
function checkMarket($email, $pass, &$user) {
    global $db;
    
    $stmt = $db->prepare("SELECT * FROM markets WHERE email = ? AND verified = TRUE");
    $stmt->execute([$email]);
    $user = $stmt->fetch();
    return $user ? password_verify($pass, $user["password"]) : false;
}

// Check user credentials for consumer
function checkConsumer($email, $pass, &$user) {
    global $db;
    
    $stmt = $db->prepare("SELECT * FROM consumers WHERE email = ? AND verified = TRUE");
    $stmt->execute([$email]);
    $user = $stmt->fetch();
    return $user ? password_verify($pass, $user["password"]) : false;
}

// Get user by remember token
function getUserByToken($token, $userType) {
    global $db;
    
    $table = ($userType === 'market') ? 'markets' : 'consumers';
    $stmt = $db->prepare("SELECT * FROM $table WHERE remember_token = ?");
    $stmt->execute([$token]);
    return $stmt->fetch();
}

// Set remember token for user
function setTokenByEmail($email, $token, $userType) {
    global $db;
    
    $table = ($userType === 'market') ? 'markets' : 'consumers';
    $stmt = $db->prepare("UPDATE $table SET remember_token = ? WHERE email = ?");
    $stmt->execute([$token, $email]);
} 