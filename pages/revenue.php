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

$paidOrderStats = db_one(
    $conn,
    "SELECT
        COUNT(DISTINCT e.idEncomenda) AS paid_orders,
        COALESCE(SUM(CASE WHEN ei.estado_item != 'cancelado' THEN ei.total_linha ELSE 0 END) / NULLIF(COUNT(DISTINCT e.idEncomenda), 0), 0) AS average_order_value
     FROM encomenda_item ei
     JOIN encomenda e ON e.idEncomenda = ei.idEncomenda
     WHERE ei.idArtista = {$uid}
       AND e.estado_pagamento = 'pago'
       AND e.estado_encomenda != 'cancelada'
       AND ei.estado_item != 'cancelado'"
) ?: [];

$monthlyRevenue = db_all(
    $conn,
    "SELECT
        DATE_FORMAT(e.created_at, '%Y-%m') AS period_key,
        DATE_FORMAT(e.created_at, '%m/%Y') AS period_label,
        COALESCE(SUM(CASE WHEN ei.estado_item = 'entregue' THEN ei.valor_artista ELSE 0 END), 0) AS artist_value,
        COALESCE(SUM(CASE WHEN ei.estado_item = 'entregue' THEN ei.total_linha ELSE 0 END), 0) AS total_revenue,
        COALESCE(SUM(CASE WHEN ei.estado_item = 'entregue' THEN ei.comissao_valor ELSE 0 END), 0) AS commission,
        COALESCE(SUM(CASE WHEN ei.estado_item = 'entregue' THEN ei.quantidade ELSE 0 END), 0) AS items_count
     FROM encomenda_item ei
     JOIN encomenda e ON e.idEncomenda = ei.idEncomenda
     WHERE ei.idArtista = {$uid}
       AND e.created_at >= DATE_SUB(CURDATE(), INTERVAL 5 MONTH)
     GROUP BY DATE_FORMAT(e.created_at, '%Y-%m'), DATE_FORMAT(e.created_at, '%m/%Y')
     ORDER BY period_key ASC"
);

$orderStates = db_one(
    $conn,
    "SELECT
        SUM(ei.estado_item = 'pendente') AS pendente,
        SUM(ei.estado_item = 'em_preparacao') AS em_preparacao,
        SUM(ei.estado_item = 'enviado') AS enviado,
        SUM(ei.estado_item = 'entregue') AS entregue,
        SUM(ei.estado_item = 'cancelado') AS cancelado
     FROM encomenda_item ei
     WHERE ei.idArtista = {$uid}"
) ?: [];

