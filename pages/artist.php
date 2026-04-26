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

$releases = db_all(
    $conn,
    "SELECT r.*, COUNT(f.idFaixa) AS total_faixas
     FROM release_musical r
     LEFT JOIN faixa f ON f.idRelease = r.idRelease AND f.estado = 'aprovada' AND f.ativo = 1
     WHERE r.idCliente = {$artistId}
       AND r.estado = 'aprovado'
       AND r.ativo = 1
     GROUP BY r.idRelease
     ORDER BY COALESCE(r.data_lancamento, DATE(r.created_at)) DESC, r.idRelease DESC"
);

$tracks = db_all(
    $conn,
    "SELECT
        f.idFaixa,
        f.titulo,
        f.numero_faixa,
        f.ficheiro_audio,
        r.idRelease,
        r.titulo AS release_titulo,
        r.tipo,
        r.capa
     FROM faixa f
     JOIN release_musical r ON r.idRelease = f.idRelease
     WHERE r.idCliente = {$artistId}
       AND r.estado = 'aprovado'
       AND r.ativo = 1
       AND f.estado = 'aprovada'
       AND f.ativo = 1
     ORDER BY COALESCE(r.data_lancamento, DATE(r.created_at)) DESC, r.idRelease DESC, f.numero_faixa ASC"
);

$products = db_all(
    $conn,
    "SELECT p.*, cat.nomeCategoria
     FROM produto p
     JOIN categoria cat ON cat.idCategoria = p.idCategoria
     WHERE p.idCliente = {$artistId}
       AND p.estado = 'aprovado'
       AND p.ativo = 1
     ORDER BY p.created_at DESC"
);

$viewerId = current_user_id();
$isOwnArtistPage = $viewerId > 0 && $viewerId === $artistId;
$isFollowingArtist = false;
$followMessage = '';

if ($viewerId > 0 && !$isOwnArtistPage) {
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['artist_action'] ?? '') === 'toggle_follow') {
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
            $followMessage = 'Deixaste de seguir este artista.';
        } else {
            mysqli_query(
                $conn,
                "INSERT INTO seguir_artista (idSeguidor, idArtista)
                 VALUES ({$viewerId}, {$artistId})"
            );
            $followMessage = 'Agora segues este artista.';
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
        <div class="stat-val"><?= count($releases) ?></div>
        <div class="stat-lbl" data-t="artist_stat_releases">Releases</div>
      </div>
      <div class="stat">
        <div class="stat-val"><?= count($tracks) ?></div>
        <div class="stat-lbl" data-t="artist_stat_tracks">Tracks</div>
      </div>
      <div class="stat">
        <div class="stat-val"><?= count($products) ?></div>
        <div class="stat-lbl" data-t="artist_stat_products">Products</div>
      </div>
    </div>

    <div class="page-intro mt8">
      <span class="slabel" data-t="artist_releases_label">Releases</span>
      <h2 data-t="artist_releases_title">Releases</h2>
    </div>

    <div class="grid stg">
      <?php foreach ($releases as $release): ?>
        <div class="mcard">
          <div class="cover">
            <?php if (!empty($release['capa'])): ?>
              <img src="<?= h(asset_url('img', $release['capa'])) ?>" alt="<?= h($release['titulo']) ?>">
            <?php endif; ?>
          </div>
          <div class="meta">
            <span class="badge badge-dark"><?= h($release['tipo']) ?></span>
            <h4><?= h($release['titulo']) ?></h4>
            <div class="sub"><?= (int)$release['total_faixas'] ?> track(s)</div>
          </div>
        </div>
      <?php endforeach; ?>
    </div>

    <div class="page-intro mt8">
      <span class="slabel" data-t="artist_tracks_label">Tracks</span>
      <h2 data-t="artist_tracks_title">Tracks</h2>
    </div>

    <div class="grid stg">
      <?php foreach ($tracks as $track): ?>
        <?php
        $payload = [
            'id' => (int)$track['idFaixa'],
            'title' => $track['titulo'],
            'artist' => $artist['nome'],
            'cover' => asset_url('img', $track['capa']),
            'audio' => asset_url('audio', $track['ficheiro_audio']),
            'artistId' => $artistId,
            'artistFoto' => asset_url('img', $artist['foto']),
            'type' => $track['tipo'],
            'releaseKey' => $track['idRelease'] . '-' . $artistId
        ];
        ?>
        <div class="mcard" onclick="playTrack('<?= h(addslashes($track['titulo'])) ?>','<?= h(addslashes($artist['nome'])) ?>','<?= h($payload['cover']) ?>','<?= h($payload['audio']) ?>',<?= $artistId ?>,'<?= h($payload['artistFoto']) ?>',<?= (int)$track['idFaixa'] ?>)" data-track='<?= json_encode($payload) ?>'>
          <div class="cover">
            <?php if (!empty($track['capa'])): ?>
              <img src="<?= h(asset_url('img', $track['capa'])) ?>" alt="<?= h($track['titulo']) ?>">
            <?php endif; ?>
            <div class="cover-ov"><button class="pbt">Play</button></div>
          </div>
          <div class="meta">
            <h4><?= h($track['titulo']) ?></h4>
            <div class="sub"><?= h($track['release_titulo']) ?></div>
          </div>
        </div>
      <?php endforeach; ?>
    </div>

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
    <?php endif; ?>
  </div>
</section>

<?php include '../includes/footer.php'; ?>
