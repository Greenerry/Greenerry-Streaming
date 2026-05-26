<?php
header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/../includes/config.php';

if (!is_admin_logged_in()) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => tr('error.api_unauthenticated')], JSON_UNESCAPED_UNICODE);
    exit;
}

if (!verify_csrf_token($_POST['csrf_token'] ?? $_SERVER['HTTP_X_CSRF_TOKEN'] ?? null)) {
    http_response_code(403);
    echo json_encode(['success' => false, 'error' => tr('error.invalid_session')], JSON_UNESCAPED_UNICODE);
    exit;
}

$adminId = current_admin_id();
$type = (string)($_POST['item_type'] ?? '');
$id = (int)($_POST['item_id'] ?? 0);
$action = (string)($_POST['action'] ?? '');
$reason = trim((string)($_POST['reason'] ?? ''));
$reasonSafe = db_escape($conn, $reason);

if ($type === '' && isset($_POST['product_id'])) {
    // Accept old form field names and the newer generic AJAX payload.
    $type = 'product';
    $id = (int)$_POST['product_id'];
} elseif ($type === '' && isset($_POST['release_id'])) {
    $type = 'release';
    $id = (int)$_POST['release_id'];
}

if ($id <= 0 || !in_array($type, ['product', 'release'], true) || !in_array($action, ['aprovar', 'rejeitar', 'inativar', 'reativar'], true)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => tr('error.api_invalid_request')], JSON_UNESCAPED_UNICODE);
    exit;
}

$permission = $type === 'product' ? 'products' : 'releases';
if (!admin_can($permission)) {
    http_response_code(403);
    echo json_encode(['success' => false, 'error' => tr('error.api_forbidden')], JSON_UNESCAPED_UNICODE);
    exit;
}

$message = '';
$state = '';
$previousState = '';

if ($type === 'product') {
    // Product reviews only change the product row and then notify the artist.
    $productActionRow = db_one($conn, "SELECT estado FROM produto WHERE idProduto = {$id} LIMIT 1");
    if (!$productActionRow) {
        http_response_code(404);
        echo json_encode(['success' => false, 'error' => tr('error.api_invalid_request')], JSON_UNESCAPED_UNICODE);
        exit;
    }
    $previousState = (string)($productActionRow['estado'] ?? '');

    if ($action === 'aprovar') {
        mysqli_query($conn, "UPDATE produto SET estado = 'aprovado', motivo_rejeicao = NULL, idAdminAprovacao = {$adminId}, aprovado_em = NOW(), ativo = 1 WHERE idProduto = {$id}");
        send_product_review_email($conn, $id, $action);
        notify_product_review($conn, $id, $action);
        $message = tr('success.product_approved');
        $state = 'aprovado';
    } elseif ($action === 'rejeitar') {
        mysqli_query($conn, "UPDATE produto SET estado = 'rejeitado', motivo_rejeicao = '{$reasonSafe}', idAdminAprovacao = {$adminId}, aprovado_em = NOW(), ativo = 0 WHERE idProduto = {$id}");
        send_product_review_email($conn, $id, $action, $reason);
        notify_product_review($conn, $id, $action, $reason);
        $message = tr('success.product_rejected');
        $state = 'rejeitado';
    } elseif ($action === 'inativar') {
        mysqli_query($conn, "UPDATE produto SET estado = 'inativo', ativo = 0, bloqueado_admin = 1 WHERE idProduto = {$id}");
        notify_product_review($conn, $id, $action);
        $message = tr('success.product_deactivated');
        $state = 'inativo';
    } else {
        mysqli_query($conn, "UPDATE produto SET estado = 'aprovado', ativo = 1, bloqueado_admin = 0 WHERE idProduto = {$id}");
        notify_product_review($conn, $id, $action);
        $message = tr('success.product_reactivated');
        $state = 'aprovado';
    }
} else {
    // Release reviews also update tracks because public playback depends on both states.
    $releaseActionRow = db_one($conn, "SELECT estado FROM release_musical WHERE idRelease = {$id} LIMIT 1");
    if (!$releaseActionRow) {
        http_response_code(404);
        echo json_encode(['success' => false, 'error' => tr('error.api_invalid_request')], JSON_UNESCAPED_UNICODE);
        exit;
    }
    $currentReleaseState = (string)($releaseActionRow['estado'] ?? '');
    $previousState = $currentReleaseState;

    if ($action === 'aprovar') {
        mysqli_query($conn, "UPDATE release_musical SET estado = 'aprovado', motivo_rejeicao = NULL, idAdminAprovacao = {$adminId}, aprovado_em = NOW(), ativo = 1 WHERE idRelease = {$id}");
        mysqli_query($conn, "UPDATE faixa SET estado = 'aprovada', ativo = 1 WHERE idRelease = {$id}");
        send_release_review_email($conn, $id, $action);
        notify_release_review($conn, $id, $action);
        $message = tr('success.release_approved');
        $state = 'aprovado';
    } elseif ($action === 'rejeitar') {
        $tracksToDelete = db_all($conn, "SELECT ficheiro_audio FROM faixa WHERE idRelease = {$id}");
        $audioFilesToDelete = array_map(static fn($track) => (string)($track['ficheiro_audio'] ?? ''), $tracksToDelete);

        // Rejected releases lose playable audio until the artist resubmits files.
        mysqli_query($conn, "UPDATE release_musical SET estado = 'rejeitado', motivo_rejeicao = '{$reasonSafe}', idAdminAprovacao = {$adminId}, aprovado_em = NOW(), ativo = 0 WHERE idRelease = {$id}");
        mysqli_query($conn, "UPDATE faixa SET estado = 'rejeitada', ativo = 0, ficheiro_audio = '' WHERE idRelease = {$id}");
        delete_orphan_asset_files($conn, 'audio', $audioFilesToDelete);
        cleanup_unused_uploaded_assets($conn);
        send_release_review_email($conn, $id, $action, $reason);
        notify_release_review($conn, $id, $action, $reason);
        $message = tr('success.release_rejected');
        $state = 'rejeitado';
    } elseif ($action === 'inativar') {
        mysqli_query($conn, "UPDATE release_musical SET estado = 'inativo', ativo = 0, bloqueado_admin = 1 WHERE idRelease = {$id}");
        mysqli_query($conn, "UPDATE faixa SET estado = 'inativa', ativo = 0 WHERE idRelease = {$id}");
        notify_release_review($conn, $id, $action);
        $message = tr('success.release_deactivated');
        $state = 'inativo';
    } elseif ($currentReleaseState !== 'rejeitado') {
        mysqli_query($conn, "UPDATE release_musical SET estado = 'aprovado', ativo = 1, bloqueado_admin = 0 WHERE idRelease = {$id}");
        mysqli_query($conn, "UPDATE faixa SET estado = 'aprovada', ativo = 1 WHERE idRelease = {$id}");
        notify_release_review($conn, $id, $action);
        $message = tr('success.release_reactivated');
        $state = 'aprovado';
    } else {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => tr('error.api_invalid_request')], JSON_UNESCAPED_UNICODE);
        exit;
    }
}

echo json_encode([
    'success' => true,
    'message' => $message,
    'type' => $type,
    'id' => $id,
    'state' => $state,
    'previousState' => $previousState,
    'stateLabel' => order_status_label($state),
    'badgeClass' => state_badge_class($state),
    'action' => $action,
], JSON_UNESCAPED_UNICODE);
