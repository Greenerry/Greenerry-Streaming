<?php
require_once '../includes/config.php';

$artistId = (int)($_GET['id'] ?? 0);
if ($artistId <= 0) {
    header('Location: artists.php');
    exit;
}

$artist = db_one($conn, "SELECT * FROM cliente WHERE idCliente = {$artistId} AND estado = 'ativo' LIMIT 1");
if (!$artist) {
    header('Location: artists.php');
    exit;
}

$perPage = 20;
$releasePage = max(1, (int)($_GET['rel_page'] ?? 1));
$productPage = max(1, (int)($_GET['merch_page'] ?? 1));

$totalReleases = (int)(db_one(
    $conn,
    "SELECT COUNT(*) AS total
     FROM release_musical
     WHERE idCliente = {$artistId}
       AND estado = 'aprovado'
       AND ativo = 1"
)['total'] ?? 0);
$totalTracks = (int)(db_one(
    $conn,
    "SELECT COUNT(*) AS total
     FROM faixa f
     JOIN release_musical r ON r.idRelease = f.idRelease
     WHERE r.idCliente = {$artistId}
       AND r.estado = 'aprovado'
       AND r.ativo = 1
       AND f.estado = 'aprovada'
       AND f.ativo = 1"
)['total'] ?? 0);
$totalProducts = (int)(db_one(
    $conn,
    "SELECT COUNT(*) AS total
     FROM produto
     WHERE idCliente = {$artistId}
       AND estado = 'aprovado'
       AND ativo = 1"
)['total'] ?? 0);

$releasePages = max(1, (int)ceil($totalReleases / $perPage));
$productPages = max(1, (int)ceil($totalProducts / $perPage));
$releasePage = min($releasePage, $releasePages);
$productPage = min($productPage, $productPages);
$releaseOffset = ($releasePage - 1) * $perPage;
$productOffset = ($productPage - 1) * $perPage;

$artistPageUrl = static function (string $key, int $targetPage) use ($artistId, $releasePage, $productPage): string {
    $query = [
        'id' => $artistId,
        'rel_page' => $releasePage,
        'merch_page' => $productPage,
        $key => $targetPage
    ];
    return 'artist.php?' . http_build_query($query);
};

$releases = db_all(
    $conn,
    "SELECT r.*, COUNT(f.idFaixa) AS total_faixas
     FROM release_musical r
     LEFT JOIN faixa f ON f.idRelease = r.idRelease AND f.estado = 'aprovada' AND f.ativo = 1
     WHERE r.idCliente = {$artistId}
       AND r.estado = 'aprovado'
       AND r.ativo = 1
     GROUP BY r.idRelease
     ORDER BY COALESCE(r.data_lancamento, DATE(r.created_at)) DESC, r.idRelease DESC
     LIMIT {$perPage} OFFSET {$releaseOffset}"
);

$products = db_all(
    $conn,
    "SELECT p.*, cat.nomeCategoria
     FROM produto p
     JOIN categoria cat ON cat.idCategoria = p.idCategoria
     WHERE p.idCliente = {$artistId}
       AND p.estado = 'aprovado'
       AND p.ativo = 1
     ORDER BY p.created_at DESC
     LIMIT {$perPage} OFFSET {$productOffset}"
);

$viewerId = current_user_id();
$isOwnArtistPage = $viewerId > 0 && $viewerId === $artistId;
$isFollowingArtist = false;
$followMessage = '';

if ($viewerId > 0 && !$isOwnArtistPage) {
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['artist_action'] ?? '') === 'toggle_follow') {
        $followMessage = verify_csrf_request() ?? '';

        if ($followMessage === '') {
            $existingFollow = db_one(
                $conn,
                "SELECT idSeguirArtista
                 FROM seguir_artista
                 WHERE idSeguidor = {$viewerId}
                   AND idArtista = {$artistId}
                 LIMIT 1"
            );

            if ($existingFollow) {
                mysqli_query(
                    $conn,
                    "DELETE FROM seguir_artista
                     WHERE idSeguidor = {$viewerId}
                       AND idArtista = {$artistId}"
                );
                $followMessage = current_lang() === 'en'
                    ? 'You stopped following this artist.'
                    : 'Deixaste de seguir este artista.';
            } else {
                mysqli_query(
                    $conn,
                    "INSERT INTO seguir_artista (idSeguidor, idArtista)
                     VALUES ({$viewerId}, {$artistId})"
                );
                $followMessage = current_lang() === 'en'
                    ? 'You are now following this artist.'
                    : 'Agora segues este artista.';
            }
        }
    }

    $isFollowingArtist = db_one(
        $conn,
        "SELECT idSeguirArtista
         FROM seguir_artista
         WHERE idSeguidor = {$viewerId}
           AND idArtista = {$artistId}
         LIMIT 1"
    ) !== null;
}

include '../includes/header.php';
?>

