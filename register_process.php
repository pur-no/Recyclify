<?php
session_start();
require 'db_connect.php';

// Validate CSRF token
if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
    header("Location: register.php?error=invalid_request");
    exit;
}

// Sanitize inputs
$username = trim(filter_input(INPUT_POST, 'username', FILTER_SANITIZE_STRING));
$email = filter_input(INPUT_POST, 'email', FILTER_SANITIZE_EMAIL);
$password = $_POST['password'] ?? '';
$confirm_password = $_POST['confirm_password'] ?? '';
$role = in_array($_POST['role'], ['user', 'company']) ? $_POST['role'] : 'user';

// Admin role can only be set by existing admins
if ($_POST['role'] === 'admin' && (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin')) {
    $role = 'user';
}

// Validate inputs
$errors = [];
if (empty($username) || strlen($username) < 3) {
    $errors[] = 'username_invalid';
}
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $errors[] = 'email_invalid';
}
if (strlen($password) < 8) {
    $errors[] = 'password_length';
}
if (!preg_match('/[A-Z]/', $password) || !preg_match('/[0-9]/', $password)) {
    $errors[] = 'password_complexity';
}
if ($password !== $confirm_password) {
    $errors[] = 'password_mismatch';
}

// Check if email exists
$stmt = $pdo->prepare("SELECT 1 FROM users WHERE email = ?");
$stmt->execute([$email]);
if ($stmt->fetch()) {
    $errors[] = 'email_exists';
}

if (!empty($errors)) {
    header("Location: register.php?error=" . implode(',', $errors));
    exit;
}

// Create user
$hashed_password = password_hash($password, PASSWORD_BCRYPT, ['cost' => 12]);
$stmt = $pdo->prepare("INSERT INTO users (Name, Email, Password, Role) VALUES (?, ?, ?, ?)");
$stmt->execute([$username, $email, $hashed_password, $role]);

// Auto-login after registration
if ($stmt->rowCount() > 0) {
    $user_id = $pdo->lastInsertId();
    $_SESSION['user_id'] = $user_id;
    $_SESSION['username'] = $username;
    $_SESSION['email'] = $email;
    $_SESSION['role'] = $role;
    
    header("Location: " . ($role === 'admin' ? 'admin_dashboard.php' : 
                         ($role === 'company' ? 'company_dashboard.php' : 'user_dashboard.php')));
    exit;
} else {
    header("Location: register.php?error=registration_failed");
    exit;
}