<?php
// session.php

// Start the session
if (session_status() === PHP_SESSION_NONE) {
    session_set_cookie_params([
        'lifetime' => 0,
        'path' => '/',
        'secure' => (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off'),
        'httponly' => true,
        'samesite' => 'Lax',
    ]);
    session_start();
}
// Check if the user is logged in
function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

// Set a session for the logged-in user
function setSession($user_id) {
    session_regenerate_id(true);
    $_SESSION['user_id'] = $user_id;
    // Generate and set a unique token
    $token = generateUniqueToken();
    $_SESSION['csrf_token'] = $token;
    saveTokenToDatabase($user_id, $token);
}

// Generate a unique CSRF token
function generateUniqueToken() {
    return bin2hex(random_bytes(32));
}

// Save the CSRF token to the database
function saveTokenToDatabase($user_id, $token) {
    global $pdo; // Use the global database connection

    try {
        $stmt = $pdo->prepare('UPDATE accounts SET token = ? WHERE account_id = ?');
        $stmt->execute([$token, $user_id]);
    } catch (PDOException $e) {
        error_log('Error saving token to database: ' . $e->getMessage());
    }
}

// Destroy the session to log out
// Destroy the session and remove the token from the database
function logout() {
    global $pdo, $base_url; // Access the global database connection

    try {
        // Get the user ID from the session
        $user_id = $_SESSION['user_id'] ?? null;

        // Delete the token from the database
        if ($user_id) {
            $stmt = $pdo->prepare('UPDATE accounts SET token = NULL WHERE account_id = ?');
            $stmt->execute([$user_id]);
        }
    } catch (PDOException $e) {
        error_log('Error deleting token from database: ' . $e->getMessage());
    }

    // Destroy the session
    session_destroy();
    header('Location: ' . $base_url . '/views/admin/log-masuk.php');
    exit();
}


?>