<section class="artist-hero<?= !empty($artist['banner']) ? ' artist-hero--with-banner' : '' ?>">
  <div class="artist-hero-backdrop">
    <?php if (!empty($artist['banner'])): ?>
      <img src="<?= h(asset_url('img', $artist['banner'])) ?>" alt="<?= h($artist['nome']) ?>">
    <?php endif; ?>
  </div>
  <div class="artist-hero-overlay"></div>
  <div class="artist-hero-content wrap">
    <div class="artist-hero-avatar avatar">
      <?php if (!empty($artist['foto'])): ?>
        <img src="<?= h(asset_url('img', $artist['foto'])) ?>" alt="<?= h($artist['nome']) ?>">
      <?php endif; ?>
    </div>
    <div class="artist-hero-panel">
      <span class="badge" data-t="artist_badge">Artist profile</span>
      <h1><?= h($artist['nome']) ?></h1>
      <?php if (!empty($artist['bio'])): ?>
        <p><?= h($artist['bio']) ?></p>
      <?php endif; ?>
      <?php if ($followMessage !== ''): ?>
        <p class="artist-follow-feedback"><?= h($followMessage) ?></p>
      <?php endif; ?>
      <?php if ($viewerId > 0 && !$isOwnArtistPage): ?>
        <form method="post" class="artist-hero-actions">
          <?= csrf_input() ?>
          <input type="hidden" name="artist_action" value="toggle_follow">
          <button type="submit" class="btn <?= $isFollowingArtist ? 'btn-outline' : 'btn-dark' ?>">
            <?= $isFollowingArtist ? 'A seguir' : 'Seguir artista' ?>
          </button>
        </form>
      <?php endif; ?>
    </div>
  </div>
</section>

<section class="content-shell">
  <div class="wrap">
    <div class="artist-overview">
      <div class="stat">
        <div class="stat-val"><?= $totalReleases ?></div>
        <div class="stat-lbl" data-t="artist_stat_releases">Releases</div>
      </div>
      <div class="stat">
        <div class="stat-val"><?= $totalTracks ?></div>
        <div class="stat-lbl" data-t="artist_stat_tracks">Tracks</div>
      </div>
      <div class="stat">
        <div class="stat-val"><?= $totalProducts ?></div>
        <div class="stat-lbl" data-t="artist_stat_products">Products</div>
      </div>
    </div>

    <div class="page-intro mt8">
      <span class="slabel" data-t="artist_releases_label">Releases</span>
      <h2 data-t="artist_releases_title">Releases</h2>
    </div>

    <div class="grid stg">
      <?php foreach ($releases as $release): ?>
        <a class="mcard" href="release.php?id=<?= (int)$release['idRelease'] ?>">
          <div class="cover">
            <?php if (!empty($release['capa'])): ?>
              <img src="<?= h(asset_url('img', $release['capa'])) ?>" alt="<?= h($release['titulo']) ?>">
            <?php endif; ?>
          </div>
          <div class="meta">
            <span class="badge badge-dark"><?= h($release['tipo']) ?></span>
            <h4><?= h($release['titulo']) ?></h4>
            <div class="sub"><?= (int)$release['total_faixas'] ?> <span data-t="release_tracks_count">faixas</span></div>
          </div>
        </a>
      <?php endforeach; ?>
    </div>

    <?php if ($releasePages > 1): ?>
      <nav class="pager" aria-label="Pagination">
        <?php if ($releasePage > 1): ?>
          <a class="btn btn-ghost btn-sm" href="<?= h($artistPageUrl('rel_page', $releasePage - 1)) ?>" data-t="pagination_previous">Anterior</a>
        <?php else: ?>
          <span class="btn btn-ghost btn-sm is-disabled" data-t="pagination_previous">Anterior</span>
        <?php endif; ?>
        <span class="pager-status"><span data-t="pagination_page">Pagina</span> <?= $releasePage ?> <span data-t="pagination_of">de</span> <?= $releasePages ?></span>
        <?php if ($releasePage < $releasePages): ?>
          <a class="btn btn-ghost btn-sm" href="<?= h($artistPageUrl('rel_page', $releasePage + 1)) ?>" data-t="pagination_next">Seguinte</a>
        <?php else: ?>
          <span class="btn btn-ghost btn-sm is-disabled" data-t="pagination_next">Seguinte</span>
        <?php endif; ?>
      </nav>
    <?php endif; ?>

    <?php if ($products): ?>
      <div class="page-intro mt8">
        <span class="slabel" data-t="artist_merch_label">Merch</span>
        <h2 data-t="artist_merch_title">Merch</h2>
      </div>

      <div class="grid stg">
        <?php foreach ($products as $product): ?>
          <a href="produto.php?id=<?= (int)$product['idProduto'] ?>" class="mcard">
            <div class="cover">
              <?php if (!empty($product['imagem'])): ?>
                <img src="<?= h(asset_url('img', $product['imagem'])) ?>" alt="<?= h($product['nomeProduto']) ?>">
              <?php endif; ?>
            </div>
            <div class="meta">
              <span class="badge badge-dark"><?= h($product['nomeCategoria']) ?></span>
              <h4><?= h($product['nomeProduto']) ?></h4>
              <div class="price"><?= h(format_eur((float)$product['precoAtual'])) ?></div>
            </div>
          </a>
        <?php endforeach; ?>
      </div>

      <?php if ($productPages > 1): ?>
        <nav class="pager" aria-label="Pagination">
          <?php if ($productPage > 1): ?>
            <a class="btn btn-ghost btn-sm" href="<?= h($artistPageUrl('merch_page', $productPage - 1)) ?>" data-t="pagination_previous">Anterior</a>
          <?php else: ?>
            <span class="btn btn-ghost btn-sm is-disabled" data-t="pagination_previous">Anterior</span>
          <?php endif; ?>
          <span class="pager-status"><span data-t="pagination_page">Pagina</span> <?= $productPage ?> <span data-t="pagination_of">de</span> <?= $productPages ?></span>
          <?php if ($productPage < $productPages): ?>
            <a class="btn btn-ghost btn-sm" href="<?= h($artistPageUrl('merch_page', $productPage + 1)) ?>" data-t="pagination_next">Seguinte</a>
          <?php else: ?>
            <span class="btn btn-ghost btn-sm is-disabled" data-t="pagination_next">Seguinte</span>
          <?php endif; ?>
        </nav>
      <?php endif; ?>
    <?php endif; ?>
  </div>
</section>

<?php include '../includes/footer.php'; ?>
