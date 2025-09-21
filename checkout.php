<?php
session_start();
require 'includes/db_connect.php';
require 'includes/header.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php?redirect=" . urlencode($_SERVER['REQUEST_URI']));
    exit();
}

// Validate cart
if (empty($_SESSION['cart']) || !is_array($_SESSION['cart'])) {
    header("Location: cart.php");
    exit();
}

// Fetch products in cart
$product_ids = array_keys($_SESSION['cart']);
$placeholders = implode(',', array_fill(0, count($product_ids), '?'));
$stmt = $pdo->prepare("SELECT ProductID, ProductName, Price, Image FROM products WHERE ProductID IN ($placeholders)");
$stmt->execute($product_ids);
$products = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Calculate total
$total_price = 0;
$cart_items = [];
foreach ($products as $product) {
    $quantity = $_SESSION['cart'][$product['ProductID']];
    $subtotal = $product['Price'] * $quantity;
    $total_price += $subtotal;
    $cart_items[] = [
        'product' => $product,
        'quantity' => $quantity,
        'subtotal' => $subtotal
    ];
}

// Handle checkout
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Validate CSRF token
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        $error_message = "Invalid request";
    } else {
        // Validate inputs
        $name = trim(filter_input(INPUT_POST, 'name', FILTER_SANITIZE_STRING));
        $phone = trim(filter_input(INPUT_POST, 'phone', FILTER_SANITIZE_STRING));
        $email = filter_input(INPUT_POST, 'email', FILTER_VALIDATE_EMAIL);
        $delivery_address = trim(filter_input(INPUT_POST, 'delivery_address', FILTER_SANITIZE_STRING));
        $payment_method = in_array($_POST['payment_method'], 
            ['Credit Card', 'Debit Card', 'PayPal', 'Bank Transfer', 'Cash on Delivery'])
            ? $_POST['payment_method']
            : 'Cash on Delivery';

        if (empty($name) || empty($phone) || !$email || empty($delivery_address)) {
            $error_message = "Please fill all required fields";
        } else {
            try {
                $pdo->beginTransaction();

                // Create payment record
                $stmt = $pdo->prepare("INSERT INTO payments 
                    (PaymentMethod, PaymentStatus, PaymentDate) 
                    VALUES (?, 'Pending', NOW())");
                $stmt->execute([$payment_method]);
                $payment_id = $pdo->lastInsertId();

                // Create order
                $stmt = $pdo->prepare("INSERT INTO orders 
                    (UserID, TotalAmount, DeliveryAddress, Status, OrderDate, PaymentID) 
                    VALUES (?, ?, ?, 'Pending', NOW(), ?)");
                $stmt->execute([
                    $_SESSION['user_id'],
                    $total_price,
                    $delivery_address,
                    $payment_id
                ]);
                $order_id = $pdo->lastInsertId();

                // Add order items
                foreach ($cart_items as $item) {
                    $stmt = $pdo->prepare("INSERT INTO order_details 
                        (OrderID, ProductID, Quantity, Price) 
                        VALUES (?, ?, ?, ?)");
                    $stmt->execute([
                        $order_id,
                        $item['product']['ProductID'],
                        $item['quantity'],
                        $item['product']['Price']
                    ]);

                    // Update product status to sold
                    $stmt = $pdo->prepare("UPDATE products SET status = 'sold' WHERE ProductID = ?");
                    $stmt->execute([$item['product']['ProductID']]);
                }

                $pdo->commit();
                
                // Clear cart and redirect
                unset($_SESSION['cart']);
                header("Location: order_success.php?order_id=" . $order_id);
                exit();
            } catch (Exception $e) {
                $pdo->rollBack();
                error_log("Checkout error: " . $e->getMessage());
                $error_message = "An error occurred during checkout. Please try again.";
            }
        }
    }
}
?>

<!-- Add CSRF token to your checkout form -->
<input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">