<?php
header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/../includes/config.php';

if (!is_user_logged_in()) {
    http_response_code(401);
    echo json_encode(['error' => 'Nao autenticado'], JSON_UNESCAPED_UNICODE);
    exit;
}

$uid = current_user_id();
$action = $_GET['action'] ?? $_POST['action'] ?? '';

if ($action === 'get') {
    $rows = db_all(
        $conn,
        "SELECT
            fm.idFavorito,
            f.idFaixa AS idFaixa,
            f.titulo AS titulo,
            f.ficheiro_audio AS ficheiro_audio,
            r.idRelease AS idRelease,
            r.titulo AS releaseTitle,
            r.capa AS capa,
            r.tipo AS tipo,
            c.idCliente AS artistId,
            c.nome AS artist,
            c.foto AS artistFoto
         FROM favorito_musica fm
         JOIN faixa f ON f.idFaixa = fm.idFaixa
         JOIN release_musical r ON r.idRelease = f.idRelease
         JOIN cliente c ON c.idCliente = r.idCliente
         WHERE fm.idCliente = {$uid}
         ORDER BY fm.created_at DESC"
    );

    $result = [];
    foreach ($rows as $row) {
        $result[] = [
            'idMusica' => (int)$row['idFaixa'],
            'title' => $row['titulo'],
            'cover' => $row['capa'],
            'artist' => $row['artist'],
            'artistId' => (int)$row['artistId'],
            'artistFoto' => $row['artistFoto'],
            'audio' => $row['ficheiro_audio'],
            'releaseId' => (int)$row['idRelease'],
            'releaseTitle' => $row['releaseTitle'],
            'type' => $row['tipo']
        ];
    }

    echo json_encode($result, JSON_UNESCAPED_UNICODE);
    exit;
}

if ($action === 'add') {
    $trackId = (int)($_POST['musicId'] ?? 0);
    if ($trackId <= 0) {
        http_response_code(400);
        echo json_encode(['error' => 'Faixa invalida'], JSON_UNESCAPED_UNICODE);
        exit;
    }

    mysqli_query($conn, "INSERT IGNORE INTO favorito_musica (idCliente, idFaixa) VALUES ({$uid}, {$trackId})");
    echo json_encode(['status' => 'added'], JSON_UNESCAPED_UNICODE);
    exit;
}

if ($action === 'remove') {
    $trackId = (int)($_POST['musicId'] ?? 0);
    if ($trackId <= 0) {
        http_response_code(400);
        echo json_encode(['error' => 'Faixa invalida'], JSON_UNESCAPED_UNICODE);
        exit;
    }

    mysqli_query($conn, "DELETE FROM favorito_musica WHERE idCliente = {$uid} AND idFaixa = {$trackId}");
    echo json_encode(['status' => 'removed'], JSON_UNESCAPED_UNICODE);
    exit;
}

if ($action === 'check') {
    $trackId = (int)($_GET['musicId'] ?? 0);
    if ($trackId <= 0) {
        http_response_code(400);
        echo json_encode(['error' => 'Faixa invalida'], JSON_UNESCAPED_UNICODE);
        exit;
    }

    $exists = db_one($conn, "SELECT idFavorito FROM favorito_musica WHERE idCliente = {$uid} AND idFaixa = {$trackId} LIMIT 1");
    echo json_encode(['isFavorited' => (bool)$exists], JSON_UNESCAPED_UNICODE);
    exit;
}

http_response_code(400);
echo json_encode(['error' => 'Acao invalida'], JSON_UNESCAPED_UNICODE);
