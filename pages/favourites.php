<?php
require_once '../includes/config.php';

$followedArtists = [];
if (is_user_logged_in()) {
    $uid = current_user_id();
    $followedArtists = db_all(
        $conn,
        "SELECT
            c.idCliente,
            c.nome,
            c.bio,
            c.foto,
            c.banner,
            c.slug,
            COUNT(DISTINCT r.idRelease) AS total_releases,
            COUNT(DISTINCT f.idFaixa) AS total_faixas
         FROM seguir_artista sa
         JOIN cliente c
           ON c.idCliente = sa.idArtista
         LEFT JOIN release_musical r
           ON r.idCliente = c.idCliente
          AND r.estado = 'aprovado'
          AND r.ativo = 1
         LEFT JOIN faixa f
           ON f.idRelease = r.idRelease
          AND f.estado = 'aprovada'
          AND f.ativo = 1
         WHERE sa.idSeguidor = {$uid}
           AND c.estado = 'ativo'
         GROUP BY c.idCliente, c.nome, c.bio, c.foto, c.banner, c.slug, sa.created_at
         ORDER BY sa.created_at DESC"
    );
}

include '../includes/header.php';
?>

<section class="content-shell">
  <div class="wrap">
    <div class="library-hero hero-card--single">
      <div class="library-hero-copy">
        <span class="slabel">Biblioteca</span>
        <h2>A tua biblioteca.</h2>
      </div>
    </div>

    <section class="library-section">
      <div class="section-band">
        <div class="page-intro">
          <span class="slabel">Faixas</span>
          <h2>Favoritas</h2>
        </div>
      </div>

      <div id="favs-empty" class="cart-empty-state is-hidden">
        <div class="cart-empty-icon">Fav</div>
        <h3>Ainda nao tens favoritas.</h3>
        <a href="music.php" class="btn btn-ghost btn-sm">Descobrir musica</a>
      </div>

      <div class="grid stg" id="favs-grid"></div>
    </section>

    <section class="library-section">
      <div class="section-band">
        <div class="page-intro">
          <span class="slabel">Artistas</span>
          <h2>A seguir</h2>
        </div>
      </div>

      <?php if (!is_user_logged_in()): ?>
        <div class="card surface-card surface-card--soft">
          <div class="card-body">
            <p>Faz login para guardares artistas.</p>
          </div>
        </div>
      <?php elseif (!$followedArtists): ?>
        <div class="card surface-card surface-card--soft">
          <div class="card-body">
            <p>Ainda nao segues artistas.</p>
          </div>
        </div>
      <?php else: ?>
        <div class="artist-grid-panels">
          <?php foreach ($followedArtists as $artist): ?>
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
                  <?php if (!empty($artist['bio'])): ?>
                    <p><?= h($artist['bio']) ?></p>
                  <?php endif; ?>
                </div>
                <div class="artist-panel-stats">
                  <span><?= (int)$artist['total_releases'] ?> releases</span>
                  <span><?= (int)$artist['total_faixas'] ?> faixas</span>
                </div>
              </div>
            </a>
          <?php endforeach; ?>
        </div>
      <?php endif; ?>
    </section>
  </div>
</section>

<?php include '../includes/footer.php'; ?>
