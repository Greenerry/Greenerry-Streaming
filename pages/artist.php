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

$allowedReleaseTypes = ['Single', 'EP', 'Album'];
$showMusicArea = public_page_active('music.php') && public_page_active('release.php');
$showShopArea = public_page_active('shop.php') && public_page_active('produto.php');

$totalReleases = $showMusicArea ? (int)(db_one(
    $conn,
    "SELECT COUNT(*) AS total
     FROM release_musical
     WHERE idCliente = {$artistId}
       AND estado = 'aprovado'
       AND ativo = 1"
)['total'] ?? 0) : 0;
$totalTracks = $showMusicArea ? (int)(db_one(
    $conn,
    "SELECT COUNT(*) AS total
     FROM faixa f
     JOIN release_musical r ON r.idRelease = f.idRelease
     WHERE r.idCliente = {$artistId}
       AND r.estado = 'aprovado'
       AND r.ativo = 1
       AND f.estado = 'aprovada'
       AND f.ativo = 1"
)['total'] ?? 0) : 0;
$totalProducts = $showShopArea ? (int)(db_one(
    $conn,
    "SELECT COUNT(*) AS total
     FROM produto
     WHERE idCliente = {$artistId}
       AND estado = 'aprovado'
       AND ativo = 1"
)['total'] ?? 0) : 0;
$totalFollowers = (int)(db_one($conn, "SELECT COUNT(*) AS total FROM seguir_artista WHERE idArtista = {$artistId}")['total'] ?? 0);
$totalFollowing = (int)(db_one($conn, "SELECT COUNT(*) AS total FROM seguir_artista WHERE idSeguidor = {$artistId}")['total'] ?? 0);
// These query flags open the follower/following modal directly from profile links.
$showFollowers = (int)($_GET['followers'] ?? 0) === 1;
$showFollowing = (int)($_GET['following'] ?? 0) === 1;
$artistReleasesPerPage = 8;
$artistProductsPerPage = 8;
$artistReleasePage = max(1, (int)($_GET['release_page'] ?? 1));
$artistProductPage = max(1, (int)($_GET['product_page'] ?? 1));
$artistReleaseTotalPages = max(1, (int)ceil($totalReleases / $artistReleasesPerPage));
$artistProductTotalPages = max(1, (int)ceil($totalProducts / $artistProductsPerPage));
$artistReleasePage = min($artistReleasePage, $artistReleaseTotalPages);
$artistProductPage = min($artistProductPage, $artistProductTotalPages);
$artistReleaseOffset = ($artistReleasePage - 1) * $artistReleasesPerPage;
$artistProductOffset = ($artistProductPage - 1) * $artistProductsPerPage;
$artistPageUrl = static function (string $key, int $targetPage) use ($artistId): string {
    $query = $_GET;
    $query['id'] = $artistId;
    $query[$key] = $targetPage;
    return 'artist.php?' . http_build_query($query);
};
$followers = db_all(
    $conn,
    "SELECT c.idCliente, c.nome, c.foto, c.banner, c.bio
     FROM seguir_artista sa
     JOIN cliente c ON c.idCliente = sa.idSeguidor
     WHERE sa.idArtista = {$artistId}
       AND c.estado = 'ativo'
     ORDER BY sa.criado_em DESC
     LIMIT 80"
);
// Both modal lists are limited to keep the artist page fast.
$following = db_all(
    $conn,
    "SELECT c.idCliente, c.nome, c.foto, c.banner, c.bio
     FROM seguir_artista sa
     JOIN cliente c ON c.idCliente = sa.idArtista
     WHERE sa.idSeguidor = {$artistId}
       AND c.estado = 'ativo'
     ORDER BY sa.criado_em DESC
     LIMIT 80"
);

$artistCategories = $showShopArea ? db_all(
    $conn,
    "SELECT DISTINCT cat.idCategoria, cat.nomeCategoria
     FROM produto p
     JOIN categoria cat ON cat.idCategoria = p.idCategoria
     WHERE p.idCliente = {$artistId}
       AND p.estado = 'aprovado'
       AND p.ativo = 1
     ORDER BY cat.nomeCategoria ASC"
) : [];

