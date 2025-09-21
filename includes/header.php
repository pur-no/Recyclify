<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start([
        'cookie_lifetime' => 86400, // 1 day
        'cookie_secure'   => true,
        'cookie_httponly' => true,
        'use_strict_mode' => true
    ]);
}

// Initialize CSRF token if not exists
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// Theme handling with session fallback
$theme = $_SESSION['theme'] ?? 'light';
if (isset($_GET['toggle_theme'])) {
    $newTheme = $theme === 'light' ? 'dark' : 'light';
    $_SESSION['theme'] = $newTheme;
    header("Location: " . strtok($_SERVER["REQUEST_URI"], '?'));
    exit();
}

$cart_items_count = isset($_SESSION['cart']) ? count($_SESSION['cart']) : 0;
?>
<!DOCTYPE html>
<html lang="en" data-theme="<?= $theme ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="Recyclify - Your sustainable marketplace for recycled products">
    <title><?= htmlspecialchars($page_title ?? 'Recyclify') ?></title>
    
    <!-- Favicon -->
    <link rel="icon" href="images/favicon.ico">
    
    <!-- Preload CSS -->
    <link rel="preload" href="css/style.css" as="style">
    <link rel="preload" href="css/header.css" as="style">
    
    <!-- Stylesheets -->
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="css/header.css">
    
    <!-- Dark mode CSS if enabled -->
    <?php if ($theme === 'dark'): ?>
    <link rel="stylesheet" href="css/dark.css">
    <?php endif; ?>
</head>
<body>
<header>
    <nav>
        <div class="hamburger" onclick="toggleMenu()">
            <span></span>
            <span></span>
            <span></span>
        </div>
        
        <a href="index.php" class="logo">
            <img src="images/Recyclify_bd.png" alt="Recyclify Logo" height="40">
        </a>

        <ul class="navbar" id="menu">
            <li><a href="products.php" class="<?= basename($_SERVER['PHP_SELF']) === 'products.php' ? 'active' : '' ?>">
                <i class="fas fa-store"></i> Shop
            </a></li>
            
            <li>
                <a href="cart.php" class="cart-btn">
                    <i class="fas fa-shopping-cart"></i>
                    <?php if($cart_items_count > 0): ?>
                        <span class="cart-badge"><?= $cart_items_count ?></span>
                    <?php endif; ?>
                </a>
            </li>
            
            <li>
                <a href="?toggle_theme=1" class="btn-toggle">
                    <i class="fas fa-<?= $theme === 'dark' ? 'sun' : 'moon' ?>"></i>
                    <?= $theme === 'dark' ? 'Light' : 'Dark' ?>
                </a>
            </li>
        </ul>
    </nav>
</header>