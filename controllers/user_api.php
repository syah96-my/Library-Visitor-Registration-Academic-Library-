<?php
include_once '../config/config.php';
include_once '../config/minda.php';
include_once '../sessions/session.php';
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: ' . $base_url);



// Helper to get JSON data from POST requests
function getRequestData() {
    $input = file_get_contents('php://input');
    return json_decode($input, true);
}

function validateCsrfToken($token) {
    global $pdo; // Access the global database connection

    // Check if the user is logged in
    if (!isset($_SESSION['user_id'])) {
        return false;
    }

    try {
        // Fetch the stored token from the database using the user ID
        $stmt = $pdo->prepare('SELECT token FROM accounts WHERE account_id = ?');
        $stmt->execute([$_SESSION['user_id']]);
        $dbToken = $stmt->fetchColumn();

        // Compare the session token with the database token
        return is_string($dbToken) && hash_equals($dbToken, $token);
    } catch (PDOException $e) {
        error_log('Error validating CSRF token: ' . $e->getMessage());
        return false;
    }
}


// Enforce rate limiting
function enforceRateLimit() {
    if (!isset($_SESSION['request_count'])) {
        $_SESSION['request_count'] = 0;
        $_SESSION['last_request_time'] = time();
    }

    $timeDiff = time() - $_SESSION['last_request_time'];
    if ($timeDiff > 60) {
        $_SESSION['request_count'] = 0;
        $_SESSION['last_request_time'] = time();
    }

    $_SESSION['request_count']++;
    if ($_SESSION['request_count'] > 100) {
        echo json_encode(['status' => 'error', 'message' => 'Rate limit exceeded']);
        exit;
    }
}

$method = $_SERVER['REQUEST_METHOD'];

if ($method !== 'POST') {
    echo json_encode(['status' => 'error', 'message' => 'Unauthorized or invalid method']);
    exit;
}

enforceRateLimit();
$data = getRequestData();
if (!is_array($data)) {
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => 'Invalid request']);
    exit;
}

$action = $data['action'] ?? '';
$csrfToken = isset($data['csrf_token']) ? simpleDecode($data['csrf_token']) : '';

if (!validateCsrfToken($csrfToken)) {
    echo json_encode(['status' => 'error', 'message' => 'Invalid CSRF token']);
    exit;
}

try {
    if ($action === 'add') {
          $username = trim((string) ($data['username'] ?? ''));
        if ($username === '' || empty($data['password'])) {
            echo json_encode(['status' => 'error', 'message' => 'Username and password are required']);
            exit;
        }

        $password = password_hash($data['password'], PASSWORD_BCRYPT);
        $requestedRole = $data['role'] ?? 'admin';
        $role = in_array($requestedRole, ['admin', 'superadmin'], true) ? $requestedRole : 'admin';
    
        $stmt = $pdo->prepare('INSERT INTO accounts (username, password, role) VALUES (?, ?, ?)');
        $stmt->execute([$username, $password, $role]);
    
        $id = $pdo->lastInsertId();
    
        echo json_encode([
            'status' => 'success',
            'message' => 'User added',
            'user' => [
                'id' => $id,
                'username' => $username,
                'role' => $role
            ]
        ]);
    } elseif ($action === 'update') {
        $id = (int) ($data['id'] ?? 0);
        $username = trim((string) ($data['username'] ?? ''));
        $requestedRole = $data['role'] ?? 'admin';
        $role = in_array($requestedRole, ['admin', 'superadmin'], true) ? $requestedRole : 'admin';

        if ($id <= 0 || $username === '') {
            echo json_encode(['status' => 'error', 'message' => 'ID and username are required']);
            exit;
        }
    
        if (!empty($data['password'])) {
            $password = password_hash($data['password'], PASSWORD_BCRYPT);
            $stmt = $pdo->prepare('UPDATE accounts SET username=?, password=?, role=? WHERE account_id=?');
            $stmt->execute([$username, $password, $role, $id]);
        } else {
            $stmt = $pdo->prepare('UPDATE accounts SET username=?, role=? WHERE account_id=?');
            $stmt->execute([$username, $role, $id]);
        }
    
        echo json_encode(['status' => 'success', 'message' => 'User updated']);
    } elseif ($action === 'delete') {
        $id = (int) ($data['id'] ?? 0);
        if ($id <= 0 || (isset($_SESSION['user_id']) && (int) $_SESSION['user_id'] === $id)) {
            echo json_encode(['status' => 'error', 'message' => 'Invalid user delete request']);
            exit;
        }

        $stmt = $pdo->prepare('DELETE FROM accounts WHERE account_id=?');
        $stmt->execute([$id]);

        echo json_encode(['status' => 'success', 'message' => 'User deleted']);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Invalid action']);
    }
} catch (PDOException $e) {
    error_log('User API database error: ' . $e->getMessage());
    echo json_encode(['status' => 'error', 'message' => 'Database error']);
}
?>
