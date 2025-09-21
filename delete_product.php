<?php
session_start();
require 'includes/db_connect.php';

// Validate user and role
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

// Get product ID safely
$product_id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
if (!$product_id) {
    header("Location: dashboard.php?error=invalid_product");
    exit;
}

// Verify product ownership
$stmt = $pdo->prepare("SELECT UserID FROM products WHERE ProductID = ?");
$stmt->execute([$product_id]);
$product = $stmt->fetch();

if (!$product) {
    header("Location: dashboard.php?error=product_not_found");
    exit;
}

// Only allow deletion if user is admin or owns the product
if ($_SESSION['role'] !== 'admin' && $product['UserID'] !== $_SESSION['user_id']) {
    header("Location: dashboard.php?error=unauthorized");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Validate CSRF token
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        header("Location: dashboard.php?error=invalid_request");
        exit;
    }

    try {
        // First delete product image if exists
        $stmt = $pdo->prepare("SELECT Image FROM products WHERE ProductID = ?");
        $stmt->execute([$product_id]);
        $image = $stmt->fetchColumn();
        
        if ($image && file_exists("uploads/product_images/$image")) {
            unlink("uploads/product_images/$image");
        }

        // Then delete the product
        $stmt = $pdo->prepare("DELETE FROM products WHERE ProductID = ?");
        $stmt->execute([$product_id]);

        // Redirect with success message
        $redirect = $_SESSION['role'] === 'admin' ? 'admin_dashboard.php' : 
                   ($_SESSION['role'] === 'company' ? 'company_dashboard.php' : 'user_dashboard.php');
        header("Location: $redirect?success=product_deleted");
        exit;
    } catch (PDOException $e) {
        error_log("Delete product error: " . $e->getMessage());
        header("Location: dashboard.php?error=delete_failed");
        exit;
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Confirm Deletion</title>
    <link rel="stylesheet" href="css/style.css">
    <style>
        .confirmation-container {
            max-width: 600px;
            margin: 2rem auto;
            padding: 2rem;
            background: white;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            text-align: center;
        }
        .btn-delete {
            background: #dc3545;
            color: white;
        }
        .btn-cancel {
            background: #6c757d;
            color: white;
        }
    </style>
</head>
<body>
    <div class="confirmation-container">
        <h2>Confirm Product Deletion</h2>
        <p>Are you sure you want to delete this product? This action cannot be undone.</p>
        
        <form method="POST" action="delete_product.php?id=<?= $product_id ?>">
            <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
            <button type="submit" class="btn btn-delete">Yes, Delete Permanently</button>
            <a href="dashboard.php" class="btn btn-cancel">Cancel</a>
        </form>
    </div>
</body>
</html>