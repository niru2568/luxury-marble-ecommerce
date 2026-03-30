<?php
// Database configuration
$host = 'localhost';
$db_name = 'luxury_marble_db';
$user = 'root'; // replace with your database username
$password = ''; // replace with your database password

try {
    $conn = new PDO("mysql:host=$host;dbname=$db_name", $user, $password);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    echo "Connection failed: " . $e->getMessage();
}
?>