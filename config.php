<?php
// Database configuration
define('DB_HOST', 'sql209.infinityfree.com');
define('DB_USER', 'if0_39222248');
define('DB_PASS', '76536462Ah');
define('DB_NAME', 'if0_39222248_sportbootspro');

// Create connection
$conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Set charset
$conn->set_charset("utf8");

// Session start
session_start();

// Cart initialization
if (!isset($_SESSION['cart'])) {
    $_SESSION['cart'] = [];
}
?>