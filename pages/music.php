<?php
require_once '../includes/config.php';

// Filters arrive through the URL, for example: music.php?q=rock&tipo=EP&page=2
$type = trim($_GET['tipo'] ?? '');
$search = trim($_GET['q'] ?? '');
$perPage = 18;
$pageNumber = max(1, (int)($_GET['page'] ?? 1));

$whereParts = ["r.estado = 'aprovado'", 'r.ativo = 1', "c.estado = 'ativo'"];
$types = '';
$params = [];

if ($type !== '' && in_array($type, ['Single', 'EP', 'Album'], true)) {
    // Only show one release type when the user selected Single, EP, or Album.
    $whereParts[] = 'r.tipo = ?';
    $types .= 's';
    $params[] = $type;
}
if ($search !== '') {
    // Search checks release title, artist name, and approved track titles.
    $searchLike = '%' . $search . '%';
    $whereParts[] = "(
        r.titulo LIKE ?
        OR c.nome LIKE ?
        OR EXISTS (
            SELECT 1
            FROM faixa fs
            WHERE fs.idRelease = r.idRelease
              AND fs.estado = 'aprovada'
              AND fs.ativo = 1
              AND fs.titulo LIKE ?
        )
    )";
    $types .= 'sss';
    array_push($params, $searchLike, $searchLike, $searchLike);
}
$where = 'WHERE ' . implode(' AND ', $whereParts);

$totalReleases = (int)(db_one_prepared(
    $conn,
    "SELECT COUNT(*) AS total
     FROM release_musical r
     JOIN cliente c ON c.idCliente = r.idCliente
     {$where}",
    $types,
    $params
)['total'] ?? 0);
$totalPages = max(1, (int)ceil($totalReleases / $perPage));
$pageNumber = min($pageNumber, $totalPages);
$offset = ($pageNumber - 1) * $perPage;

// Main catalog query. It also finds the first playable track for each release card.
$releases = db_all_prepared(
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
        first_track.genero AS first_track_genre,
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
     GROUP BY r.idRelease, r.titulo, r.tipo, r.capa, r.data_lancamento, r.criado_em, c.idCliente, c.nome, c.foto, first_track.idFaixa, first_track.titulo, first_track.genero, first_track.ficheiro_audio
     ORDER BY COALESCE(r.data_lancamento, DATE(r.criado_em)) DESC, r.idRelease DESC
     LIMIT {$perPage} OFFSET {$offset}",
    $types,
    $params
);

$musicMediaCloud = [];
foreach ($releases as $release) {
    // The media cloud uses real cover images from the current result set.
    $cover = asset_url('img', $release['capa']);
    if ($cover !== '') {
        $musicMediaCloud[$cover] = [
            'src' => $cover,
            'label' => (string)$release['release_titulo'],
            'type' => 'music',
        ];
    }
}
$musicMediaCloud = array_values(array_slice($musicMediaCloud, 0, 12));

$paginationQuery = [];
if ($search !== '') {
    $paginationQuery['q'] = $search;
}
if ($type !== '') {
    $paginationQuery['tipo'] = $type;
}
$pageUrl = static function (int $targetPage) use ($paginationQuery): string {
    // Keeps the current filters when the user changes page.
    return 'music.php?' . http_build_query($paginationQuery + ['page' => $targetPage]);
};

include '../includes/header.php';
?>

<section class="content-shell content-shell--cloud content-shell--catalog-cloud content-shell--music-page-cloud">
  <div class="section-media-cloud section-media-cloud--catalog section-media-cloud--music-page" data-media-cloud='<?= h(json_encode($musicMediaCloud, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE)) ?>' aria-hidden="true"></div>
  <div class="wrap">
    <div class="catalog-hero">
      <div>
        <span class="slabel" data-t="music_label">Music catalog</span>
        <h1 data-t="music_title">Discover music</h1>
      </div>

      <form method="get" class="catalog-filter" data-instant-filter>
        <input type="text" name="q" class="finput" value="<?= h($search) ?>" data-tp="music_search_placeholder" placeholder="Search track, release, or artist" autocomplete="off">
        <select name="tipo" class="finput">
          <option value="" data-t="music_all_formats">All formats</option>
          <option value="Single" data-t="release_type_single" <?= $type === 'Single' ? 'selected' : '' ?>><?= h(release_type_label('Single')) ?></option>
          <option value="EP" data-t="release_type_ep" <?= $type === 'EP' ? 'selected' : '' ?>><?= h(release_type_label('EP')) ?></option>
          <option value="Album" data-t="release_type_album" <?= $type === 'Album' ? 'selected' : '' ?>><?= h(release_type_label('Album')) ?></option>
        </select>
      </form>
    </div>

    <div data-catalog-results>
    <?php if (!$releases): ?>
      <div class="card surface-card catalog-empty-state">
        <div class="card-body text-center">
          <p data-t="music_empty">No approved tracks matched your search.</p>
        </div>
      </div>
    <?php else: ?>
      <div class="grid stg music-catalog-grid">
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
              <span class="badge badge-dark" data-release-type="<?= h($release['tipo']) ?>"><?= h(release_type_label($release['tipo'])) ?></span>
              <h4><?= h($release['release_titulo']) ?></h4>
              <div class="sub"><?= h($release['artist_nome']) ?></div>
              <?php if (!empty($release['first_track_genre'])): ?>
                <div class="sub"><?= h($release['first_track_genre']) ?></div>
              <?php endif; ?>
              <div class="sub" data-count-type="track" data-count-value="<?= (int)$release['total_faixas'] ?>"><?= h(count_label((int)$release['total_faixas'], 'track')) ?></div>
              <?php if (is_user_logged_in() && !empty($release['first_track_id'])): ?>
                <button type="button" class="btn btn-ghost btn-sm playlist-add-trigger" data-track-id="<?= (int)$release['first_track_id'] ?>" onclick="event.preventDefault(); event.stopPropagation(); openPlaylistPicker(this)" data-t="playlist_add">Adicionar à playlist</button>
              <?php endif; ?>
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
  </div>
</section>

<?php if (is_user_logged_in()): ?>
<dialog class="playlist-picker-dialog" id="playlist-picker">
  <form method="dialog" class="playlist-picker-card">
    <div class="playlist-picker-head">
      <h3 data-t="playlist_add_title">Adicionar à playlist</h3>
      <button type="submit" class="playlist-picker-close" aria-label="Close">×</button>
    </div>
    <label class="playlist-picker-search">
      <svg width="20" height="20" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><circle cx="11" cy="11" r="8"/><path d="m21 21-4.3-4.3"/></svg>
      <input type="search" id="playlist-picker-search" data-tp="playlist_find_placeholder" placeholder="Encontrar playlist">
    </label>
    <button type="button" class="playlist-new-row" id="playlist-picker-new-toggle">
      <span>+</span>
      <strong data-t="playlist_new">Nova playlist</strong>
    </button>
    <div class="playlist-create-inline" id="playlist-picker-create-row" hidden>
      <input type="text" id="playlist-picker-new" maxlength="140" data-tp="playlist_create_placeholder" placeholder="Nova playlist">
      <button type="button" id="playlist-picker-create" data-t="playlist_create">Criar</button>
    </div>
    <p class="playlist-picker-label" data-t="playlist_saved_in">Guardada em</p>
    <div id="playlist-picker-list" class="playlist-picker-list"></div>
    <div class="playlist-picker-actions">
      <button type="submit" class="playlist-cancel" data-t="cancel">Cancelar</button>
    </div>
  </form>
</dialog>
<?php endif; ?>

<?php include '../includes/footer.php'; ?>
