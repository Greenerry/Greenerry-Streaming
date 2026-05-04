<?php
require_once '../includes/config.php';
require_user_login();

$uid = current_user_id();

$summary = db_one(
    $conn,
    "SELECT
        COUNT(DISTINCT ei.idEncomenda) AS total_orders,
        SUM(ei.quantidade) AS total_items,
        SUM(ei.valor_artista) AS total_artist_value,
        SUM(ei.comissao_valor) AS total_commission
     FROM encomenda_item ei
     WHERE ei.idArtista = {$uid}
       AND ei.estado_item = 'entregue'"
) ?: [];

$sales = db_all(
    $conn,
    "SELECT
        ei.*,
        e.created_at,
        e.estado_encomenda
     FROM encomenda_item ei
     JOIN encomenda e ON e.idEncomenda = ei.idEncomenda
     WHERE ei.idArtista = {$uid}
     ORDER BY e.created_at DESC, ei.idEncomendaItem DESC
     LIMIT 25"
);

$monthlyRevenue = db_all(
    $conn,
    "SELECT
        DATE_FORMAT(e.created_at, '%Y-%m') AS period_key,
        DATE_FORMAT(e.created_at, '%m/%Y') AS period_label,
        COALESCE(SUM(CASE WHEN ei.estado_item = 'entregue' THEN ei.valor_artista ELSE 0 END), 0) AS artist_value,
        COALESCE(SUM(CASE WHEN ei.estado_item = 'entregue' THEN ei.comissao_valor ELSE 0 END), 0) AS commission,
        COALESCE(SUM(CASE WHEN ei.estado_item = 'entregue' THEN ei.quantidade ELSE 0 END), 0) AS items_count
     FROM encomenda_item ei
     JOIN encomenda e ON e.idEncomenda = ei.idEncomenda
     WHERE ei.idArtista = {$uid}
       AND e.created_at >= DATE_SUB(CURDATE(), INTERVAL 5 MONTH)
     GROUP BY DATE_FORMAT(e.created_at, '%Y-%m'), DATE_FORMAT(e.created_at, '%m/%Y')
     ORDER BY period_key ASC"
);

$productRevenue = db_all(
    $conn,
    "SELECT
        ei.nome_produto,
        COALESCE(SUM(CASE WHEN ei.estado_item = 'entregue' THEN ei.valor_artista ELSE 0 END), 0) AS artist_value,
        COALESCE(SUM(CASE WHEN ei.estado_item = 'entregue' THEN ei.quantidade ELSE 0 END), 0) AS items_count
     FROM encomenda_item ei
     WHERE ei.idArtista = {$uid}
     GROUP BY ei.nome_produto
     ORDER BY artist_value DESC
     LIMIT 6"
);

$maxMonthly = 0.0;
foreach ($monthlyRevenue as $month) {
    $maxMonthly = max($maxMonthly, (float)$month['artist_value']);
}

$maxProduct = 0.0;
foreach ($productRevenue as $product) {
    $maxProduct = max($maxProduct, (float)$product['artist_value']);
}

include '../includes/header.php';
?>

