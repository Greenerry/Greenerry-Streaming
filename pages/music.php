<?php
// Page purpose: Shows the music catalogue and player-ready track data.
// Keep this page simple: prepare data first, then render the view.
require_once '../includes/config.php';

// Filters arrive through the URL, for example: music.php?q=rock&tipo=EP&page=2
$type = trim($_GET['tipo'] ?? '');
$genre = trim($_GET['genero'] ?? '');
$search = trim($_GET['q'] ?? '');
$perPage = 18;
$pageNumber = max(1, (int)($_GET['page'] ?? 1));

$genreOptions = db_all(
    $conn,
    "SELECT DISTINCT g.nome AS genero
     FROM genero g
     JOIN faixa f ON f.idGenero = g.idGenero
     WHERE g.estado = 'ativo'
       AND f.estado = 'aprovada'
       AND f.ativo = 1
     ORDER BY g.nome ASC"
);
$validGenres = array_map(static fn($row) => (string)$row['genero'], $genreOptions);
if ($genre !== '' && !in_array($genre, $validGenres, true)) {
    $genre = '';
}

$whereParts = ["r.estado = 'aprovado'", 'r.ativo = 1', "c.estado = 'ativo'"];
$types = '';
$params = [];

if ($type !== '' && in_array($type, ['Single', 'EP', 'Album'], true)) {
    // Only show one release type when the user selected Single, EP, or Album.
    $whereParts[] = 'r.tipo = ?';
    $types .= 's';
    $params[] = $type;
}
if ($genre !== '') {
    $whereParts[] = "EXISTS (
        SELECT 1
        FROM faixa fg
        WHERE fg.idRelease = r.idRelease
          AND fg.estado = 'aprovada'
          AND fg.ativo = 1
          AND fg.idGenero = (
              SELECT g.idGenero
              FROM genero g
              WHERE g.nome = ?
              LIMIT 1
          )
    )";
    $types .= 's';
    $params[] = $genre;
}
if ($search !== '') {
    // Search checks release title, artist name, and approved track titles.
    $searchLike = '%' . $search . '%';
    $whereParts[] = "(
        r.titulo LIKE ?
        OR c.nome LIKE ?
        OR EXISTS (
            SELECT 1
            FROM faixa fsg
            WHERE fsg.idRelease = r.idRelease
              AND fsg.estado = 'aprovada'
              AND fsg.ativo = 1
              AND COALESCE((
                  SELECT g.nome
                  FROM genero g
                  WHERE g.idGenero = fsg.idGenero
                  LIMIT 1
              ), fsg.genero) LIKE ?
        )
        OR EXISTS (
            SELECT 1
            FROM faixa fs
            WHERE fs.idRelease = r.idRelease
              AND fs.estado = 'aprovada'
              AND fs.ativo = 1
              AND fs.titulo LIKE ?
        )
    )";
    $types .= 'ssss';
    array_push($params, $searchLike, $searchLike, $searchLike, $searchLike);
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
        COALESCE(first_track_genre.nome, first_track.genero) AS first_track_genre,
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
     LEFT JOIN genero first_track_genre
        ON first_track_genre.idGenero = first_track.idGenero
     {$where}
     GROUP BY r.idRelease, r.titulo, r.tipo, r.capa, r.data_lancamento, r.criado_em, c.idCliente, c.nome, c.foto, first_track.idFaixa, first_track.titulo, first_track_genre.nome, first_track.genero, first_track.ficheiro_audio
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
if ($genre !== '') {
    $paginationQuery['genero'] = $genre;
}
$pageUrl = static function (int $targetPage) use ($paginationQuery): string {
    // Keeps the current filters when the user changes page.
    return 'music.php?' . http_build_query($paginationQuery + ['page' => $targetPage]);
};

include '../includes/header.php';
?>

<style>
@media (min-width: 901px) {
  .content-shell--music-page-cloud .catalog-hero {
    display: grid !important;
    grid-template-columns: minmax(0, 1fr) minmax(500px, 540px) !important;
    align-items: end !important;
    gap: 24px !important;
    margin-bottom: 24px !important;
  }

  .content-shell--music-page-cloud .catalog-hero h1 {
    max-width: none !important;
    white-space: nowrap !important;
    font-size: clamp(3.35rem, 4.25vw, 4rem) !important;
    line-height: .98 !important;
    margin: 0 !important;
  }

  .content-shell--music-page-cloud .catalog-filter {
    width: 100% !important;
    max-width: 540px !important;
    justify-self: end !important;
    display: grid !important;
    grid-template-columns: minmax(180px, 1fr) 126px 126px !important;
    align-items: end !important;
    gap: 10px !important;
    margin: 0 !important;
  }

  .content-shell--music-page-cloud .catalog-search-field {
    min-width: 0 !important;
  }
}

