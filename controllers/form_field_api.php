<?php
include_once '../config/config.php';
include_once '../config/minda.php';
include_once '../sessions/session.php';
include_once 'FormFieldController.php';

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: ' . $base_url);

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['status' => 'error', 'message' => 'Only POST allowed']);
    exit;
}

if (!isLoggedIn()) {
    http_response_code(401);
    echo json_encode(['status' => 'error', 'message' => 'Unauthorized']);
    exit;
}

$data = json_decode(file_get_contents('php://input'), true);
if (!is_array($data)) {
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => 'Invalid request']);
    exit;
}

function validateFieldCsrfToken($token) {
    global $pdo;

    if (!isset($_SESSION['user_id'])) {
        return false;
    }

    try {
        $stmt = $pdo->prepare('SELECT token FROM accounts WHERE account_id = ?');
        $stmt->execute([$_SESSION['user_id']]);
        $dbToken = $stmt->fetchColumn();

        return is_string($dbToken) && hash_equals($dbToken, $token);
    } catch (PDOException $e) {
        error_log('Error validating CSRF token: ' . $e->getMessage());
        return false;
    }
}

$csrfToken = isset($data['csrf_token']) ? simpleDecode($data['csrf_token']) : '';
if (!validateFieldCsrfToken($csrfToken)) {
    http_response_code(403);
    echo json_encode(['status' => 'error', 'message' => 'Invalid CSRF token']);
    exit;
}

$action = $data['action'] ?? '';

try {
    if ($action === 'list') {
        echo json_encode(['status' => 'success', 'fields' => getFormFields(false)]);
        exit;
    }

    if ($action === 'add') {
        $label = trim((string) ($data['label'] ?? ''));
        if ($label === '') {
            echo json_encode(['status' => 'error', 'message' => 'Label is required']);
            exit;
        }

        $fieldType = ($data['field_type'] ?? 'text') === 'select' ? 'select' : 'text';
        $keyBase = sanitizeFieldKey($label);
        $fieldKey = $keyBase;
        $suffix = 2;

        while (true) {
            $stmt = $pdo->prepare('SELECT COUNT(*) FROM form_fields WHERE field_key = ?');
            $stmt->execute([$fieldKey]);
            if ((int) $stmt->fetchColumn() === 0) {
                break;
            }
            $fieldKey = substr($keyBase, 0, 58) . '_' . $suffix;
            $suffix++;
        }

        $stmt = $pdo->prepare('INSERT INTO form_fields (field_key, label, field_type, options, required, active, is_system, sort_order) VALUES (?, ?, ?, ?, ?, ?, 0, ?)');
        $stmt->execute([
            $fieldKey,
            $label,
            $fieldType,
            $fieldType === 'select' ? normalizeOptions($data['options'] ?? '') : null,
            !empty($data['required']) ? 1 : 0,
            !empty($data['active']) ? 1 : 0,
            (int) ($data['sort_order'] ?? 100),
        ]);

        echo json_encode(['status' => 'success', 'message' => 'Field added', 'fields' => getFormFields(false)]);
        exit;
    }

    if ($action === 'edit') {
        $id = (int) ($data['id'] ?? 0);
        $label = trim((string) ($data['label'] ?? ''));
        $fieldType = ($data['field_type'] ?? 'text') === 'select' ? 'select' : 'text';

        if ($id <= 0 || $label === '') {
            echo json_encode(['status' => 'error', 'message' => 'ID and label are required']);
            exit;
        }

        $stmt = $pdo->prepare('UPDATE form_fields SET label = ?, field_type = ?, options = ?, required = ?, active = ?, sort_order = ? WHERE field_id = ?');
        $stmt->execute([
            $label,
            $fieldType,
            $fieldType === 'select' ? normalizeOptions($data['options'] ?? '') : null,
            !empty($data['required']) ? 1 : 0,
            !empty($data['active']) ? 1 : 0,
            (int) ($data['sort_order'] ?? 100),
            $id,
        ]);

        echo json_encode(['status' => 'success', 'message' => 'Field updated', 'fields' => getFormFields(false)]);
        exit;
    }

    if ($action === 'delete') {
        $id = (int) ($data['id'] ?? 0);
        if ($id <= 0) {
            echo json_encode(['status' => 'error', 'message' => 'ID is required']);
            exit;
        }

        $stmt = $pdo->prepare('DELETE FROM form_fields WHERE field_id = ? AND is_system = 0');
        $stmt->execute([$id]);

        echo json_encode(['status' => 'success', 'message' => 'Field deleted', 'fields' => getFormFields(false)]);
        exit;
    }

    echo json_encode(['status' => 'error', 'message' => 'Invalid action']);
} catch (PDOException $e) {
    error_log('Form field API database error: ' . $e->getMessage());
    echo json_encode(['status' => 'error', 'message' => 'Database error']);
}