<section class="content-shell">
  <div class="wrap">
    <div class="page-intro">
      <span class="slabel" data-t="revenue_label">Rendimento</span>
      <h2 data-t="revenue_title">Resumo das vendas</h2>
      <p data-t="revenue_intro">Os ganhos sao calculados a partir do valor artistico registado em cada item da encomenda.</p>
    </div>

    <div class="stats-grid">
      <div class="stat">
        <div class="stat-val"><?= h(format_eur((float)($summary['total_artist_value'] ?? 0))) ?></div>
        <div class="stat-lbl" data-t="revenue_received">Valor recebido</div>
      </div>
      <div class="stat">
        <div class="stat-val"><?= (int)($summary['total_orders'] ?? 0) ?></div>
        <div class="stat-lbl" data-t="revenue_orders">Encomendas entregues</div>
      </div>
      <div class="stat">
        <div class="stat-val"><?= (int)($summary['total_items'] ?? 0) ?></div>
        <div class="stat-lbl" data-t="revenue_items">Itens vendidos</div>
      </div>
      <div class="stat">
        <div class="stat-val"><?= h(format_eur((float)($summary['total_commission'] ?? 0))) ?></div>
        <div class="stat-lbl" data-t="revenue_commission">Comissao da plataforma</div>
      </div>
    </div>

    <section class="revenue-dashboard">
      <article class="revenue-chart-card">
        <div class="revenue-chart-head">
          <div>
            <span class="slabel" data-t="revenue_chart_label">Grafico</span>
            <h3 data-t="revenue_monthly_chart">Rendimento mensal</h3>
          </div>
          <span class="badge badge-light" data-t="box_last_six_months">Ultimos 6 meses</span>
        </div>

        <?php if (!$monthlyRevenue): ?>
          <p data-t="revenue_empty">Ainda nao tens vendas registadas.</p>
        <?php else: ?>
          <div class="revenue-bars">
            <?php foreach ($monthlyRevenue as $month): ?>
              <?php
              $value = (float)$month['artist_value'];
              $height = $maxMonthly > 0 ? max(18, (int)round(($value / $maxMonthly) * 170)) : 18;
              ?>
              <div class="revenue-bar-col">
                <span class="revenue-bar-value"><?= h(format_eur($value)) ?></span>
                <div class="revenue-bar-track"><i class="revenue-bar" style="height: <?= $height ?>px"></i></div>
                <strong><?= h($month['period_label']) ?></strong>
              </div>
            <?php endforeach; ?>
          </div>
        <?php endif; ?>
      </article>

      <article class="revenue-chart-card">
        <div class="revenue-chart-head">
          <div>
            <span class="slabel" data-t="revenue_products_label">Produtos</span>
            <h3 data-t="revenue_products_chart">Top produtos</h3>
          </div>
        </div>

        <?php if (!$productRevenue): ?>
          <p data-t="revenue_empty">Ainda nao tens vendas registadas.</p>
        <?php else: ?>
          <div class="revenue-split">
            <?php foreach ($productRevenue as $product): ?>
              <?php $width = $maxProduct > 0 ? max(8, (int)round(((float)$product['artist_value'] / $maxProduct) * 100)) : 8; ?>
              <div class="revenue-split-row">
                <strong><?= h($product['nome_produto']) ?></strong>
                <span><?= h(format_eur((float)$product['artist_value'])) ?></span>
                <div class="revenue-split-track"><i style="width: <?= $width ?>%"></i></div>
              </div>
            <?php endforeach; ?>
          </div>
        <?php endif; ?>
      </article>
    </section>

    <div class="card surface-card mt8">
      <div class="card-body">
        <h3 class="section-card-title" data-t="revenue_recent">Movimentos recentes</h3>

        <?php if (!$sales): ?>
          <p data-t="revenue_empty">Ainda nao tens vendas registadas.</p>
        <?php else: ?>
          <div class="tbl-wrap">
            <table>
              <thead>
                <tr>
                  <th>Encomenda</th>
                  <th>Produto</th>
                  <th>Qtd</th>
                  <th>Estado</th>
                  <th>Valor artista</th>
                  <th>Comissao</th>
                  <th>Data</th>
                </tr>
              </thead>
              <tbody>
                <?php foreach ($sales as $sale): ?>
                  <tr>
                    <td><strong>#<?= (int)$sale['idEncomenda'] ?></strong></td>
                    <td><?= h($sale['nome_produto']) ?></td>
                    <td><?= (int)$sale['quantidade'] ?></td>
                    <td><span class="badge <?= h(state_badge_class($sale['estado_item'])) ?>"><?= h(order_status_label($sale['estado_item'])) ?></span></td>
                    <td><?= h(format_eur($sale['estado_item'] === 'cancelado' ? 0.0 : (float)$sale['valor_artista'])) ?></td>
                    <td><?= h(format_eur($sale['estado_item'] === 'cancelado' ? 0.0 : (float)$sale['comissao_valor'])) ?></td>
                    <td><?= date('d/m/Y', strtotime($sale['created_at'])) ?></td>
                  </tr>
                <?php endforeach; ?>
              </tbody>
            </table>
          </div>
        <?php endif; ?>
      </div>
    </div>
  </div>
</section>

<?php include '../includes/footer.php'; ?>
