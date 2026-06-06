<?php
// AuthController.php

// Login function
function login($username, $password) {
    
    global $pdo, $base_url;
    // Query to check if the user exists
    $stmt = $pdo->prepare("SELECT * FROM accounts WHERE username = :username LIMIT 1");
    $stmt->execute(['username' => $username]);
    $user = $stmt->fetch();
    
    if ($user && password_verify($password, $user['password'])) {
        setSession($user['account_id']);
        header('Location: ' . $base_url . '/views/admin/main.php');
        exit;
    } else {
        return false;
    }

    return true;
}


// Logout function

?>
