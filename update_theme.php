<?php
<?php
session_start();

if (isset($_GET['theme'])) {
    $_SESSION['theme'] = $_GET['theme'];
    echo json_encode(['success' => true]);
} else {
    echo json_encode(['success' => false]);
}