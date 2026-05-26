<?php
require_once '../includes/config.php';

$showMusicArea = public_page_active('music.php') && public_page_active('release.php');
$showArtistArea = public_page_active('artists.php') && public_page_active('artist.php');
$showShopArea = public_page_active('shop.php') && public_page_active('produto.php');

$featuredReleases = $showMusicArea ? db_all(
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
     WHERE r.estado = 'aprovado'
       AND r.ativo = 1
       AND c.estado = 'ativo'
     GROUP BY r.idRelease, r.titulo, r.tipo, r.capa, r.data_lancamento, r.criado_em, c.idCliente, c.nome, c.foto, first_track.idFaixa, first_track.titulo, first_track.ficheiro_audio
     ORDER BY COALESCE(r.data_lancamento, DATE(r.criado_em)) DESC, r.idRelease DESC
     LIMIT 12"
) : [];

$publicArtistsSql = "
    SELECT
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
    GROUP BY c.idCliente, c.nome, c.email, c.foto, c.banner, c.bio, c.slug
";

$featuredArtists = $showArtistArea ? db_all($conn, "{$publicArtistsSql} ORDER BY total_releases DESC, nome ASC LIMIT 5") : [];
$featuredProducts = $showShopArea ? db_all(
    $conn,
    "SELECT p.*, cat.nomeCategoria
     FROM produto p
     JOIN categoria cat ON cat.idCategoria = p.idCategoria
     JOIN cliente c ON c.idCliente = p.idCliente
     WHERE p.estado = 'aprovado'
       AND p.ativo = 1
       AND c.estado = 'ativo'
     ORDER BY p.criado_em DESC
     LIMIT 10"
) : [];
$curatedArtistId = (int)site_setting('featured_artist_id', '0');
$curatedReleaseId = (int)site_setting('featured_release_id', '0');
$curatedProductId = (int)site_setting('featured_product_id', '0');

// Admin-selected homepage picks replace the automatic first card when available.
if ($showArtistArea && $curatedArtistId > 0) {
    $curatedArtist = db_one($conn, "SELECT * FROM ({$publicArtistsSql}) public_artists WHERE idCliente = {$curatedArtistId} LIMIT 1");
    if ($curatedArtist) {
        $featuredArtists = array_values(array_filter($featuredArtists, static fn($artist) => (int)$artist['idCliente'] !== $curatedArtistId));
        array_unshift($featuredArtists, $curatedArtist);
        $featuredArtists = array_slice($featuredArtists, 0, 5);
    }
}

if ($showMusicArea && $curatedReleaseId > 0) {
    $curatedRelease = db_one(
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
         LEFT JOIN faixa f ON f.idRelease = r.idRelease AND f.estado = 'aprovada' AND f.ativo = 1
         LEFT JOIN faixa first_track ON first_track.idFaixa = (
             SELECT f2.idFaixa FROM faixa f2 WHERE f2.idRelease = r.idRelease AND f2.estado = 'aprovada' AND f2.ativo = 1 ORDER BY f2.numero_faixa ASC LIMIT 1
         )
         WHERE r.idRelease = {$curatedReleaseId}
           AND r.estado = 'aprovado'
           AND r.ativo = 1
           AND c.estado = 'ativo'
         GROUP BY r.idRelease, r.titulo, r.tipo, r.capa, r.data_lancamento, r.criado_em, c.idCliente, c.nome, c.foto, first_track.idFaixa, first_track.titulo, first_track.ficheiro_audio
         LIMIT 1"
    );
    if ($curatedRelease) {
        $featuredReleases = array_values(array_filter($featuredReleases, static fn($release) => (int)$release['idRelease'] !== $curatedReleaseId));
        array_unshift($featuredReleases, $curatedRelease);
        $featuredReleases = array_slice($featuredReleases, 0, 12);
    }
}

if ($showShopArea && $curatedProductId > 0) {
    $curatedProduct = db_one(
        $conn,
        "SELECT p.*, cat.nomeCategoria
         FROM produto p
         JOIN categoria cat ON cat.idCategoria = p.idCategoria
         JOIN cliente c ON c.idCliente = p.idCliente
         WHERE p.idProduto = {$curatedProductId}
           AND p.estado = 'aprovado'
           AND p.ativo = 1
           AND c.estado = 'ativo'
         LIMIT 1"
    );
    if ($curatedProduct) {
        $featuredProducts = array_values(array_filter($featuredProducts, static fn($product) => (int)$product['idProduto'] !== $curatedProductId));
        array_unshift($featuredProducts, $curatedProduct);
        $featuredProducts = array_slice($featuredProducts, 0, 10);
    }
}

