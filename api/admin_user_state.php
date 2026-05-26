<?php
header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/../includes/config.php';

if (!is_admin_logged_in()) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => tr('error.api_unauthenticated')], JSON_UNESCAPED_UNICODE);
    exit;
}

if (!admin_can('users')) {
    http_response_code(403);
    echo json_encode(['success' => false, 'error' => tr('error.api_forbidden')], JSON_UNESCAPED_UNICODE);
    exit;
}

if (!verify_csrf_token($_POST['csrf_token'] ?? $_SERVER['HTTP_X_CSRF_TOKEN'] ?? null)) {
    http_response_code(403);
    echo json_encode(['success' => false, 'error' => tr('error.invalid_session')], JSON_UNESCAPED_UNICODE);
    exit;
}

$userId = (int)($_POST['user_id'] ?? 0);
$state = (string)($_POST['estado'] ?? '');
$allowedStates = ['ativo', 'inativo', 'bloqueado'];

if ($userId <= 0 || !in_array($state, $allowedStates, true)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => tr('error.api_invalid_request')], JSON_UNESCAPED_UNICODE);
    exit;
}

$current = db_one_prepared($conn, "SELECT estado FROM cliente WHERE idCliente = ? LIMIT 1", 'i', [$userId]);
if (!$current) {
    http_response_code(404);
    echo json_encode(['success' => false, 'error' => tr('error.api_invalid_request')], JSON_UNESCAPED_UNICODE);
    exit;
}

if (!db_prepared($conn, "UPDATE cliente SET estado = ? WHERE idCliente = ?", 'si', [$state, $userId])) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => tr('error.user_update')], JSON_UNESCAPED_UNICODE);
    exit;
}

echo json_encode([
    'success' => true,
    'message' => tr('success.user_state_updated'),
    'id' => $userId,
    'state' => $state,
    'previousState' => (string)$current['estado'],
    'stateLabel' => order_status_label($state),
    'badgeClass' => state_badge_class($state),
], JSON_UNESCAPED_UNICODE);