$releases = $showMusicArea ? db_all(
    $conn,
    "SELECT
        r.idRelease,
        r.titulo,
        r.tipo,
        r.capa,
        r.data_lancamento,
        r.criado_em,
        COUNT(f.idFaixa) AS total_faixas,
        first_track.idFaixa AS first_track_id,
        first_track.titulo AS first_track_title,
        first_track.ficheiro_audio AS first_track_audio
     FROM release_musical r
     LEFT JOIN faixa f ON f.idRelease = r.idRelease AND f.estado = 'aprovada' AND f.ativo = 1
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
     WHERE r.idCliente = {$artistId}
       AND r.estado = 'aprovado'
       AND r.ativo = 1
     GROUP BY r.idRelease, r.titulo, r.tipo, r.capa, r.data_lancamento, r.criado_em, first_track.idFaixa, first_track.titulo, first_track.ficheiro_audio
     ORDER BY COALESCE(r.data_lancamento, DATE(r.criado_em)) DESC, r.idRelease DESC
     LIMIT {$artistReleasesPerPage} OFFSET {$artistReleaseOffset}"
) : [];

$products = $showShopArea ? db_all(
    $conn,
    "SELECT p.*, cat.nomeCategoria
     FROM produto p
     JOIN categoria cat ON cat.idCategoria = p.idCategoria
     WHERE p.idCliente = {$artistId}
       AND p.estado = 'aprovado'
       AND p.ativo = 1
     ORDER BY p.criado_em DESC
     LIMIT {$artistProductsPerPage} OFFSET {$artistProductOffset}"
) : [];

$artistPageMediaCloud = [];
// Use the artist, release, and merch media already loaded for this page background.
foreach (['foto', 'banner'] as $field) {
    $image = asset_url('img', $artist[$field] ?? '');
    if ($image !== '') {
        $artistPageMediaCloud[$image] = [
            'src' => $image,
            'label' => (string)$artist['nome'],
            'type' => 'artist',
        ];
    }
}
foreach ($releases as $release) {
    $image = asset_url('img', $release['capa']);
    if ($image !== '') {
        $artistPageMediaCloud[$image] = [
            'src' => $image,
            'label' => (string)$release['titulo'],
            'type' => 'music',
        ];
    }
}
foreach ($products as $product) {
    $image = asset_url('img', product_main_image($conn, (int)$product['idProduto']));
    if ($image !== '') {
        $artistPageMediaCloud[$image] = [
            'src' => $image,
            'label' => (string)$product['nomeProduto'],
            'type' => 'store',
        ];
    }
}
$artistPageMediaCloud = array_values(array_slice($artistPageMediaCloud, 0, 12));

$viewerId = current_user_id();
if ($viewerId > 0 && !active_user_session($conn)) {
    end_user_session_only();
    $viewerId = 0;
}
$isOwnArtistPage = $viewerId > 0 && $viewerId === $artistId;
$isFollowingArtist = false;
$followMessage = '';
$shouldAutoFollow = (int)($_GET['follow'] ?? 0) === 1;

