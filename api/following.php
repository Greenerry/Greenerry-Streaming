<?php
header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/../includes/config.php';

if (!is_user_logged_in()) {
    http_response_code(401);
    echo json_encode(['error' => tr('error.api_unauthenticated')], JSON_UNESCAPED_UNICODE);
    exit;
}

$uid = current_user_id();
$page = max(1, (int)($_GET['page'] ?? 1));
$perPage = max(1, min(24, (int)($_GET['perPage'] ?? 5)));
$search = trim((string)($_GET['q'] ?? ''));
$searchSafe = db_escape($conn, $search);
$searchSql = $search !== ''
    ? "AND (c.nome LIKE '%{$searchSafe}%' OR c.bio LIKE '%{$searchSafe}%')"
    : '';

$total = (int)(db_one(
    $conn,
    "SELECT COUNT(*) AS total
     FROM seguir_artista sa
     JOIN cliente c ON c.idCliente = sa.idArtista
     WHERE sa.idSeguidor = {$uid}
       AND c.estado = 'ativo'
       {$searchSql}"
)['total'] ?? 0);

$totalPages = max(1, (int)ceil($total / $perPage));
$page = min($page, $totalPages);
$offset = ($page - 1) * $perPage;

$rows = db_all(
    $conn,
    "SELECT
        c.idCliente,
        c.nome,
        c.bio,
        c.foto,
        c.banner,
        c.slug,
        COUNT(DISTINCT r.idRelease) AS total_releases,
        COUNT(DISTINCT f.idFaixa) AS total_faixas
     FROM seguir_artista sa
     JOIN cliente c
       ON c.idCliente = sa.idArtista
     LEFT JOIN release_musical r
       ON r.idCliente = c.idCliente
      AND r.estado = 'aprovado'
      AND r.ativo = 1
     LEFT JOIN faixa f
       ON f.idRelease = r.idRelease
      AND f.estado = 'aprovada'
      AND f.ativo = 1
     WHERE sa.idSeguidor = {$uid}
       AND c.estado = 'ativo'
       {$searchSql}
     GROUP BY c.idCliente, c.nome, c.bio, c.foto, c.banner, c.slug, sa.created_at
     ORDER BY sa.created_at DESC
     LIMIT {$perPage} OFFSET {$offset}"
);

$artists = array_map(static function (array $row): array {
    return [
        'idCliente' => (int)$row['idCliente'],
        'nome' => $row['nome'],
        'bio' => $row['bio'],
        'foto' => $row['foto'],
        'banner' => $row['banner'],
        'slug' => $row['slug'],
        'total_releases' => (int)$row['total_releases'],
        'total_faixas' => (int)$row['total_faixas']
    ];
}, $rows);

echo json_encode([
    'artists' => $artists,
    'total' => $total,
    'page' => $page,
    'perPage' => $perPage,
    'totalPages' => $totalPages,
    'query' => $search
], JSON_UNESCAPED_UNICODE);
