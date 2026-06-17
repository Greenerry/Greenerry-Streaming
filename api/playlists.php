<?php
// API purpose: Creates and updates user playlists.
// Keep this endpoint simple: validate input, perform the action, then return JSON.
header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/../includes/config.php';

if (!is_user_logged_in() || !active_user_session($conn)) {
    http_response_code(401);
    echo json_encode(['error' => tr('error.api_unauthenticated')], JSON_UNESCAPED_UNICODE);
    exit;
}

$uid = current_user_id();
$action = $_GET['action'] ?? $_POST['action'] ?? 'list';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && !verify_csrf_token($_POST['csrf_token'] ?? $_SERVER['HTTP_X_CSRF_TOKEN'] ?? null)) {
    http_response_code(403);
    echo json_encode(['error' => tr('error.invalid_session')], JSON_UNESCAPED_UNICODE);
    exit;
}

if ($action === 'list') {
    $trackId = (int)($_GET['trackId'] ?? 0);
    $playlists = db_all_prepared(
        $conn,
        "SELECT p.idPlaylist, p.nome, p.descricao, p.capa, COUNT(pf.idPlaylistFaixa) AS total_faixas" . ($trackId > 0 ? ",
                MAX(CASE WHEN pf.idFaixa = ? THEN 1 ELSE 0 END) AS in_playlist" : ",
                0 AS in_playlist") . "
         FROM playlist p
         LEFT JOIN playlist_faixa pf ON pf.idPlaylist = p.idPlaylist
         WHERE p.idCliente = ?
         GROUP BY p.idPlaylist, p.nome, p.descricao, p.capa
         ORDER BY p.atualizado_em DESC",
        $trackId > 0 ? 'ii' : 'i',
        $trackId > 0 ? [$trackId, $uid] : [$uid]
    );

    echo json_encode(['playlists' => $playlists], JSON_UNESCAPED_UNICODE);
    exit;
}

if ($action === 'create') {
    $name = trim((string)($_POST['name'] ?? ''));
    if ($name === '' || mb_strlen($name) > 140) {
        http_response_code(400);
        echo json_encode(['error' => tr('error.api_invalid_request')], JSON_UNESCAPED_UNICODE);
        exit;
    }

    $cover = '';
    if (!empty($_FILES['cover']) && ($_FILES['cover']['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_NO_FILE) {
        $imageErr = validate_uploaded_image($_FILES['cover']);
        if ($imageErr) {
            http_response_code(400);
            echo json_encode(['error' => $imageErr], JSON_UNESCAPED_UNICODE);
            exit;
        }

        [$cover, $saveErr] = save_uploaded_file($_FILES['cover'], 'img', 'playlist_' . $uid, ['jpg', 'jpeg', 'png', 'webp'], GREENERRY_MAX_IMAGE_BYTES);
        if ($saveErr) {
            http_response_code(400);
            echo json_encode(['error' => $saveErr], JSON_UNESCAPED_UNICODE);
            exit;
        }
    }

    db_prepared($conn, "INSERT INTO playlist (idCliente, nome, capa) VALUES (?, ?, ?)", 'iss', [$uid, $name, $cover ?: null]);
    echo json_encode(['success' => true, 'idPlaylist' => mysqli_insert_id($conn), 'cover' => $cover], JSON_UNESCAPED_UNICODE);
    exit;
}

if ($action === 'add_track') {
    $playlistId = (int)($_POST['playlistId'] ?? 0);
    $trackId = (int)($_POST['trackId'] ?? 0);
    $playlist = db_one_prepared($conn, "SELECT idPlaylist FROM playlist WHERE idPlaylist = ? AND idCliente = ? LIMIT 1", 'ii', [$playlistId, $uid]);
    $track = db_one_prepared(
        $conn,
        "SELECT f.idFaixa
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

    if (!$playlist || !$track) {
        http_response_code(404);
        echo json_encode(['error' => tr('error.api_invalid_request')], JSON_UNESCAPED_UNICODE);
        exit;
    }

    $nextOrder = (int)(db_one_prepared(
        $conn,
        "SELECT COALESCE(MAX(ordem), 0) + 1 AS next_order FROM playlist_faixa WHERE idPlaylist = ?",
        'i',
        [$playlistId]
    )['next_order'] ?? 1);
    db_prepared($conn, "INSERT IGNORE INTO playlist_faixa (idPlaylist, idFaixa, ordem) VALUES (?, ?, ?)", 'iii', [$playlistId, $trackId, $nextOrder]);
    db_prepared($conn, "UPDATE playlist SET atualizado_em = NOW() WHERE idPlaylist = ? AND idCliente = ?", 'ii', [$playlistId, $uid]);
    echo json_encode(['success' => true], JSON_UNESCAPED_UNICODE);
    exit;
}

if ($action === 'remove_track') {
    $playlistId = (int)($_POST['playlistId'] ?? 0);
    $trackId = (int)($_POST['trackId'] ?? 0);
    db_prepared(
        $conn,
        "DELETE pf
         FROM playlist_faixa pf
         JOIN playlist p ON p.idPlaylist = pf.idPlaylist
         WHERE pf.idPlaylist = ?
           AND pf.idFaixa = ?
           AND p.idCliente = ?",
        'iii',
        [$playlistId, $trackId, $uid]
    );
    echo json_encode(['success' => true], JSON_UNESCAPED_UNICODE);
    exit;
}

if ($action === 'delete') {
    $playlistId = (int)($_POST['playlistId'] ?? 0);
    db_prepared($conn, "DELETE FROM playlist WHERE idPlaylist = ? AND idCliente = ?", 'ii', [$playlistId, $uid]);
    echo json_encode(['success' => true], JSON_UNESCAPED_UNICODE);
    exit;
}

http_response_code(400);
echo json_encode(['error' => tr('error.api_invalid_request')], JSON_UNESCAPED_UNICODE);
