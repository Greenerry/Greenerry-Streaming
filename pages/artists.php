<?php
require_once '../includes/config.php';

$search = trim($_GET['q'] ?? '');
$searchSafe = db_escape($conn, $search);
$searchSql = $search !== '' ? "AND c.nome LIKE '%{$searchSafe}%'" : '';
$artists = db_all(
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
     ORDER BY total_releases DESC, nome ASC"
);

include '../includes/header.php';
?>

<section class="content-shell">
  <div class="wrap">
    <div class="catalog-hero">
      <div>
        <span class="slabel" data-t="artists_label">Artists</span>
        <h1 data-t="artists_title">Discover the artists</h1>
      </div>

      <form method="get" class="catalog-filter catalog-filter--single">
        <input type="text" name="q" value="<?= h($search) ?>" class="finput" data-tp="artists_search_placeholder" placeholder="Search artists">
        <button type="submit" class="btn btn-dark" data-t="artists_search">Search</button>
      </form>
    </div>

    <?php if (!$artists): ?>
      <div class="card surface-card">
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
                <?php if (!empty($artist['bio'])): ?>
                  <p><?= h($artist['bio']) ?></p>
                <?php endif; ?>
              </div>
              <div class="artist-panel-stats">
                <span><?= (int)$artist['total_releases'] ?> releases</span>
                <span><?= (int)$artist['total_faixas'] ?> tracks</span>
              </div>
            </div>
          </a>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>
  </div>
</section>

<?php include '../includes/footer.php'; ?>