$homeStats = [
    'tracks' => $showMusicArea ? (int)(db_one($conn, "SELECT COUNT(*) AS total FROM faixa WHERE estado = 'aprovada' AND ativo = 1")['total'] ?? 0) : 0,
    'artists' => $showArtistArea ? (int)(db_one($conn, "SELECT COUNT(*) AS total FROM ({$publicArtistsSql}) public_artists")['total'] ?? 0) : 0,
    'products' => $showShopArea ? (int)(db_one($conn, "SELECT COUNT(*) AS total FROM produto p JOIN cliente c ON c.idCliente = p.idCliente WHERE p.estado = 'aprovado' AND p.ativo = 1 AND c.estado = 'ativo'")['total'] ?? 0) : 0,
];
$heroSpotlight = $featuredReleases[0] ?? null;
$homeMusicCloud = [];
$homeArtistCloud = [];
$homeStoreCloud = [];
$addHomeMedia = static function (array &$bucket, ?string $url, string $label = '', string $type = 'all'): void {
    if (!$url) return;
    $bucket[$url] = ['src' => $url, 'label' => $label, 'type' => $type];
};
// Build lightweight image pools for the animated homepage backgrounds.
foreach ($featuredReleases as $release) {
    $addHomeMedia($homeMusicCloud, asset_url('img', $release['capa']), (string)$release['release_titulo'], 'music');
}
foreach ($featuredArtists as $artist) {
    $addHomeMedia($homeArtistCloud, asset_url('img', $artist['foto']), (string)$artist['nome'], 'artist');
    $addHomeMedia($homeArtistCloud, asset_url('img', $artist['banner'] ?? ''), (string)$artist['nome'], 'artist');
}
foreach ($featuredProducts as $product) {
    $productImage = product_main_image($conn, (int)$product['idProduto']);
    $addHomeMedia($homeStoreCloud, asset_url('img', $productImage), (string)$product['nomeProduto'], 'store');
}
$homeMediaCloud = array_values(array_slice($homeMusicCloud + $homeArtistCloud + $homeStoreCloud, 0, 24));
$homeMusicCloud = array_values(array_slice($homeMusicCloud, 0, 18));
$homeArtistCloud = array_values(array_slice($homeArtistCloud, 0, 18));
$homeStoreCloud = array_values(array_slice($homeStoreCloud, 0, 18));

include '../includes/header.php';
?>

