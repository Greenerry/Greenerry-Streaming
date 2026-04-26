<?php
header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/../includes/config.php';

$tracks = db_all(
    $conn,
    "SELECT
        f.idFaixa AS id,
        f.titulo AS title,
        f.ficheiro_audio AS audio,
        r.capa AS cover,
        r.tipo AS type,
        r.idRelease AS releaseId,
        r.titulo AS releaseTitle,
        c.idCliente AS artistId,
        c.nome AS artist,
        c.foto AS artistFoto,
        CONCAT(r.idRelease, '-', c.idCliente) AS releaseKey
     FROM faixa f
     JOIN release_musical r ON r.idRelease = f.idRelease
     JOIN cliente c ON c.idCliente = r.idCliente
     WHERE r.estado = 'aprovado'
       AND r.ativo = 1
       AND f.estado = 'aprovada'
       AND f.ativo = 1
       AND c.estado = 'ativo'
     ORDER BY COALESCE(r.data_lancamento, DATE(r.created_at)) DESC, r.idRelease DESC, f.numero_faixa ASC"
);

echo json_encode($tracks, JSON_UNESCAPED_UNICODE);
