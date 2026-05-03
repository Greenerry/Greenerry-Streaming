<?php
require_once '../includes/config.php';

$featuredReleases = db_all(
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
     GROUP BY r.idRelease, r.titulo, r.tipo, r.capa, r.data_lancamento, r.created_at, c.idCliente, c.nome, c.foto, first_track.idFaixa, first_track.titulo, first_track.ficheiro_audio
     ORDER BY COALESCE(r.data_lancamento, DATE(r.created_at)) DESC, r.idRelease DESC
     LIMIT 12"
);

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

$featuredArtists = db_all($conn, "{$publicArtistsSql} ORDER BY total_releases DESC, nome ASC LIMIT 6");
$featuredProducts = db_all(
    $conn,
    "SELECT p.*, cat.nomeCategoria
     FROM produto p
     JOIN categoria cat ON cat.idCategoria = p.idCategoria
     WHERE p.estado = 'aprovado' AND p.ativo = 1
     ORDER BY p.created_at DESC
     LIMIT 4"
);
$homeStats = [
    'tracks' => (int)(db_one($conn, "SELECT COUNT(*) AS total FROM faixa WHERE estado = 'aprovada' AND ativo = 1")['total'] ?? 0),
    'artists' => (int)(db_one($conn, "SELECT COUNT(*) AS total FROM ({$publicArtistsSql}) public_artists")['total'] ?? 0),
    'products' => (int)(db_one($conn, "SELECT COUNT(*) AS total FROM produto WHERE estado = 'aprovado' AND ativo = 1")['total'] ?? 0),
];
$heroSpotlight = $featuredReleases[0] ?? null;

include '../includes/header.php';
?>

<section class="home-hero">
  <div class="wrap home-hero-grid">
    <div class="home-hero-copy home-hero-copy--fresh home-hero-copy--editorial">
      <div class="home-hero-stack">
        <span class="auth-kicker" data-t="home_kicker">Music. Merch. Identity.</span>
        <h1 data-t="home_title">A premium space for artists and listeners.</h1>
        <p data-t="home_intro">Stream releases, discover artists, and shop official merch.</p>
        <div class="hero-actions">
          <a href="music.php" class="btn btn-dark btn-lg" data-t="home_cta_music">Explore music</a>
          <a href="shop.php" class="btn btn-outline btn-lg" data-t="home_cta_shop">Shop merch</a>
        </div>
      </div>

      <div class="home-hero-stat-strip stg">
        <div class="home-hero-stat">
          <strong><?= $homeStats['tracks'] ?></strong>
          <span data-t="home_stat_tracks">Approved tracks</span>
        </div>
        <div class="home-hero-stat">
          <strong><?= $homeStats['artists'] ?></strong>
          <span data-t="home_stat_artists">Active artists</span>
        </div>
        <div class="home-hero-stat">
          <strong><?= $homeStats['products'] ?></strong>
          <span data-t="home_stat_merch">Merch products</span>
        </div>
      </div>
    </div>

    <div class="home-hero-side">
      <div class="home-hero-card home-hero-card--spotlight">
        <div class="home-hero-card-head">
          <span class="badge badge-dark" data-t="home_panel_badge">Platform</span>
          <h3 data-t="home_panel_title">Built to feel complete</h3>
        </div>
        <?php if ($heroSpotlight): ?>
          <?php $heroCover = asset_url('img', $heroSpotlight['capa']); ?>
          <a href="release.php?id=<?= (int)$heroSpotlight['idRelease'] ?>" class="home-hero-spotlight">
            <div class="home-hero-spotlight-media">
              <?php if ($heroCover): ?>
                <img src="<?= h($heroCover) ?>" alt="<?= h($heroSpotlight['release_titulo']) ?>">
              <?php endif; ?>
            </div>
            <div class="home-hero-spotlight-copy">
              <span class="home-hero-spotlight-kicker" data-t="home_spotlight_label">Now in focus</span>
              <strong><?= h($heroSpotlight['release_titulo']) ?></strong>
              <p><?= h($heroSpotlight['artist_nome']) ?> - <?= h($heroSpotlight['tipo']) ?></p>
            </div>
          </a>
        <?php endif; ?>
        <div class="home-hero-feature-pills">
          <span data-t="home_panel_streaming_title">Streaming catalog</span>
          <span data-t="home_panel_merch_title">Merch store</span>
          <span data-t="home_panel_admin_title">Admin review flow</span>
        </div>
      </div>
    </div>
  </div>
</section>

<section class="content-shell">
  <div class="wrap">
    <div class="section-band">
      <div class="page-intro">
        <span class="slabel" data-t="home_tracks_label">Latest tracks</span>
        <h2 data-t="home_tracks_title">Listen to the latest</h2>
      </div>
      <a href="music.php" class="btn btn-ghost btn-sm" data-t="home_tracks_cta">Open music</a>
    </div>

    <div class="grid stg">
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
            <span class="badge badge-dark"><?= h($release['tipo']) ?></span>
            <h4><?= h($release['release_titulo']) ?></h4>
            <div class="sub"><?= h($release['artist_nome']) ?></div>
            <div class="sub"><?= (int)$release['total_faixas'] ?> <span data-t="release_tracks_count">faixas</span></div>
          </div>
        </a>
      <?php endforeach; ?>
    </div>
  </div>
</section>

<section class="content-shell content-shell--soft">
  <div class="wrap">
    <div class="section-band">
      <div class="page-intro">
        <span class="slabel" data-t="home_artists_label">Artists</span>
        <h2 data-t="home_artists_title">Meet the artists</h2>
      </div>
      <a href="artists.php" class="btn btn-ghost btn-sm" data-t="home_artists_cta">See all artists</a>
    </div>

    <div class="grid-art stg">
      <?php foreach ($featuredArtists as $artist): ?>
        <a href="artist.php?id=<?= (int)$artist['idCliente'] ?>" class="acard acard--panel">
          <div class="avatar">
            <?php if (!empty($artist['foto'])): ?>
              <img src="<?= h(asset_url('img', $artist['foto'])) ?>" alt="<?= h($artist['nome']) ?>">
            <?php endif; ?>
          </div>
          <h4><?= h($artist['nome']) ?></h4>
          <p><?= (int)$artist['total_releases'] ?> release(s)</p>
        </a>
      <?php endforeach; ?>
    </div>
  </div>
</section>

<section class="content-shell">
  <div class="wrap">
    <div class="section-band">
      <div class="page-intro">
        <span class="slabel" data-t="home_merch_label">Merch</span>
        <h2 data-t="home_merch_title">Selected merch</h2>
      </div>
      <a href="shop.php" class="btn btn-ghost btn-sm" data-t="home_merch_cta">Visit store</a>
    </div>

    <div class="grid stg">
      <?php foreach ($featuredProducts as $product): ?>
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
  </div>
</section>

<?php include '../includes/footer.php'; ?>
