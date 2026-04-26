<?php
require_once '../includes/config.php';

$featuredTracks = db_all(
    $conn,
    "SELECT
        f.idFaixa,
        f.titulo AS faixa_titulo,
        f.ficheiro_audio,
        r.idRelease,
        r.titulo AS release_titulo,
        r.tipo,
        r.capa,
        r.data_lancamento,
        c.idCliente AS artistId,
        c.nome AS artist_nome,
        c.foto AS artist_foto
     FROM faixa f
     JOIN release_musical r ON r.idRelease = f.idRelease
     JOIN cliente c ON c.idCliente = r.idCliente
     WHERE r.estado = 'aprovado'
       AND r.ativo = 1
       AND f.estado = 'aprovada'
       AND f.ativo = 1
       AND c.estado = 'ativo'
     ORDER BY COALESCE(r.data_lancamento, DATE(r.created_at)) DESC, r.idRelease DESC, f.numero_faixa ASC
     LIMIT 12"
);

$featuredArtists = db_all($conn, "SELECT * FROM vw_artistas_publicos ORDER BY total_releases DESC, nome ASC LIMIT 6");
$featuredProducts = db_all(
    $conn,
    "SELECT p.*, cat.nomeCategoria
     FROM produto p
     JOIN categoria cat ON cat.idCategoria = p.idCategoria
     WHERE p.estado = 'aprovado' AND p.ativo = 1
     ORDER BY p.created_at DESC
     LIMIT 4"
);

include '../includes/header.php';
?>

<section class="home-hero">
  <div class="wrap home-hero-grid">
    <div class="home-hero-copy home-hero-copy--fresh">
      <span class="auth-kicker" data-t="home_kicker">Music. Merch. Identity.</span>
      <h1 data-t="home_title">A premium space for artists and listeners.</h1>
      <p data-t="home_intro">Stream releases, discover artists, and shop official merch.</p>
      <div class="hero-actions">
        <a href="music.php" class="btn btn-dark btn-lg" data-t="home_cta_music">Explore music</a>
        <a href="shop.php" class="btn btn-outline btn-lg" data-t="home_cta_shop">Shop merch</a>
      </div>
    </div>

    <div class="home-hero-side">
      <div class="home-hero-card">
        <div class="home-hero-card-head">
          <span class="badge badge-dark" data-t="home_panel_badge">Platform</span>
          <h3 data-t="home_panel_title">Built to feel complete</h3>
        </div>
        <div class="simple-list">
          <div class="simple-list-item">
            <div>
              <strong data-t="home_panel_streaming_title">Streaming catalog</strong>
              <p data-t="home_panel_streaming_text">Singles, EPs and albums with artist profiles.</p>
            </div>
          </div>
          <div class="simple-list-item">
            <div>
              <strong data-t="home_panel_merch_title">Merch store</strong>
              <p data-t="home_panel_merch_text">Products, stock, sizes, checkout and receipt generation.</p>
            </div>
          </div>
          <div class="simple-list-item">
            <div>
              <strong data-t="home_panel_admin_title">Admin review flow</strong>
              <p data-t="home_panel_admin_text">Content approval, messages, and manual password recovery.</p>
            </div>
          </div>
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
      <?php foreach ($featuredTracks as $track): ?>
        <?php
        $cover = asset_url('img', $track['capa']);
        $audio = asset_url('audio', $track['ficheiro_audio']);
        $artistFoto = asset_url('img', $track['artist_foto']);
        $payload = [
            'id' => (int)$track['idFaixa'],
            'title' => $track['faixa_titulo'],
            'artist' => $track['artist_nome'],
            'cover' => $cover,
            'audio' => $audio,
            'artistId' => (int)$track['artistId'],
            'artistFoto' => $artistFoto,
            'type' => $track['tipo'],
            'releaseKey' => $track['idRelease'] . '-' . $track['artistId']
        ];
        ?>
        <div class="mcard" onclick="playTrack('<?= h(addslashes($track['faixa_titulo'])) ?>','<?= h(addslashes($track['artist_nome'])) ?>','<?= h($cover) ?>','<?= h($audio) ?>',<?= (int)$track['artistId'] ?>,'<?= h($artistFoto) ?>',<?= (int)$track['idFaixa'] ?>)" data-track='<?= json_encode($payload) ?>'>
          <div class="cover">
            <?php if ($cover): ?>
              <img src="<?= h($cover) ?>" alt="<?= h($track['faixa_titulo']) ?>">
            <?php endif; ?>
            <div class="cover-ov"><button class="pbt">Play</button></div>
          </div>
          <div class="meta">
            <span class="badge badge-dark"><?= h($track['tipo']) ?></span>
            <h4><?= h($track['faixa_titulo']) ?></h4>
            <div class="sub"><?= h($track['artist_nome']) ?> • <?= h($track['release_titulo']) ?></div>
          </div>
        </div>
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