@media (min-width: 901px) and (max-width: 1260px) {
  .content-shell--music-page-cloud .catalog-hero {
    grid-template-columns: 1fr !important;
  }

  .content-shell--music-page-cloud .catalog-filter {
    justify-self: start !important;
    width: min(640px, 100%) !important;
    max-width: 640px !important;
    grid-template-columns: minmax(220px, 1fr) 140px 140px !important;
  }
}

@media (min-width: 901px) {
  .main.sr-open .content-shell--music-page-cloud .catalog-hero {
    grid-template-columns: minmax(0, max-content) minmax(430px, 500px) !important;
    justify-content: space-between !important;
    align-items: end !important;
    gap: 22px !important;
  }

  .main.sr-open .content-shell--music-page-cloud .catalog-hero h1 {
    max-width: none !important;
    white-space: nowrap !important;
    font-size: clamp(2.85rem, 3.2vw, 3.55rem) !important;
  }

  .main.sr-open .content-shell--music-page-cloud .catalog-filter {
    width: min(500px, 100%) !important;
    max-width: 500px !important;
    justify-self: end !important;
    grid-template-columns: minmax(170px, 1fr) 112px 112px !important;
    gap: 8px !important;
  }

  .main.sr-open .content-shell--music-page-cloud .catalog-filter select.finput {
    min-width: 0 !important;
    padding-left: 14px !important;
    padding-right: 30px !important;
  }
}
</style>

<section class="content-shell content-shell--cloud content-shell--catalog-cloud content-shell--music-page-cloud">
  <div class="section-media-cloud section-media-cloud--catalog section-media-cloud--music-page" data-media-cloud='<?= h(json_encode($musicMediaCloud, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE)) ?>' aria-hidden="true"></div>
  <div class="wrap">
    <div class="catalog-hero">
      <div>
        <h1 data-t="music_title">Discover music</h1>
      </div>

      <form method="get" class="catalog-filter" data-instant-filter>
        <label class="catalog-search-field">
          <svg width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true"><circle cx="11" cy="11" r="8"/><path d="m21 21-4.3-4.3"/></svg>
          <input type="text" name="q" class="finput" value="<?= h($search) ?>" data-tp="music_search_placeholder" placeholder="Search track, release, or artist" autocomplete="off">
        </label>
        <select name="tipo" class="finput">
          <option value="" data-t="music_all_formats">All formats</option>
          <option value="Single" data-t="release_type_single" <?= $type === 'Single' ? 'selected' : '' ?>><?= h(release_type_label('Single')) ?></option>
          <option value="EP" data-t="release_type_ep" <?= $type === 'EP' ? 'selected' : '' ?>><?= h(release_type_label('EP')) ?></option>
          <option value="Album" data-t="release_type_album" <?= $type === 'Album' ? 'selected' : '' ?>><?= h(release_type_label('Album')) ?></option>
        </select>
        <select name="genero" class="finput">
          <option value="" data-t="music_all_genres">All genres</option>
          <?php foreach ($genreOptions as $genreOption): ?>
            <?php $genreName = (string)$genreOption['genero']; ?>
            <option value="<?= h($genreName) ?>" <?= $genre === $genreName ? 'selected' : '' ?>><?= h($genreName) ?></option>
          <?php endforeach; ?>
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
                  <button type="button" class="pbt" aria-label="<?= h(current_lang() === 'en' ? 'Play' : 'Tocar') ?>" onclick="event.preventDefault(); event.stopPropagation(); playTrack('<?= h(addslashes($release['first_track_title'])) ?>','<?= h(addslashes($release['artist_nome'])) ?>','<?= h($cover) ?>','<?= h($audio) ?>',<?= (int)$release['artistId'] ?>,'<?= h($artistFoto) ?>',<?= (int)$release['first_track_id'] ?>)">
                    <svg width="18" height="18" fill="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path d="M8 5v14l11-7z"/></svg>
                  </button>
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
            <span data-t="pagination_page">Página</span> <?= $pageNumber ?>
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
<?php include '../includes/footer.php'; ?>