if ($viewerId > 0 && !$isOwnArtistPage) {
    // Normal POST stays as a no-JS fallback; JS uses api/toggle_follow.php instead.
    $isFollowPost = $_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['artist_action'] ?? '') === 'toggle_follow';
    $followMessage = $isFollowPost ? (verify_csrf_request() ?? '') : '';

    if ($followMessage === '' && ($isFollowPost || $shouldAutoFollow)) {
        $existingFollow = db_one(
            $conn,
            "SELECT idSeguirArtista
             FROM seguir_artista
             WHERE idSeguidor = {$viewerId}
               AND idArtista = {$artistId}
             LIMIT 1"
        );

        if ($existingFollow) {
            if ($isFollowPost) {
                mysqli_query(
                    $conn,
                    "DELETE FROM seguir_artista
                     WHERE idSeguidor = {$viewerId}
                       AND idArtista = {$artistId}"
                );
                $followMessage = current_lang() === 'en'
                    ? 'You stopped following this artist.'
                    : 'Deixaste de seguir este artista.';
            }
        } else {
            mysqli_query(
                $conn,
                "INSERT INTO seguir_artista (idSeguidor, idArtista)
                 VALUES ({$viewerId}, {$artistId})"
            );
            if ($isFollowPost || $shouldAutoFollow) {
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

// Re-read counts after fallback follow/unfollow so the rendered page is fresh.
$totalFollowers = (int)(db_one($conn, "SELECT COUNT(*) AS total FROM seguir_artista WHERE idArtista = {$artistId}")['total'] ?? 0);
$totalFollowing = (int)(db_one($conn, "SELECT COUNT(*) AS total FROM seguir_artista WHERE idSeguidor = {$artistId}")['total'] ?? 0);

include '../includes/header.php';
?>

<section class="artist-hero<?= !empty($artist['banner']) ? ' artist-hero--with-banner' : '' ?>">
  <div class="artist-hero-backdrop">
    <?php if (!empty($artist['banner'])): ?>
      <img src="<?= h(asset_url('img', $artist['banner'])) ?>" alt="<?= h($artist['nome']) ?>">
    <?php endif; ?>
  </div>
  <div class="artist-hero-overlay"></div>
  <button type="button" class="btn btn-ghost btn-sm artist-back-btn" onclick="window.history.back()" data-t="artist_back">Voltar</button>
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
      <div class="artist-follow-stats">
        <button type="button" class="artist-follow-count" data-open-followers="followers-modal">
          <strong data-artist-followers-count><?= (int)$totalFollowers ?></strong>&nbsp;<span data-t="artist_stat_followers">Seguidores</span>
        </button>
        <button type="button" class="artist-follow-count" data-open-followers="following-modal">
          <strong data-artist-following-count><?= (int)$totalFollowing ?></strong>&nbsp;<span data-t="artist_stat_following">A seguir</span>
        </button>
      </div>
      <?php if ($followMessage !== ''): ?>
        <p class="artist-follow-feedback"><?= h($followMessage) ?></p>
      <?php endif; ?>
      <?php if ($viewerId > 0 && !$isOwnArtistPage): ?>
        <form method="post" class="artist-hero-actions" data-follow-form data-artist-id="<?= (int)$artistId ?>">
          <?= csrf_input() ?>
          <input type="hidden" name="artist_action" value="toggle_follow">
          <button type="submit" class="btn <?= $isFollowingArtist ? 'btn-outline' : 'btn-dark' ?>" data-follow-button>
            <span data-t="<?= $isFollowingArtist ? 'artist_following' : 'artist_follow' ?>"><?= $isFollowingArtist ? 'A seguir' : 'Seguir artista' ?></span>
          </button>
        </form>
      <?php elseif ($viewerId <= 0): ?>
        <div class="artist-hero-actions">
          <a class="btn btn-dark" href="login.php?next=artist.php?id=<?= (int)$artistId ?>%26follow=1" data-t="artist_follow">Seguir artista</a>
        </div>
      <?php endif; ?>
    </div>
  </div>
</section>

<section class="content-shell content-shell--cloud content-shell--catalog-cloud">
  <div class="section-media-cloud section-media-cloud--catalog" data-media-cloud='<?= h(json_encode($artistPageMediaCloud, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE)) ?>' aria-hidden="true"></div>
  <div class="wrap">
    <?php if ($showMusicArea || $showShopArea): ?>
    <div class="artist-overview">
      <?php if ($showMusicArea): ?>
      <div class="stat">
        <div class="stat-val"><?= $totalReleases ?></div>
        <div class="stat-lbl" data-t="artist_stat_releases">Releases</div>
      </div>
      <div class="stat">
        <div class="stat-val"><?= $totalTracks ?></div>
        <div class="stat-lbl" data-t="artist_stat_tracks">Tracks</div>
      </div>
      <?php endif; ?>
      <?php if ($showShopArea): ?>
      <div class="stat">
        <div class="stat-val"><?= $totalProducts ?></div>
        <div class="stat-lbl" data-t="artist_stat_products">Products</div>
      </div>
      <?php endif; ?>
    </div>
    <?php endif; ?>

    <div id="followers-modal" class="followers-modal<?= $showFollowers ? ' is-open' : '' ?>" data-followers-modal aria-hidden="<?= $showFollowers ? 'false' : 'true' ?>">
      <div class="followers-modal-backdrop" data-close-followers></div>
      <div class="followers-modal-panel" role="dialog" aria-modal="true" aria-labelledby="followers-modal-title">
        <div class="followers-modal-head">
          <div>
            <span class="slabel" data-t="artist_followers_label">Comunidade</span>
            <h2 id="followers-modal-title" data-t="artist_followers_title">Seguidores</h2>
          </div>
          <button type="button" class="followers-modal-close" data-close-followers aria-label="<?= h(current_lang() === 'en' ? 'Close' : 'Fechar') ?>">X</button>
        </div>
        <input type="search" class="finput followers-search" data-followers-search placeholder="<?= h(current_lang() === 'en' ? 'Search followers' : 'Procurar seguidores') ?>" data-tp="artist_followers_search">
        <?php if (!$followers): ?>
          <p class="empty-copy" data-t="artist_followers_empty">Ainda não existem seguidores.</p>
        <?php else: ?>
          <div class="followers-list">
            <?php foreach ($followers as $follower): ?>
              <a href="artist.php?id=<?= (int)$follower['idCliente'] ?>" class="followers-list-item<?= !empty($follower['banner']) ? ' has-banner' : '' ?>"<?= !empty($follower['banner']) ? ' style="--follower-banner:url(\'' . h(asset_url('img', $follower['banner'])) . '\')"' : '' ?> data-follower-name="<?= h(mb_strtolower($follower['nome'])) ?>">
                <div class="followers-list-media">
                  <span class="followers-list-banner" aria-hidden="true"></span>
                  <div class="avatar">
                    <?php if (!empty($follower['foto'])): ?>
                      <img src="<?= h(asset_url('img', $follower['foto'])) ?>" alt="<?= h($follower['nome']) ?>">
                    <?php endif; ?>
                  </div>
                </div>
                <div>
                  <strong><?= h($follower['nome']) ?></strong>
                  <p><?= h($follower['bio'] ?: '') ?></p>
                </div>
              </a>
            <?php endforeach; ?>
          </div>
          <p class="empty-copy is-hidden" data-followers-empty data-t="artist_followers_no_results">Nenhum seguidor encontrado.</p>
        <?php endif; ?>
      </div>
    </div>

    <div id="following-modal" class="followers-modal<?= $showFollowing ? ' is-open' : '' ?>" data-followers-modal aria-hidden="<?= $showFollowing ? 'false' : 'true' ?>">
      <div class="followers-modal-backdrop" data-close-followers></div>
      <div class="followers-modal-panel" role="dialog" aria-modal="true" aria-labelledby="following-modal-title">
        <div class="followers-modal-head">
          <div>
            <span class="slabel" data-t="artist_followers_label">Comunidade</span>
            <h2 id="following-modal-title" data-t="artist_following_title">A seguir</h2>
          </div>
          <button type="button" class="followers-modal-close" data-close-followers aria-label="<?= h(current_lang() === 'en' ? 'Close' : 'Fechar') ?>">X</button>
        </div>
        <input type="search" class="finput followers-search" data-followers-search placeholder="<?= h(current_lang() === 'en' ? 'Search following' : 'Procurar a seguir') ?>" data-tp="artist_following_search">
        <?php if (!$following): ?>
          <p class="empty-copy" data-t="artist_following_empty">Ainda não segue artistas.</p>
        <?php else: ?>
          <div class="followers-list">
            <?php foreach ($following as $followed): ?>
              <a href="artist.php?id=<?= (int)$followed['idCliente'] ?>" class="followers-list-item<?= !empty($followed['banner']) ? ' has-banner' : '' ?>"<?= !empty($followed['banner']) ? ' style="--follower-banner:url(\'' . h(asset_url('img', $followed['banner'])) . '\')"' : '' ?> data-follower-name="<?= h(mb_strtolower($followed['nome'])) ?>">
                <div class="followers-list-media">
                  <span class="followers-list-banner" aria-hidden="true"></span>
                  <div class="avatar">
                    <?php if (!empty($followed['foto'])): ?>
                      <img src="<?= h(asset_url('img', $followed['foto'])) ?>" alt="<?= h($followed['nome']) ?>">
                    <?php endif; ?>
                  </div>
                </div>
                <div>
                  <strong><?= h($followed['nome']) ?></strong>
                  <p><?= h($followed['bio'] ?: '') ?></p>
                </div>
              </a>
            <?php endforeach; ?>
          </div>
          <p class="empty-copy is-hidden" data-followers-empty data-t="artist_following_no_results">Nenhum artista encontrado.</p>
        <?php endif; ?>
      </div>
    </div>

    <?php if ($showMusicArea): ?>
    <div class="page-intro mt8">
      <span class="slabel" data-t="artist_releases_label">Releases</span>
      <h2 data-t="artist_releases_title">Releases</h2>
    </div>

    <?php if ($totalReleases > 0): ?>
      <nav class="artist-filter-pills" aria-label="Release filters">
        <button type="button" class="on" data-artist-filter="release" data-filter-value="all" data-t="music_all_formats">Todos os formatos</button>
        <?php foreach ($allowedReleaseTypes as $type): ?>
          <button type="button" data-artist-filter="release" data-filter-value="<?= h($type) ?>" data-release-type="<?= h($type) ?>"><?= h(release_type_label($type)) ?></button>
        <?php endforeach; ?>
      </nav>
    <?php endif; ?>

    <div class="grid stg" data-artist-filter-grid="release">
      <?php foreach ($releases as $release): ?>
        <?php
          $cover = asset_url('img', $release['capa']);
          $audio = asset_url('audio', $release['first_track_audio']);
          $artistFoto = asset_url('img', $artist['foto']);
          $payload = [
              'id' => (int)$release['first_track_id'],
              'title' => $release['first_track_title'],
              'artist' => $artist['nome'],
              'cover' => $cover,
              'audio' => $audio,
              'artistId' => (int)$artistId,
              'artistFoto' => $artistFoto,
              'type' => $release['tipo'],
              'releaseKey' => $release['idRelease'] . '-' . $artistId
          ];
        ?>
        <a class="mcard" href="release.php?id=<?= (int)$release['idRelease'] ?>" data-filter-item data-release-type-value="<?= h($release['tipo']) ?>" data-track='<?= h(json_encode($payload)) ?>'>
          <div class="cover">
            <?php if ($cover): ?>
              <img src="<?= h($cover) ?>" alt="<?= h($release['titulo']) ?>">
            <?php endif; ?>
            <div class="cover-ov">
              <?php if (!empty($release['first_track_audio'])): ?>
                <button type="button" class="pbt" data-t="release_play_track" onclick="event.preventDefault(); event.stopPropagation(); playTrack('<?= h(addslashes($release['first_track_title'])) ?>','<?= h(addslashes($artist['nome'])) ?>','<?= h($cover) ?>','<?= h($audio) ?>',<?= (int)$artistId ?>,'<?= h($artistFoto) ?>',<?= (int)$release['first_track_id'] ?>)">Play</button>
              <?php endif; ?>
            </div>
          </div>
          <div class="meta">
            <span class="badge badge-dark" data-release-type="<?= h($release['tipo']) ?>"><?= h(release_type_label($release['tipo'])) ?></span>
            <h4><?= h($release['titulo']) ?></h4>
            <div class="sub"><?= h(count_label((int)$release['total_faixas'], 'track')) ?></div>
          </div>
        </a>
      <?php endforeach; ?>
    </div>

    <?php if ($totalReleases > 0): ?>
      <p class="empty-copy is-hidden" data-artist-empty="release" data-t="artist_releases_empty">Nenhum release correspondeu ao filtro.</p>
      <?php if ($artistReleaseTotalPages > 1): ?>
        <nav class="pager" aria-label="Pagination">
          <?= $artistReleasePage > 1 ? '<a class="btn btn-ghost btn-sm" href="' . h($artistPageUrl('release_page', $artistReleasePage - 1)) . '" data-t="pagination_previous">Anterior</a>' : '<span class="btn btn-ghost btn-sm is-disabled" data-t="pagination_previous">Anterior</span>' ?>
          <span class="pager-status"><span data-t="pagination_page">Página</span> <?= (int)$artistReleasePage ?> <span data-t="pagination_of">de</span> <?= (int)$artistReleaseTotalPages ?></span>
          <?= $artistReleasePage < $artistReleaseTotalPages ? '<a class="btn btn-ghost btn-sm" href="' . h($artistPageUrl('release_page', $artistReleasePage + 1)) . '" data-t="pagination_next">Seguinte</a>' : '<span class="btn btn-ghost btn-sm is-disabled" data-t="pagination_next">Seguinte</span>' ?>
        </nav>
      <?php endif; ?>
    <?php endif; ?>
    <?php endif; ?>

    <?php if ($showShopArea && $totalProducts > 0): ?>
      <div class="page-intro mt8">
        <span class="slabel" data-t="artist_merch_label">Merch</span>
        <h2 data-t="artist_merch_title">Merch</h2>
      </div>

      <nav class="artist-filter-pills" aria-label="Merch filters">
        <button type="button" class="on" data-artist-filter="merch" data-filter-value="all" data-t="shop_all_categories">Todas as categorias</button>
        <?php foreach ($artistCategories as $category): ?>
          <button type="button" data-artist-filter="merch" data-filter-value="<?= (int)$category['idCategoria'] ?>" data-product-category="<?= h($category['nomeCategoria']) ?>"><?= h(category_label($category['nomeCategoria'])) ?></button>
        <?php endforeach; ?>
      </nav>

      <div class="grid stg" data-artist-filter-grid="merch">
        <?php foreach ($products as $product): ?>
          <?php $productImage = product_main_image($conn, (int)$product['idProduto']); ?>
          <a href="produto.php?id=<?= (int)$product['idProduto'] ?>" class="mcard" data-filter-item data-merch-category-value="<?= (int)$product['idCategoria'] ?>">
            <div class="cover">
              <?php if ($productImage): ?>
                <img src="<?= h(asset_url('img', $productImage)) ?>" alt="<?= h($product['nomeProduto']) ?>">
              <?php endif; ?>
            </div>
            <div class="meta">
              <span class="badge badge-dark" data-product-category="<?= h($product['nomeCategoria']) ?>"><?= h(category_label($product['nomeCategoria'])) ?></span>
              <h4><?= h($product['nomeProduto']) ?></h4>
              <div class="price"><?= h(format_eur((float)$product['precoAtual'])) ?></div>
            </div>
          </a>
        <?php endforeach; ?>
      </div>

      <?php if ($totalProducts > 0): ?>
        <p class="empty-copy is-hidden" data-artist-empty="merch" data-t="artist_merch_empty">Nenhum produto correspondeu ao filtro.</p>
        <?php if ($artistProductTotalPages > 1): ?>
          <nav class="pager" aria-label="Pagination">
            <?= $artistProductPage > 1 ? '<a class="btn btn-ghost btn-sm" href="' . h($artistPageUrl('product_page', $artistProductPage - 1)) . '" data-t="pagination_previous">Anterior</a>' : '<span class="btn btn-ghost btn-sm is-disabled" data-t="pagination_previous">Anterior</span>' ?>
            <span class="pager-status"><span data-t="pagination_page">Página</span> <?= (int)$artistProductPage ?> <span data-t="pagination_of">de</span> <?= (int)$artistProductTotalPages ?></span>
            <?= $artistProductPage < $artistProductTotalPages ? '<a class="btn btn-ghost btn-sm" href="' . h($artistPageUrl('product_page', $artistProductPage + 1)) . '" data-t="pagination_next">Seguinte</a>' : '<span class="btn btn-ghost btn-sm is-disabled" data-t="pagination_next">Seguinte</span>' ?>
          </nav>
        <?php endif; ?>
      <?php endif; ?>
    <?php endif; ?>
  </div>
</section>

<?php include '../includes/footer.php'; ?>
