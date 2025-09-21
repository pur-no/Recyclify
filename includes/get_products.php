<?php
header('Content-Type: application/json');
require 'db_connect.php';

try {
    // Validate request method
    if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
        throw new Exception('Method not allowed', 405);
    }

    // Optional authentication
    $user_id = null;
    if (isset($_SERVER['HTTP_AUTHORIZATION'])) {
        session_start();
        if (!isset($_SESSION['user_id'])) {
            throw new Exception('Unauthorized', 401);
        }
        $user_id = $_SESSION['user_id'];
    }

    // Build base query
    $query = "SELECT 
                p.ProductID, 
                p.ProductName, 
                p.Price, 
                p.ProductDescription, 
                p.Image,
                p.status,
                c.CategoryName,
                u.Name AS seller_name
              FROM products p
              LEFT JOIN categories c ON p.CategoryID = c.CategoryID
              LEFT JOIN users u ON p.UserID = u.UserID
              WHERE p.status = 'available'";

    $params = [];

    // Filter by category if provided
    if (isset($_GET['category'])) {
        $category = filter_input(INPUT_GET, 'category', FILTER_SANITIZE_STRING);
        $query .= " AND c.CategoryName = ?";
        $params[] = $category;
    }

    // Filter by price range if provided
    if (isset($_GET['min_price']) || isset($_GET['max_price'])) {
        $min_price = filter_input(INPUT_GET, 'min_price', FILTER_VALIDATE_FLOAT);
        $max_price = filter_input(INPUT_GET, 'max_price', FILTER_VALIDATE_FLOAT);
        
        if ($min_price !== false) {
            $query .= " AND p.Price >= ?";
            $params[] = $min_price;
        }
        if ($max_price !== false) {
            $query .= " AND p.Price <= ?";
            $params[] = $max_price;
        }
    }

    // Pagination
    $page = filter_input(INPUT_GET, 'page', FILTER_VALIDATE_INT, ['options' => ['default' => 1, 'min_range' => 1]]);
    $per_page = 10;
    $offset = ($page - 1) * $per_page;

    // Get total count for pagination
    $count_query = str_replace(
        'SELECT p.ProductID, p.ProductName, p.Price, p.ProductDescription, p.Image, p.status, c.CategoryName, u.Name AS seller_name',
        'SELECT COUNT(*) as total',
        $query
    );
    $count_stmt = $pdo->prepare($count_query);
    $count_stmt->execute($params);
    $total = $count_stmt->fetchColumn();

    // Add sorting and pagination to main query
    $query .= " ORDER BY p.ProductName LIMIT ? OFFSET ?";
    $params[] = $per_page;
    $params[] = $offset;

    // Execute query
    $stmt = $pdo->prepare($query);
    $stmt->execute($params);
    $products = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Format response
    $response = [
        'success' => true,
        'data' => $products,
        'pagination' => [
            'page' => $page,
            'per_page' => $per_page,
            'total' => $total,
            'total_pages' => ceil($total / $per_page)
        ]
    ];

    // Add user-specific data if authenticated
    if ($user_id) {
        foreach ($products as &$product) {
            $product['can_edit'] = ($user_id == $product['UserID'] || $_SESSION['role'] === 'admin');
        }
    }

    echo json_encode($response);

} catch (Exception $e) {
    http_response_code($e->getCode() ?: 500);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage(),
        'code' => $e->getCode()
    ]);
}