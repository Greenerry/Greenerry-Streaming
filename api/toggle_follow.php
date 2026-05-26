<?php
header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/../includes/config.php';

if (!is_user_logged_in()) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => tr('error.api_unauthenticated')], JSON_UNESCAPED_UNICODE);
    exit;
}

if (!active_user_session($conn)) {
    end_user_session_only();
    http_response_code(403);
    echo json_encode(['success' => false, 'error' => tr('error.account_inactive')], JSON_UNESCAPED_UNICODE);
    exit;
}

$err = verify_csrf_request() ?? '';
$uid = current_user_id();
$artistId = (int)($_POST['artist_id'] ?? 0);

// Guard the relationship before changing anything: valid artist, not your own profile.
if (!$err && $artistId <= 0) {
    $err = tr('error.api_invalid_request');
}

if (!$err && $artistId === $uid) {
    $err = tr('error.api_forbidden');
}

if (!$err) {
    $artist = db_one($conn, "SELECT idCliente FROM cliente WHERE idCliente = {$artistId} AND estado = 'ativo' LIMIT 1");
    if (!$artist) {
        $err = tr('error.api_invalid_request');
    }
}

if ($err) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => $err], JSON_UNESCAPED_UNICODE);
    exit;
}

// One endpoint handles both states so the frontend can update instantly.
$existingFollow = db_one(
    $conn,
    "SELECT idSeguirArtista
     FROM seguir_artista
     WHERE idSeguidor = {$uid}
       AND idArtista = {$artistId}
     LIMIT 1"
);

if ($existingFollow) {
    mysqli_query(
        $conn,
        "DELETE FROM seguir_artista
         WHERE idSeguidor = {$uid}
           AND idArtista = {$artistId}"
    );
    $following = false;
} else {
    mysqli_query(
        $conn,
        "INSERT INTO seguir_artista (idSeguidor, idArtista)
         VALUES ({$uid}, {$artistId})"
    );
    $following = true;
}

// Return fresh counts so the page does not need a reload to stay accurate.
$followers = (int)(db_one($conn, "SELECT COUNT(*) AS total FROM seguir_artista WHERE idArtista = {$artistId}")['total'] ?? 0);
$followingCount = (int)(db_one($conn, "SELECT COUNT(*) AS total FROM seguir_artista WHERE idSeguidor = {$artistId}")['total'] ?? 0);

echo json_encode([
    'success' => true,
    'following' => $following,
    'followers' => $followers,
    'followingCount' => $followingCount,
    'message' => $following
        ? (current_lang() === 'en' ? 'You are now following this artist.' : 'Agora segues este artista.')
        : (current_lang() === 'en' ? 'You stopped following this artist.' : 'Deixaste de seguir este artista.'),
], JSON_UNESCAPED_UNICODE);
