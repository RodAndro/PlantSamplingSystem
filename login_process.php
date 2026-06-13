<?php
session_start();
require_once 'config/db.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email']);
    $password = $_POST['password'];
    
    // Validate input
    if (empty($email) || empty($password)) {
        header('Location: login.php?error=1');
        exit;
    }
    
    try {
        $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ?");
        $stmt->execute([$email]);
        $user = $stmt->fetch();
        
        error_log("[v0] Login attempt for email: " . $email);
        error_log("[v0] User found: " . ($user ? "yes" : "no"));
        if ($user) {
            error_log("[v0] Password verify result: " . (password_verify($password, $user['password']) ? "true" : "false"));
            error_log("[v0] Stored hash: " . substr($user['password'], 0, 20) . "...");
        }
        
        if ($user && password_verify($password, $user['password'])) {
            // Login successful
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['user_name'] = $user['name'];
            $_SESSION['user_email'] = $user['email'];
            $_SESSION['user_role'] = $user['role'];
            
            header('Location: index.php');
            exit;
        } else {
            // Invalid credentials
            header('Location: login.php?error=1');
            exit;
        }
    } catch(PDOException $e) {
        error_log("Login error: " . $e->getMessage());
        header('Location: login.php?error=2');
        exit;
    }
} else {
    header('Location: login.php');
    exit;
}
?>