<section class="home-hero">
  <div class="home-media-cloud" data-media-cloud='<?= h(json_encode($homeMediaCloud, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE)) ?>' aria-hidden="true"></div>
  <div class="wrap">
    <div class="home-hero-panel">
      <div class="home-hero-flipper">
        <div class="home-hero-face home-hero-face--front">
          <button type="button" class="hero-flip-logo" data-hero-flip aria-expanded="false" aria-label="Flip to visual mode">
            <span aria-hidden="true"></span>
          </button>

          <div class="home-hero-grid">
            <div class="home-hero-copy home-hero-copy--fresh home-hero-copy--editorial">
              <div class="home-hero-stack">
                <h1 data-t="home_title">Uma plataforma independente para música e merch.</h1>
                <p data-t="home_intro">Ouve lançamentos, descobre artistas e compra merch oficial.</p>
                <div class="hero-actions">
                  <?php if ($showMusicArea): ?><a href="music.php" class="btn btn-dark btn-lg" data-t="home_cta_music">Explore music</a><?php endif; ?>
                  <?php if ($showShopArea): ?><a href="shop.php" class="btn btn-outline btn-lg" data-t="home_cta_shop">Shop merch</a><?php endif; ?>
                </div>
              </div>

              <div class="home-hero-stat-strip stg">
                <?php if ($showMusicArea): ?><div class="home-hero-stat">
                  <strong><?= $homeStats['tracks'] ?></strong>
                  <span data-t="home_stat_tracks">Approved tracks</span>
                </div><?php endif; ?>
                <?php if ($showArtistArea): ?><div class="home-hero-stat">
                  <strong><?= $homeStats['artists'] ?></strong>
                  <span data-t="home_stat_artists">Active artists</span>
                </div><?php endif; ?>
                <?php if ($showShopArea): ?><div class="home-hero-stat">
                  <strong><?= $homeStats['products'] ?></strong>
                  <span data-t="home_stat_merch">Merch products</span>
                </div><?php endif; ?>
              </div>
            </div>

            <div class="home-hero-side">
                <div class="home-hero-card home-hero-card--spotlight">
                <div class="home-hero-card-head">
                  <h3 data-t="home_panel_title">Featured today</h3>
                </div>
                <?php if ($heroSpotlight): ?>
                  <?php
                  $heroCover = asset_url('img', $heroSpotlight['capa']);
                  $heroAudio = asset_url('audio', $heroSpotlight['first_track_audio']);
                  $heroArtistFoto = asset_url('img', $heroSpotlight['artist_foto']);
                  ?>
                  <a href="release.php?id=<?= (int)$heroSpotlight['idRelease'] ?>" class="home-hero-spotlight">
                    <div class="home-hero-spotlight-media">
                      <?php if ($heroCover): ?>
                        <img src="<?= h($heroCover) ?>" alt="<?= h($heroSpotlight['release_titulo']) ?>">
                      <?php endif; ?>
                      <?php if (!empty($heroSpotlight['first_track_audio'])): ?>
                        <button
                          type="button"
                          class="pbt home-hero-spotlight-play"
                          data-t="release_play_track"
                          onclick="event.preventDefault(); event.stopPropagation(); playTrack('<?= h(addslashes($heroSpotlight['first_track_title'])) ?>','<?= h(addslashes($heroSpotlight['artist_nome'])) ?>','<?= h($heroCover) ?>','<?= h($heroAudio) ?>',<?= (int)$heroSpotlight['artistId'] ?>,'<?= h($heroArtistFoto) ?>',<?= (int)$heroSpotlight['first_track_id'] ?>)"
                        >Play</button>
                      <?php endif; ?>
                    </div>
                    <div class="home-hero-spotlight-copy">
                      <span class="home-hero-spotlight-kicker" data-t="home_spotlight_label">Now in focus</span>
                      <strong><?= h($heroSpotlight['release_titulo']) ?></strong>
                      <p><?= h($heroSpotlight['artist_nome']) ?> - <?= h(release_type_label($heroSpotlight['tipo'])) ?></p>
                    </div>
                  </a>
                <?php endif; ?>
                <div class="home-hero-feature-pills home-hero-highlights">
                  <?php if (!empty($featuredArtists[0])): ?>
                    <?php
                    $featuredArtistImage = asset_url('img', $featuredArtists[0]['foto'] ?: ($featuredArtists[0]['banner'] ?? ''));
                    $featuredArtistBg = asset_url('img', $featuredArtists[0]['banner'] ?: ($featuredArtists[0]['foto'] ?? ''));
                    ?>
                    <a href="artist.php?id=<?= (int)$featuredArtists[0]['idCliente'] ?>" class="home-hero-highlight home-hero-highlight--artist" <?php if ($featuredArtistBg): ?>style="--highlight-bg:url('<?= h($featuredArtistBg) ?>')"<?php endif; ?>>
                      <span class="home-hero-highlight-media">
                        <?php if ($featuredArtistImage): ?><img src="<?= h($featuredArtistImage) ?>" alt="<?= h($featuredArtists[0]['nome']) ?>"><?php endif; ?>
                      </span>
                      <span class="home-hero-highlight-copy">
                        <small data-t="home_panel_artist">Artist</small>
                        <strong><?= h($featuredArtists[0]['nome']) ?></strong>
                        <em data-count-type="release" data-count-value="<?= (int)$featuredArtists[0]['total_releases'] ?>"><?= h(count_label((int)$featuredArtists[0]['total_releases'], 'release')) ?></em>
                      </span>
                    </a>
                  <?php endif; ?>
                  <?php if (!empty($featuredProducts[0])): ?>
                    <?php
                    $featuredProductImage = product_main_image($conn, (int)$featuredProducts[0]['idProduto']);
                    $featuredProductImageUrl = asset_url('img', $featuredProductImage);
                    ?>
                    <a href="produto.php?id=<?= (int)$featuredProducts[0]['idProduto'] ?>" class="home-hero-highlight">
                      <span class="home-hero-highlight-media">
                        <?php if ($featuredProductImageUrl): ?><img src="<?= h($featuredProductImageUrl) ?>" alt="<?= h($featuredProducts[0]['nomeProduto']) ?>"><?php endif; ?>
                      </span>
                      <span class="home-hero-highlight-copy">
                        <small data-t="home_panel_merch_title">Merch pick</small>
                        <strong><?= h($featuredProducts[0]['nomeProduto']) ?></strong>
                        <em><?= h(format_eur((float)$featuredProducts[0]['precoAtual'])) ?></em>
                      </span>
                    </a>
                  <?php endif; ?>
                </div>
              </div>
            </div>
          </div>
        </div>
        <div class="home-hero-face home-hero-face--back">
          <div class="hero-visual-cloud" data-media-cloud='<?= h(json_encode($homeMediaCloud, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE)) ?>' aria-hidden="true"></div>
          <div class="hero-visual-head">
            <div>
              <span class="auth-kicker" data-t="home_visual_label">Greenerry visual mode</span>
              <h2 data-t="home_visual_title">Pick a world to float through.</h2>
            </div>
            <button type="button" class="hero-flip-logo hero-flip-logo--back" data-hero-unflip aria-label="Flip back to hero">
              <span aria-hidden="true"></span>
            </button>
          </div>
          <div class="hero-visual-controls" aria-label="3D filters">
            <button type="button" class="on" data-media-cloud-filter="all" data-t="home_visual_all">All</button>
            <button type="button" data-media-cloud-filter="music" data-t="home_visual_music">Music</button>
            <button type="button" data-media-cloud-filter="artist" data-t="home_visual_artists">Artists</button>
            <button type="button" data-media-cloud-filter="store" data-t="home_visual_store">Store</button>
          </div>
          <div class="hero-visual-fallback" aria-hidden="true">
            <?php foreach (array_slice($homeMediaCloud, 0, 9) as $index => $item): ?>
              <span style="--i:<?= (int)$index ?>; background-image:url('<?= h($item['src']) ?>')"></span>
            <?php endforeach; ?>
          </div>
        </div>
      </div>
    </div>
  </div>
