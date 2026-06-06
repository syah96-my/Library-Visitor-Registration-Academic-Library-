<?php
header('Content-Type: application/json');
include_once '../config/config.php';
include_once '../config/minda.php';
include_once '../sessions/session.php';

if (isset($_SESSION['csrf_token'])) {
    
    echo json_encode(['csrf_token' => simpleEncode($_SESSION['csrf_token'])]);
} else {
    echo json_encode(['csrf_token' => null]);
}
?>
