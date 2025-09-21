<?php
session_start();
include 'includes/db_connect.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $order_id = $_POST['order_id'] ?? null;
    $amount = $_POST['amount'] ?? 0;
    $payment_method = $_POST['payment_method'] ?? '';

    // Validate inputs
    if (!$order_id || !is_numeric($order_id) || empty($payment_method)) {
        header("Location: payment.php?order_id=$order_id&error=invalid_input");
        exit();
    }

    try {
        // Fetch PaymentID from orders table
        $stmt = $pdo->prepare("SELECT PaymentID FROM orders WHERE OrderID = ?");
        $stmt->execute([$order_id]);
        $order = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$order || !$order['PaymentID']) {
            header("Location: payment.php?order_id=$order_id&error=order_not_found");
            exit();
        }

        $payment_id = $order['PaymentID'];

        // Check if a payment record exists
        $stmt = $pdo->prepare("SELECT * FROM payments WHERE PaymentID = ?");
        $stmt->execute([$payment_id]);
        $payment = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($payment) {
            // Update existing payment record
            $update_stmt = $pdo->prepare("
                UPDATE payments 
                SET PaymentMethod = ?, PaymentStatus = ?, PaymentDate = CURDATE() 
                WHERE PaymentID = ?
            ");
            $update_stmt->execute([$payment_method, 'Paid', $payment_id]);
        } else {
            // Insert new payment record (shouldn't happen if checkout created one)
            $insert_stmt = $pdo->prepare("
                INSERT INTO payments (PaymentMethod, PaymentStatus, PaymentDate) 
                VALUES (?, ?, CURDATE())
            ");
            $insert_stmt->execute([$payment_method, 'Paid']);
            $new_payment_id = $pdo->lastInsertId();

            // Update orders with new PaymentID (optional, only if needed)
            $update_order_payment = $pdo->prepare("UPDATE orders SET PaymentID = ? WHERE OrderID = ?");
            $update_order_payment->execute([$new_payment_id, $order_id]);
        }

        // Update order status
        $update_order_stmt = $pdo->prepare("UPDATE orders SET Status = ? WHERE OrderID = ?");
        $update_order_stmt->execute(['Completed', $order_id]);

        // Redirect to success page
        header("Location: payment_success.php");
        exit();
    } catch (Exception $e) {
        error_log("Payment processing error: " . $e->getMessage());
        header("Location: payment.php?order_id=$order_id&error=processing_error");
        exit();
    }
}
?>