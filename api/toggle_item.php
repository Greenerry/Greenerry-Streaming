<?php
header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/../includes/config.php';

if (!is_user_logged_in()) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => tr('error.api_unauthenticated')], JSON_UNESCAPED_UNICODE);
    exit;
}

$uid = current_user_id();
$type = $_POST['type'] ?? '';
$id = (int)($_POST['id'] ?? 0);

if ($id <= 0 || !in_array($type, ['music', 'merch'], true)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => tr('error.api_invalid_request')], JSON_UNESCAPED_UNICODE);
    exit;
}

if ($type === 'music') {
    $row = db_one(
        $conn,
        "SELECT r.idRelease, r.idCliente, r.ativo, r.estado, r.bloqueado_admin
         FROM release_musical r
         WHERE r.idRelease = {$id}
         LIMIT 1"
    );

    if (!$row || (int)$row['idCliente'] !== $uid || !in_array($row['estado'], ['aprovado', 'inativo'], true)) {
        http_response_code(403);
        echo json_encode(['success' => false, 'error' => tr('error.api_forbidden')], JSON_UNESCAPED_UNICODE);
        exit;
    }

    if ((int)($row['bloqueado_admin'] ?? 0) === 1) {
        http_response_code(403);
        echo json_encode(['success' => false, 'error' => tr('error.admin_blocked_item')], JSON_UNESCAPED_UNICODE);
        exit;
    }

    $newActive = (int)$row['ativo'] === 1 ? 0 : 1;
    $newState = $newActive === 1 ? 'aprovado' : 'inativo';

    mysqli_query($conn, "UPDATE release_musical SET ativo = {$newActive}, estado = '{$newState}' WHERE idRelease = {$id}");
    mysqli_query($conn, "UPDATE faixa SET ativo = {$newActive}, estado = '" . ($newActive === 1 ? 'aprovada' : 'inativa') . "' WHERE idRelease = {$id}");

    echo json_encode(['success' => true, 'enabled' => $newActive], JSON_UNESCAPED_UNICODE);
    exit;
}

$row = db_one(
    $conn,
    "SELECT idProduto, idCliente, ativo, estado, bloqueado_admin
     FROM produto
     WHERE idProduto = {$id}
     LIMIT 1"
);

if (!$row || (int)$row['idCliente'] !== $uid || !in_array($row['estado'], ['aprovado', 'inativo'], true)) {
    http_response_code(403);
    echo json_encode(['success' => false, 'error' => tr('error.api_forbidden')], JSON_UNESCAPED_UNICODE);
    exit;
}

if ((int)($row['bloqueado_admin'] ?? 0) === 1) {
    http_response_code(403);
    echo json_encode(['success' => false, 'error' => tr('error.admin_blocked_item')], JSON_UNESCAPED_UNICODE);
    exit;
}

$newActive = (int)$row['ativo'] === 1 ? 0 : 1;
$newState = $newActive === 1 ? 'aprovado' : 'inativo';
mysqli_query($conn, "UPDATE produto SET ativo = {$newActive}, estado = '{$newState}' WHERE idProduto = {$id}");

echo json_encode(['success' => true, 'enabled' => $newActive], JSON_UNESCAPED_UNICODE);