</section>

<?php if ($showMusicArea): ?>
<section class="content-shell content-shell--cloud home-section--tracks">
  <div class="section-media-cloud" data-media-cloud='<?= h(json_encode($homeMusicCloud, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE)) ?>' aria-hidden="true"></div>
  <div class="wrap">
    <div class="section-band">
      <div class="page-intro">
        <span class="slabel" data-t="home_tracks_label">Latest tracks</span>
        <h2 data-t="home_tracks_title">Listen to the latest</h2>
      </div>
      <a href="music.php" class="btn btn-ghost btn-sm" data-t="home_tracks_cta">Open music</a>
    </div>

    <div class="grid stg home-track-grid">
      <?php foreach ($featuredReleases as $release): ?>
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
            <div class="sub" data-count-type="track" data-count-value="<?= (int)$release['total_faixas'] ?>"><?= h(count_label((int)$release['total_faixas'], 'track')) ?></div>
          </div>
        </a>
      <?php endforeach; ?>
    </div>
  </div>
</section>
<?php endif; ?>

<?php if ($showArtistArea): ?>
<section class="content-shell content-shell--soft content-shell--cloud home-section--artists">
  <div class="section-media-cloud" data-media-cloud='<?= h(json_encode($homeArtistCloud, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE)) ?>' aria-hidden="true"></div>
  <div class="wrap">
    <div class="section-band">
      <div class="page-intro">
        <span class="slabel" data-t="home_artists_label">Artists</span>
        <h2 data-t="home_artists_title">Meet the artists</h2>
      </div>
      <a href="artists.php" class="btn btn-ghost btn-sm" data-t="home_artists_cta">See all artists</a>
    </div>

    <div class="grid-art stg home-artist-grid">
      <?php foreach ($featuredArtists as $artist): ?>
        <a href="artist.php?id=<?= (int)$artist['idCliente'] ?>" class="acard acard--panel">
          <div class="avatar">
            <?php if (!empty($artist['foto'])): ?>
              <img src="<?= h(asset_url('img', $artist['foto'])) ?>" alt="<?= h($artist['nome']) ?>">
            <?php endif; ?>
          </div>
          <h4><?= h($artist['nome']) ?></h4>
          <p data-count-type="release" data-count-value="<?= (int)$artist['total_releases'] ?>"><?= h(count_label((int)$artist['total_releases'], 'release')) ?></p>
        </a>
      <?php endforeach; ?>
    </div>
  </div>
</section>
<?php endif; ?>

<?php if ($showShopArea): ?>
<section class="content-shell content-shell--cloud">
  <div class="section-media-cloud" data-media-cloud='<?= h(json_encode($homeStoreCloud, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE)) ?>' aria-hidden="true"></div>
  <div class="wrap">
    <div class="section-band">
      <div class="page-intro">
        <span class="slabel" data-t="home_merch_label">Merch</span>
        <h2 data-t="home_merch_title">Selected merch</h2>
      </div>
      <a href="shop.php" class="btn btn-ghost btn-sm" data-t="home_merch_cta">Visit store</a>
    </div>

    <div class="grid stg home-store-grid">
      <?php foreach ($featuredProducts as $product): ?>
        <a href="produto.php?id=<?= (int)$product['idProduto'] ?>" class="mcard">
          <div class="cover">
            <?php $productImage = product_main_image($conn, (int)$product['idProduto']); ?>
            <?php if ($productImage !== ''): ?>
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
  </div>
</section>
<?php endif; ?>

<?php include '../includes/footer.php'; ?>
