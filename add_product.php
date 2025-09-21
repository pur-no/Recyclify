<?php
session_start();
require 'includes/db_connect.php';
require 'includes/header.php';

if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'], ['company', 'user'])) {
    header("Location: login.php");
    exit;
}

$error = '';
$allowed_mimes = ['image/jpeg' => 'jpg', 'image/png' => 'png'];
$max_file_size = 5 * 1024 * 1024; // 5MB

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Validate CSRF token
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        $error = 'Invalid request';
    } else {
        $product_name = trim(filter_input(INPUT_POST, 'product_name', FILTER_SANITIZE_STRING));
        $price = filter_input(INPUT_POST, 'price', FILTER_VALIDATE_FLOAT);
        $category_id = filter_input(INPUT_POST, 'category_id', FILTER_VALIDATE_INT);
        $product_condition = in_array($_POST['product_condition'], ['New', 'Used - Like New', 'Refurbished']) 
            ? $_POST['product_condition'] 
            : 'New';
        $description = trim(filter_input(INPUT_POST, 'description', FILTER_SANITIZE_STRING));

        // Validate inputs
        if (empty($product_name) || strlen($product_name) < 3) {
            $error = 'Product name must be at least 3 characters';
        } elseif ($price === false || $price <= 0) {
            $error = 'Invalid price';
        } elseif ($category_id === false || $category_id <= 0) {
            $error = 'Invalid category';
        } elseif (empty($_FILES['product_image']['tmp_name'])) {
            $error = 'Product image is required';
        } elseif ($_FILES['product_image']['size'] > $max_file_size) {
            $error = 'Image size exceeds 5MB limit';
        } else {
            // Validate image
            $finfo = finfo_open(FILEINFO_MIME_TYPE);
            $mime = finfo_file($finfo, $_FILES['product_image']['tmp_name']);
            finfo_close($finfo);

            if (!array_key_exists($mime, $allowed_mimes)) {
                $error = 'Only JPG and PNG images are allowed';
            } else {
                // Generate unique filename
                $ext = $allowed_mimes[$mime];
                $filename = uniqid('product_', true) . '.' . $ext;
                $upload_path = 'uploads/product_images/' . $filename;

                // Create directory if not exists
                if (!is_dir('uploads/product_images')) {
                    mkdir('uploads/product_images', 0755, true);
                }

                if (move_uploaded_file($_FILES['product_image']['tmp_name'], $upload_path)) {
                    // Insert product
                    $stmt = $pdo->prepare("INSERT INTO products 
                        (ProductName, Price, ProductDescription, ProductCondition, CategoryID, UserID, Image) 
                        VALUES (?, ?, ?, ?, ?, ?, ?)");
                    $stmt->execute([
                        $product_name,
                        $price,
                        $description,
                        $product_condition,
                        $category_id,
                        $_SESSION['user_id'],
                        $filename
                    ]);

                    header("Location: " . ($_SESSION['role'] === 'company' ? 'company_dashboard.php' : 'user_dashboard.php'));
                    exit;
                } else {
                    $error = 'Error uploading image';
                }
            }
        }
    }
}
?>

<!-- Rest of your HTML form remains the same, just add CSRF token -->
<input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">