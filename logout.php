<?php
// Include database configuration
require_once 'config.php';

// Start session
if (!session_id()) {
    session_start();
}

// Assuming you already have a database connection setup in another part of your application
// Create a new PDO connection
try {
    $pdo = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME, DB_USERNAME, DB_PASSWORD);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("ERROR: Could not connect. " . $e->getMessage());
}

// Check if user data is available in the session to log user-specific information
if (isset($_SESSION['userData']) && !empty($_SESSION['userData'])) {
    $userId = $_SESSION['userData']['id']; // Adjust based on your session structure
    $logoutTime = date('Y-m-d H:i:s'); // Current time
    $ipAddress = $_SERVER['REMOTE_ADDR']; // User's IP address

    // Insert logout record
    $query = "INSERT INTO logout_log (user_id, logout_time, ip_address) VALUES (:userId, :logoutTime, :ipAddress)";
    $stmt = $pdo->prepare($query);
    $stmt->bindParam(':userId', $userId, PDO::PARAM_INT);
    $stmt->bindParam(':logoutTime', $logoutTime);
    $stmt->bindParam(':ipAddress', $ipAddress);
    $stmt->execute();
}

// Remove access token, state, and user data from session
unset($_SESSION['access_token']);
unset($_SESSION['state']);
unset($_SESSION['userData']);

// Redirect to the homepage
header("Location:index.php");
