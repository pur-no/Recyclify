<?php
session_start();
require 'db_connect.php';

// Rate limiting - max 5 attempts per 15 minutes
$max_attempts = 5;
$lockout_time = 900; // 15 minutes in seconds

if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
    header("Location: login.php?error=invalid_request");
    exit;
}

$email = filter_input(INPUT_POST, 'email', FILTER_SANITIZE_EMAIL);
$password = $_POST['password'] ?? '';

// Check if IP is blocked
$ip = $_SERVER['REMOTE_ADDR'];
$stmt = $pdo->prepare("SELECT attempts, last_attempt FROM login_attempts WHERE ip = ?");
$stmt->execute([$ip]);
$attempt = $stmt->fetch();

if ($attempt && $attempt['attempts'] >= $max_attempts && 
    (time() - strtotime($attempt['last_attempt'])) < $lockout_time) {
    header("Location: login.php?error=too_many_attempts");
    exit;
}

// Validate credentials
$stmt = $pdo->prepare("SELECT * FROM users WHERE email = ? LIMIT 1");
$stmt->execute([$email]);
$user = $stmt->fetch();

if ($user && password_verify($password, $user['Password'])) {
    // Successful login - reset attempts
    $pdo->prepare("DELETE FROM login_attempts WHERE ip = ?")->execute([$ip]);
    
    session_regenerate_id(true);
    $_SESSION = [
        'user_id' => $user['UserID'],
        'username' => htmlspecialchars($user['Name']),
        'email' => htmlspecialchars($user['Email']),
        'role' => $user['Role'],
        'last_login' => time()
    ];
    
    // Redirect based on role
    header("Location: " . ($user['Role'] === 'admin' ? 'admin_dashboard.php' : 
                         ($user['Role'] === 'company' ? 'company_dashboard.php' : 'user_dashboard.php')));
    exit;
} else {
    // Failed login - record attempt
    if ($attempt) {
        $pdo->prepare("UPDATE login_attempts SET attempts = attempts + 1, last_attempt = NOW() WHERE ip = ?")
            ->execute([$ip]);
    } else {
        $pdo->prepare("INSERT INTO login_attempts (ip, attempts, last_attempt) VALUES (?, 1, NOW())")
            ->execute([$ip]);
    }
    
    header("Location: login.php?error=invalid_credentials");
    exit;
}