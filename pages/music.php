<?php
require_once '../includes/config.php';

$type = trim($_GET['tipo'] ?? '');
$search = trim($_GET['q'] ?? '');
$typeSafe = db_escape($conn, $type);
$searchSafe = db_escape($conn, $search);
$perPage = 20;
$pageNumber = max(1, (int)($_GET['page'] ?? 1));

$where = "
    WHERE r.estado = 'aprovado'
      AND r.ativo = 1
      AND c.estado = 'ativo'
";

if ($type !== '' && in_array($type, ['Single', 'EP', 'Album'], true)) {
    $where .= " AND r.tipo = '{$typeSafe}'";
}
if ($search !== '') {
    $where .= " AND (
        r.titulo LIKE '%{$searchSafe}%'
        OR c.nome LIKE '%{$searchSafe}%'
        OR EXISTS (
            SELECT 1
            FROM faixa fs
            WHERE fs.idRelease = r.idRelease
              AND fs.estado = 'aprovada'
              AND fs.ativo = 1
              AND fs.titulo LIKE '%{$searchSafe}%'
        )
    )";
}

$totalReleases = (int)(db_one(
    $conn,
    "SELECT COUNT(*) AS total
     FROM release_musical r
     JOIN cliente c ON c.idCliente = r.idCliente
     {$where}"
)['total'] ?? 0);
$totalPages = max(1, (int)ceil($totalReleases / $perPage));
$pageNumber = min($pageNumber, $totalPages);
$offset = ($pageNumber - 1) * $perPage;

$releases = db_all(
    $conn,
    "SELECT
        r.idRelease,
        r.titulo AS release_titulo,
        r.tipo,
        r.capa,
        r.data_lancamento,
        c.idCliente AS artistId,
        c.nome AS artist_nome,
        c.foto AS artist_foto,
        COUNT(f.idFaixa) AS total_faixas,
        first_track.idFaixa AS first_track_id,
        first_track.titulo AS first_track_title,
        first_track.ficheiro_audio AS first_track_audio
     FROM release_musical r
     JOIN cliente c ON c.idCliente = r.idCliente
     LEFT JOIN faixa f
        ON f.idRelease = r.idRelease
       AND f.estado = 'aprovada'
       AND f.ativo = 1
     LEFT JOIN faixa first_track
        ON first_track.idFaixa = (
            SELECT f2.idFaixa
            FROM faixa f2
            WHERE f2.idRelease = r.idRelease
              AND f2.estado = 'aprovada'
              AND f2.ativo = 1
            ORDER BY f2.numero_faixa ASC
            LIMIT 1
        )
     {$where}
     GROUP BY r.idRelease, r.titulo, r.tipo, r.capa, r.data_lancamento, r.created_at, c.idCliente, c.nome, c.foto, first_track.idFaixa, first_track.titulo, first_track.ficheiro_audio
     ORDER BY COALESCE(r.data_lancamento, DATE(r.created_at)) DESC, r.idRelease DESC
     LIMIT {$perPage} OFFSET {$offset}"
);

$paginationQuery = [];
if ($search !== '') {
    $paginationQuery['q'] = $search;
}
if ($type !== '') {
    $paginationQuery['tipo'] = $type;
}
$pageUrl = static function (int $targetPage) use ($paginationQuery): string {
    return 'music.php?' . http_build_query($paginationQuery + ['page' => $targetPage]);
};

include '../includes/header.php';
?>

