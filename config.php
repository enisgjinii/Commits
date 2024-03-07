<?php
// Database configuration 
define('DB_HOST', 'localhost');
define('DB_USERNAME', 'root');
define('DB_PASSWORD', '');
define('DB_NAME', 'github');
define('DB_USER_TBL', 'users');

// GitHub API configuration 
define('CLIENT_ID', 'c4d2e61ac2f3663ed791');
define('CLIENT_SECRET', '131cf7a7fb3d8885b720d73a7ef2d2b0db6b51e2');
define('REDIRECT_URL', 'http://localhost/Commits/index.php');

// Start session 
if (!session_id()) {
    session_start();
}

// Include Github client library 
require_once 'src/Github_OAuth_Client.php';

// Initialize Github OAuth client class 
$gitClient = new Github_OAuth_Client(array(
    'client_id' => CLIENT_ID,
    'client_secret' => CLIENT_SECRET,
    'redirect_uri' => REDIRECT_URL
));

// Try to get the access token 
if (isset($_SESSION['access_token'])) {
    $accessToken = $_SESSION['access_token'];
}
