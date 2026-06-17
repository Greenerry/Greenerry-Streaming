<?php
// Page purpose: Lists public artist profiles.
// Keep this page simple: prepare data first, then render the view.
require_once '../includes/config.php';

// Artist search is simple: it filters active artists by name.
$search = trim($_GET['q'] ?? '');
$perPage = 20;
$pageNumber = max(1, (int)($_GET['page'] ?? 1));
$searchSql = '';
$types = '';
$params = [];
if ($search !== '') {
    $searchSql = 'AND c.nome LIKE ?';
    $types = 's';
    $params[] = '%' . $search . '%';
}

$totalArtists = (int)(db_one_prepared(
    $conn,
    "SELECT COUNT(*) AS total
     FROM cliente c
     WHERE c.estado = 'ativo'
       {$searchSql}
       AND EXISTS (
         SELECT 1
         FROM release_musical r
         WHERE r.idCliente = c.idCliente
           AND r.estado = 'aprovado'
           AND r.ativo = 1
       )",
    $types,
    $params
)['total'] ?? 0);
$totalPages = max(1, (int)ceil($totalArtists / $perPage));
$pageNumber = min($pageNumber, $totalPages);
$offset = ($pageNumber - 1) * $perPage;
$paginationQuery = [];
if ($search !== '') {
    $paginationQuery['q'] = $search;
}
$pageUrl = static function (int $targetPage) use ($paginationQuery): string {
    return 'artists.php?' . http_build_query($paginationQuery + ['page' => $targetPage]);
};

// This list only includes artists who already have at least one approved release.
$artists = db_all_prepared(
    $conn,
    "SELECT
        c.idCliente,
        c.nome,
        c.email,
        c.foto,
        c.banner,
        c.bio,
        c.slug,
        COUNT(DISTINCT r.idRelease) AS total_releases,
        COUNT(DISTINCT f.idFaixa) AS total_faixas
     FROM cliente c
     JOIN release_musical r
        ON r.idCliente = c.idCliente
       AND r.estado = 'aprovado'
       AND r.ativo = 1
     LEFT JOIN faixa f
        ON f.idRelease = r.idRelease
       AND f.estado = 'aprovada'
       AND f.ativo = 1
     WHERE c.estado = 'ativo'
       {$searchSql}
     GROUP BY c.idCliente, c.nome, c.email, c.foto, c.banner, c.bio, c.slug
     ORDER BY total_releases DESC, nome ASC
     LIMIT {$perPage} OFFSET {$offset}",
    $types,
    $params
);

$artistsMediaCloud = [];
foreach ($artists as $artist) {
    foreach (['foto', 'banner'] as $imageField) {
        $image = asset_url('img', $artist[$imageField] ?? '');
        if ($image !== '') {
            $artistsMediaCloud[$image] = [
                'src' => $image,
                'label' => (string)$artist['nome'],
                'type' => 'artist',
            ];
        }
    }
}
$artistsMediaCloud = array_values(array_slice($artistsMediaCloud, 0, 12));

include '../includes/header.php';
?>

<section class="content-shell content-shell--cloud content-shell--catalog-cloud">
  <div class="section-media-cloud section-media-cloud--catalog" data-media-cloud='<?= h(json_encode($artistsMediaCloud, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE)) ?>' aria-hidden="true"></div>
  <div class="wrap">
    <div class="catalog-hero">
      <div>
        <h1 data-t="artists_title">Discover artists</h1>
      </div>

      <form method="get" class="catalog-filter catalog-filter--single" data-instant-filter>
        <label class="catalog-search-field">
          <svg width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true"><circle cx="11" cy="11" r="8"/><path d="m21 21-4.3-4.3"/></svg>
          <input type="text" name="q" value="<?= h($search) ?>" class="finput" data-tp="artists_search_placeholder" placeholder="Search artists" autocomplete="off">
        </label>
      </form>
    </div>

    <div data-catalog-results>
    <?php if (!$artists): ?>
      <div class="card surface-card catalog-empty-state">
        <div class="card-body text-center">
          <p data-t="artists_empty">No artists matched your search.</p>
        </div>
      </div>
    <?php else: ?>
      <div class="artist-grid-panels">
        <?php foreach ($artists as $artist): ?>
          <a
            href="artist.php?id=<?= (int)$artist['idCliente'] ?>"
            class="artist-panel"
            <?php if (!empty($artist['banner'])): ?>
              style="background-image:
                linear-gradient(180deg, rgba(7,9,13,.1), rgba(7,9,13,.18) 24%, rgba(7,9,13,.52) 72%, rgba(7,9,13,.76) 100%),
                linear-gradient(90deg, rgba(7,9,13,.34), rgba(7,9,13,.08) 58%, rgba(7,9,13,.3)),
                url('<?= h(asset_url('img', $artist['banner'])) ?>');"
            <?php endif; ?>
          >
            <div class="artist-panel-body">
              <div class="avatar artist-panel-avatar">
                <?php if (!empty($artist['foto'])): ?>
                  <img src="<?= h(asset_url('img', $artist['foto'])) ?>" alt="<?= h($artist['nome']) ?>">
                <?php endif; ?>
              </div>
              <div>
                <h3><?= h($artist['nome']) ?></h3>
              </div>
              <div class="artist-panel-stats">
                <span data-count-type="release" data-count-value="<?= (int)$artist['total_releases'] ?>"><?= h(count_label((int)$artist['total_releases'], 'release')) ?></span>
                <span data-count-type="track" data-count-value="<?= (int)$artist['total_faixas'] ?>"><?= h(count_label((int)$artist['total_faixas'], 'track')) ?></span>
              </div>
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
            <span data-t="pagination_page">Página</span> <?= (int)$pageNumber ?>
            <span data-t="pagination_of">de</span> <?= (int)$totalPages ?>
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