$productRevenue = db_all(
    $conn,
    "SELECT
        ei.nome_produto,
        COUNT(DISTINCT ei.idEncomenda) AS orders_count,
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

$orderStateCards = [
    ['status' => 'pendente', 'label' => order_status_label('pendente'), 'value' => (int)($orderStates['pendente'] ?? 0)],
    ['status' => 'em_preparacao', 'label' => order_status_label('em_preparacao'), 'value' => (int)($orderStates['em_preparacao'] ?? 0)],
    ['status' => 'enviada', 'label' => order_status_label('enviada'), 'value' => (int)($orderStates['enviado'] ?? 0)],
    ['status' => 'entregue', 'label' => order_status_label('entregue'), 'value' => (int)($orderStates['entregue'] ?? 0)],
    ['status' => 'cancelada', 'label' => order_status_label('cancelada'), 'value' => (int)($orderStates['cancelado'] ?? 0)]
];
$totalOrderStates = array_sum(array_column($orderStateCards, 'value'));
$orderColors = ['#c9d0db', '#8b98aa', '#9dafaa', '#d7b676', '#d98596'];
$orderStops = [];
$orderCursor = 0.0;
foreach ($orderStateCards as $index => $stateCard) {
    $slice = $totalOrderStates > 0 ? ((int)$stateCard['value'] / $totalOrderStates) * 100 : 0;
    $next = min(100, $orderCursor + $slice);
    if ($next > $orderCursor) {
        $orderStops[] = $orderColors[$index] . ' ' . round($orderCursor, 2) . '% ' . round($next, 2) . '%';
    }
    $orderCursor = $next;
}
$orderDonutStyle = $orderStops
    ? 'background: conic-gradient(' . implode(', ', $orderStops) . ', rgba(255,255,255,.10) ' . round($orderCursor, 2) . '% 100%);'
    : 'background: conic-gradient(#c9d0db 0 20%, #8b98aa 20% 40%, #9dafaa 40% 60%, #d7b676 60% 80%, #d98596 80% 100%);';

$chartPoints = [];
$chartAreaPoints = [];
$chartWidth = 620;
$chartHeight = 220;
$chartPadding = 28;
$chartCount = max(1, count($monthlyRevenue) - 1);
foreach ($monthlyRevenue as $index => $entry) {
    $x = $chartPadding + ($chartCount > 0 ? ($index / $chartCount) * ($chartWidth - ($chartPadding * 2)) : 0);
    $ratio = $maxMonthly > 0 ? ((float)$entry['artist_value'] / $maxMonthly) : 0;
    $y = ($chartHeight - $chartPadding) - ($ratio * ($chartHeight - ($chartPadding * 2)));
    $chartPoints[] = round($x, 1) . ',' . round($y, 1);
}
if ($chartPoints) {
    $firstX = explode(',', $chartPoints[0])[0];
    $lastX = explode(',', $chartPoints[count($chartPoints) - 1])[0];
    $chartAreaPoints = array_merge([$firstX . ',' . ($chartHeight - $chartPadding)], $chartPoints, [$lastX . ',' . ($chartHeight - $chartPadding)]);
}

include '../includes/header.php';
?>

<section class="content-shell">
  <div class="wrap">
    <section class="client-revenue-dashboard">
      <header class="client-revenue-top">
        <div>
          <span class="slabel" data-t="revenue_label">Rendimento</span>
          <h2 data-t="revenue_title">Resumo das vendas</h2>
          <p data-t="revenue_intro">Os ganhos sao calculados a partir do valor artistico registado em cada item da encomenda.</p>
        </div>
      </header>

      <div class="client-revenue-kpis">
        <article>
          <span data-t="revenue_received">Valor recebido</span>
          <strong><?= h(format_eur((float)($summary['total_artist_value'] ?? 0))) ?></strong>
          <small><?= (int)($summary['total_items'] ?? 0) ?> <span data-t="revenue_items">itens vendidos</span></small>
        </article>
        <article>
          <span data-t="revenue_orders">Encomendas entregues</span>
          <strong><?= (int)($summary['total_orders'] ?? 0) ?></strong>
          <small><?= (int)($paidOrderStats['paid_orders'] ?? 0) ?> <span data-t="revenue_paid_orders">encomendas pagas</span></small>
        </article>
        <article>
          <span data-t="revenue_commission">Comissao da plataforma</span>
          <strong><?= h(format_eur((float)($summary['total_commission'] ?? 0))) ?></strong>
          <small data-t="revenue_commission_note">sobre itens entregues</small>
        </article>
        <article>
          <span data-t="revenue_avg_order">Ticket medio</span>
          <strong><?= h(format_eur((float)($paidOrderStats['average_order_value'] ?? 0))) ?></strong>
          <small data-t="revenue_avg_order_note">por encomenda paga</small>
        </article>
      </div>

      <div class="client-revenue-grid">
        <article class="client-revenue-card client-revenue-main-chart">
          <div class="client-revenue-card-head">
            <div>
              <span class="slabel" data-t="revenue_chart_label">Grafico</span>
              <h3><?= h(format_eur((float)($summary['total_artist_value'] ?? 0))) ?></h3>
            </div>
            <span class="badge badge-light" data-t="box_last_six_months">Ultimos 6 meses</span>
          </div>

          <?php if (!$monthlyRevenue): ?>
            <p data-t="revenue_empty">Ainda nao tens vendas registadas.</p>
          <?php else: ?>
            <svg class="client-revenue-line-chart" viewBox="0 0 <?= $chartWidth ?> <?= $chartHeight ?>" role="img" aria-label="Monthly revenue trend">
              <defs>
                <linearGradient id="clientRevenueGlow" x1="0" x2="0" y1="0" y2="1">
                  <stop offset="0%" stop-color="#c9d0db" stop-opacity=".42"/>
                  <stop offset="100%" stop-color="#c9d0db" stop-opacity="0"/>
                </linearGradient>
              </defs>
              <g class="client-chart-grid-lines">
                <line x1="28" y1="48" x2="592" y2="48"/>
                <line x1="28" y1="92" x2="592" y2="92"/>
                <line x1="28" y1="136" x2="592" y2="136"/>
                <line x1="28" y1="180" x2="592" y2="180"/>
              </g>
              <?php if ($chartAreaPoints): ?>
                <polygon points="<?= h(implode(' ', $chartAreaPoints)) ?>" fill="url(#clientRevenueGlow)"/>
                <polyline points="<?= h(implode(' ', $chartPoints)) ?>" fill="none" stroke-width="5" stroke-linecap="round" stroke-linejoin="round"/>
                <?php foreach ($chartPoints as $point): ?>
                  <?php [$cx, $cy] = explode(',', $point); ?>
                  <circle cx="<?= h($cx) ?>" cy="<?= h($cy) ?>" r="5"/>
                <?php endforeach; ?>
              <?php endif; ?>
            </svg>
            <div class="client-revenue-months">
              <?php foreach ($monthlyRevenue as $entry): ?>
                <span><?= h($entry['period_label']) ?></span>
              <?php endforeach; ?>
            </div>
          <?php endif; ?>
        </article>

        <article class="client-revenue-card client-revenue-donut-card">
          <div class="client-revenue-card-head">
            <div>
              <span class="slabel" data-t="nav_orders">Pedidos</span>
              <h3><?= (int)$totalOrderStates ?></h3>
            </div>
          </div>
          <div class="client-revenue-donut" style="<?= h($orderDonutStyle) ?>">
            <div><strong><?= (int)$totalOrderStates ?></strong><span data-t="orders_total">total</span></div>
          </div>
          <div class="client-revenue-legend">
            <?php foreach ($orderStateCards as $index => $stateCard): ?>
              <?php $percent = $totalOrderStates > 0 ? (int)round(((int)$stateCard['value'] / $totalOrderStates) * 100) : 0; ?>
              <div><i style="background: <?= h($orderColors[$index]) ?>"></i><span data-status-label="<?= h($stateCard['status']) ?>"><?= h($stateCard['label']) ?></span><strong><?= $percent ?>%</strong></div>
            <?php endforeach; ?>
          </div>
        </article>

        <article class="client-revenue-card client-revenue-bars-card">
          <div class="client-revenue-card-head">
            <div>
              <span class="slabel" data-t="revenue_monthly_chart">Rendimento mensal</span>
              <h3 data-t="revenue_received">Valor recebido</h3>
            </div>
          </div>
          <?php if (!$monthlyRevenue): ?>
            <p data-t="revenue_empty">Ainda nao tens vendas registadas.</p>
          <?php else: ?>
            <div class="client-revenue-bars">
              <?php foreach ($monthlyRevenue as $entry): ?>
                <?php $height = $maxMonthly > 0 ? max(14, (int)round(((float)$entry['artist_value'] / $maxMonthly) * 100)) : 14; ?>
                <div>
                  <span><?= h(format_eur((float)$entry['artist_value'])) ?></span>
                  <i style="height: <?= $height ?>%"></i>
                  <strong><?= h($entry['period_label']) ?></strong>
                </div>
              <?php endforeach; ?>
            </div>
          <?php endif; ?>
        </article>

        <article class="client-revenue-card client-revenue-products-card">
          <div class="client-revenue-card-head">
            <div>
              <span class="slabel" data-t="revenue_products_label">Produtos</span>
              <h3 data-t="revenue_products_chart">Top produtos</h3>
            </div>
          </div>
          <?php if (!$productRevenue): ?>
            <p data-t="revenue_empty">Ainda nao tens vendas registadas.</p>
          <?php else: ?>
            <div class="client-revenue-product-list">
              <?php foreach ($productRevenue as $product): ?>
                <?php $width = $maxProduct > 0 ? max(8, (int)round(((float)$product['artist_value'] / $maxProduct) * 100)) : 8; ?>
                <div class="client-revenue-product-row">
                  <div>
                    <strong><?= h($product['nome_produto']) ?></strong>
                    <p><?= (int)$product['items_count'] ?> <span data-t="revenue_items">itens vendidos</span></p>
                  </div>
                  <span><?= h(format_eur((float)$product['artist_value'])) ?></span>
                  <div class="client-revenue-product-track"><div style="width: <?= $width ?>%"></div></div>
                </div>
              <?php endforeach; ?>
            </div>
          <?php endif; ?>
        </article>
      </div>

      <div class="client-revenue-table-card">
        <div class="client-revenue-card-head">
          <div>
            <span class="slabel" data-t="revenue_recent">Movimentos recentes</span>
            <h3 data-t="revenue_recent">Movimentos recentes</h3>
          </div>
        </div>
        <?php if (!$sales): ?>
          <p data-t="revenue_empty">Ainda nao tens vendas registadas.</p>
        <?php else: ?>
          <div class="tbl-wrap">
            <table>
              <thead>
                <tr>
                  <th data-t="revenue_table_order">Encomenda</th>
                  <th data-t="revenue_table_product">Produto</th>
                  <th data-t="revenue_table_qty">Qtd</th>
                  <th data-t="revenue_table_status">Estado</th>
                  <th data-t="revenue_table_artist_value">Valor artista</th>
                  <th data-t="revenue_table_commission">Comissao</th>
                  <th data-t="revenue_table_date">Data</th>
                </tr>
              </thead>
              <tbody>
                <?php foreach ($sales as $sale): ?>
                  <tr>
                    <td><strong>#<?= (int)$sale['idEncomenda'] ?></strong></td>
                    <td><?= h($sale['nome_produto']) ?></td>
                    <td><?= (int)$sale['quantidade'] ?></td>
                    <td><span class="badge <?= h(state_badge_class($sale['estado_item'])) ?>" data-status-label="<?= h($sale['estado_item']) ?>"><?= h(order_status_label($sale['estado_item'])) ?></span></td>
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
    </section>
  </div>
</section>

<?php include '../includes/footer.php'; ?>
