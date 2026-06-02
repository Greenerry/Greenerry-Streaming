<?php
require_once '../includes/config.php';
require_user_login();

$uid = current_user_id();
$summary = db_one_prepared(
    $conn,
    "SELECT
        (SELECT COUNT(*) FROM release_musical WHERE idCliente = ?) AS releases_count,
        (SELECT COUNT(*) FROM produto WHERE idCliente = ?) AS products_count,
        (SELECT COUNT(DISTINCT idEncomenda) FROM encomenda_item WHERE idArtista = ?) AS orders_count,
        (SELECT COALESCE(SUM(valor_artista), 0) FROM encomenda_item WHERE idArtista = ? AND estado_item = 'entregue') AS revenue_total,
        (SELECT COUNT(*) FROM faixa_listen WHERE idArtista = ?) AS listens_count,
        (SELECT COUNT(DISTINCT idCliente) FROM faixa_listen WHERE idArtista = ? AND idCliente IS NOT NULL) AS listeners_count",
    'iiiiii',
    [$uid, $uid, $uid, $uid, $uid, $uid]
) ?: [];

$topTracks = db_all_prepared(
    $conn,
    "SELECT f.idFaixa, f.titulo, f.genero, r.capa, COUNT(fl.idListen) AS listens, COUNT(DISTINCT fl.idCliente) AS listeners
     FROM faixa f
     JOIN release_musical r ON r.idRelease = f.idRelease
     LEFT JOIN faixa_listen fl ON fl.idFaixa = f.idFaixa
     WHERE r.idCliente = ?
     GROUP BY f.idFaixa, f.titulo, f.genero, r.capa
     ORDER BY listens DESC, f.idFaixa DESC
     LIMIT 8",
    'i',
    [$uid]
);

$listenMonths = db_all_prepared(
    $conn,
    "SELECT DATE_FORMAT(criado_em, '%m/%Y') AS label, COUNT(*) AS listens
     FROM faixa_listen
     WHERE idArtista = ?
       AND criado_em >= DATE_SUB(CURDATE(), INTERVAL 5 MONTH)
     GROUP BY DATE_FORMAT(criado_em, '%Y-%m'), DATE_FORMAT(criado_em, '%m/%Y')
     ORDER BY DATE_FORMAT(criado_em, '%Y-%m') ASC",
    'i',
    [$uid]
);
$maxListens = 0;
foreach ($listenMonths as $month) {
    $maxListens = max($maxListens, (int)$month['listens']);
}

include '../includes/header.php';
?>

<section class="content-shell">
  <div class="wrap">
    <header class="client-revenue-top artist-side-head">
      <div>
        <span class="slabel" data-t="artist_side_label">Área de artista</span>
        <h2 data-t="artist_side_title">Artist side</h2>
        <p data-t="artist_side_intro">Publica música, vende merchandise, acompanha encomendas, receitas e ouvintes.</p>
      </div>
      <nav class="artist-side-actions">
        <a href="upload_music.php" class="btn btn-dark btn-sm" data-t="nav_upload_music">Publicar música</a>
        <a href="upload_merch.php" class="btn btn-ghost btn-sm" data-t="nav_upload_merch">Publicar merchandise</a>
        <a href="orders.php" class="btn btn-ghost btn-sm" data-t="nav_orders">Pedidos</a>
        <a href="revenue.php" class="btn btn-ghost btn-sm" data-t="nav_revenue">Rendimento</a>
      </nav>
    </header>

    <div class="client-revenue-kpis">
      <article><span data-t="artist_side_releases">Lançamentos</span><strong><?= (int)($summary['releases_count'] ?? 0) ?></strong></article>
      <article><span data-t="artist_side_products">Produtos</span><strong><?= (int)($summary['products_count'] ?? 0) ?></strong></article>
      <article><span data-t="artist_side_orders">Pedidos</span><strong><?= (int)($summary['orders_count'] ?? 0) ?></strong></article>
      <article><span data-t="artist_side_revenue">Receita entregue</span><strong><?= h(format_eur((float)($summary['revenue_total'] ?? 0))) ?></strong></article>
    </div>

    <div class="client-revenue-grid">
      <article class="client-revenue-card client-revenue-bars-card">
        <div class="client-revenue-card-head">
          <div>
            <span class="slabel" data-t="artist_side_listener_chart">Ouvintes</span>
            <h3><?= (int)($summary['listens_count'] ?? 0) ?> <span data-t="artist_side_listens">reproduções</span></h3>
          </div>
          <span class="badge badge-light"><?= (int)($summary['listeners_count'] ?? 0) ?> <span data-t="artist_side_unique_listeners">ouvintes</span></span>
        </div>
        <?php if (!$listenMonths): ?>
          <p data-t="artist_side_no_listens">Ainda não existem dados de escuta.</p>
        <?php else: ?>
          <div class="client-revenue-bars">
            <?php foreach ($listenMonths as $month): ?>
              <?php $height = $maxListens > 0 ? max(12, (int)round(((int)$month['listens'] / $maxListens) * 100)) : 12; ?>
              <div>
                <span><?= (int)$month['listens'] ?></span>
                <i style="height: <?= $height ?>%"></i>
                <strong><?= h($month['label']) ?></strong>
              </div>
            <?php endforeach; ?>
          </div>
        <?php endif; ?>
      </article>

      <article class="client-revenue-card client-revenue-products-card">
        <div class="client-revenue-card-head">
          <div>
            <span class="slabel" data-t="artist_side_top_tracks">Faixas</span>
            <h3 data-t="artist_side_top_tracks_title">Mais ouvidas</h3>
          </div>
        </div>
        <?php if (!$topTracks): ?>
          <p data-t="artist_side_no_tracks">Ainda não submeteste músicas.</p>
        <?php else: ?>
          <div class="client-revenue-product-list">
            <?php foreach ($topTracks as $track): ?>
              <?php $width = (int)($summary['listens_count'] ?? 0) > 0 ? max(8, (int)round(((int)$track['listens'] / (int)$summary['listens_count']) * 100)) : 8; ?>
              <div class="client-revenue-product-row">
                <div>
                  <strong><?= h($track['titulo']) ?></strong>
                  <p><?= h($track['genero'] ?: (current_lang() === 'en' ? 'No genre' : 'Sem género')) ?> · <?= (int)$track['listeners'] ?> <span data-t="artist_side_unique_listeners">ouvintes</span></p>
                </div>
                <span><?= (int)$track['listens'] ?></span>
                <div class="client-revenue-product-track"><div style="width: <?= $width ?>%"></div></div>
              </div>
            <?php endforeach; ?>
          </div>
        <?php endif; ?>
      </article>
    </div>
  </div>
</section>

<?php include '../includes/footer.php'; ?>