<section class="content-shell">
  <div class="wrap">
    <div class="catalog-hero">
      <div>
        <span class="slabel" data-t="music_label">Music catalog</span>
        <h1 data-t="music_title">Browse the catalog</h1>
      </div>

      <form method="get" class="catalog-filter">
        <input type="text" name="q" class="finput" value="<?= h($search) ?>" data-tp="music_search_placeholder" placeholder="Search track, release, or artist">
        <select name="tipo" class="finput">
          <option value="" data-t="music_all_formats">All formats</option>
          <option value="Single" <?= $type === 'Single' ? 'selected' : '' ?>>Single</option>
          <option value="EP" <?= $type === 'EP' ? 'selected' : '' ?>>EP</option>
          <option value="Album" <?= $type === 'Album' ? 'selected' : '' ?>>Album</option>
        </select>
        <button type="submit" class="btn btn-dark" data-t="music_filter">Filter</button>
      </form>
    </div>

    <?php if (!$releases): ?>
      <div class="card surface-card">
        <div class="card-body text-center">
          <p data-t="music_empty">No approved tracks matched your search.</p>
        </div>
      </div>
    <?php else: ?>
      <div class="grid stg">
        <?php foreach ($releases as $release): ?>
          <?php
          $cover = asset_url('img', $release['capa']);
          $audio = asset_url('audio', $release['first_track_audio']);
          $artistFoto = asset_url('img', $release['artist_foto']);
          $payload = [
              'id' => (int)$release['first_track_id'],
              'title' => $release['first_track_title'],
              'artist' => $release['artist_nome'],
              'cover' => $cover,
              'audio' => $audio,
              'artistId' => (int)$release['artistId'],
              'artistFoto' => $artistFoto,
              'type' => $release['tipo'],
              'releaseKey' => $release['idRelease'] . '-' . $release['artistId']
          ];
          ?>
          <a class="mcard" href="release.php?id=<?= (int)$release['idRelease'] ?>" data-track='<?= h(json_encode($payload)) ?>'>
            <div class="cover">
              <?php if ($cover): ?>
                <img src="<?= h($cover) ?>" alt="<?= h($release['release_titulo']) ?>">
              <?php endif; ?>
              <div class="cover-ov">
                <?php if (!empty($release['first_track_audio'])): ?>
                  <button type="button" class="pbt" data-t="release_play_track" onclick="event.preventDefault(); event.stopPropagation(); playTrack('<?= h(addslashes($release['first_track_title'])) ?>','<?= h(addslashes($release['artist_nome'])) ?>','<?= h($cover) ?>','<?= h($audio) ?>',<?= (int)$release['artistId'] ?>,'<?= h($artistFoto) ?>',<?= (int)$release['first_track_id'] ?>)">Play</button>
                <?php endif; ?>
              </div>
            </div>
            <div class="meta">
              <span class="badge badge-dark"><?= h($release['tipo']) ?></span>
              <h4><?= h($release['release_titulo']) ?></h4>
              <div class="sub"><?= h($release['artist_nome']) ?></div>
              <div class="sub"><?= (int)$release['total_faixas'] ?> <span data-t="release_tracks_count">faixas</span></div>
            </div>
          </a>
        <?php endforeach; ?>
      </div>

      <?php if ($totalPages > 1): ?>
        <nav class="pager" aria-label="Pagination">
          <?php if ($pageNumber > 1): ?>
            <a class="btn btn-ghost btn-sm" href="<?= h($pageUrl($pageNumber - 1)) ?>" data-t="pagination_previous">Anterior</a>
          <?php else: ?>
            <span class="btn btn-ghost btn-sm is-disabled" data-t="pagination_previous">Anterior</span>
          <?php endif; ?>
          <span class="pager-status">
            <span data-t="pagination_page">Pagina</span> <?= $pageNumber ?>
            <span data-t="pagination_of">de</span> <?= $totalPages ?>
          </span>
          <?php if ($pageNumber < $totalPages): ?>
            <a class="btn btn-ghost btn-sm" href="<?= h($pageUrl($pageNumber + 1)) ?>" data-t="pagination_next">Seguinte</a>
          <?php else: ?>
            <span class="btn btn-ghost btn-sm is-disabled" data-t="pagination_next">Seguinte</span>
          <?php endif; ?>
        </nav>
      <?php endif; ?>
    <?php endif; ?>
  </div>
</section>

<?php include '../includes/footer.php'; ?>
