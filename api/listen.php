<?php
// API purpose: Records listening events for reporting.
// Keep this endpoint simple: validate input, perform the action, then return JSON.
header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/../includes/config.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => tr('error.api_invalid_request')], JSON_UNESCAPED_UNICODE);
    exit;
}

if (!verify_csrf_token($_POST['csrf_token'] ?? $_SERVER['HTTP_X_CSRF_TOKEN'] ?? null)) {
    http_response_code(403);
    echo json_encode(['error' => tr('error.invalid_session')], JSON_UNESCAPED_UNICODE);
    exit;
}

$trackId = (int)($_POST['trackId'] ?? 0);
$seconds = max(0, min(3600, (int)($_POST['seconds'] ?? 0)));
$track = db_one_prepared(
    $conn,
    "SELECT f.idFaixa, r.idCliente AS idArtista
     FROM faixa f
     JOIN release_musical r ON r.idRelease = f.idRelease
     JOIN cliente c ON c.idCliente = r.idCliente
     WHERE f.idFaixa = ?
       AND f.estado = 'aprovada'
       AND f.ativo = 1
       AND r.estado = 'aprovado'
       AND r.ativo = 1
       AND c.estado = 'ativo'
     LIMIT 1",
    'i',
    [$trackId]
);

if (!$track) {
    http_response_code(404);
    echo json_encode(['error' => tr('error.api_invalid_request')], JSON_UNESCAPED_UNICODE);
    exit;
}

$userId = is_user_logged_in() && active_user_session($conn) ? current_user_id() : null;
db_prepared(
    $conn,
    "INSERT INTO faixa_listen (idFaixa, idCliente, idArtista, segundos_ouvidos)
     VALUES (?, ?, ?, ?)",
    'iiii',
    [$trackId, $userId, (int)$track['idArtista'], $seconds]
);

echo json_encode(['success' => true], JSON_UNESCAPED_UNICODE);
