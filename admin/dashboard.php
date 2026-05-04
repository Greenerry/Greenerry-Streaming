<?php
require_once '../includes/config.php';
require_admin_login();

$stats = [
    'clientes' => db_one($conn, "SELECT COUNT(*) AS total FROM cliente WHERE estado = 'ativo'")['total'] ?? 0,
    'categorias' => db_one($conn, "SELECT COUNT(*) AS total FROM categoria WHERE estado = 'ativo'")['total'] ?? 0,
    'produtos_pendentes' => db_one($conn, "SELECT COUNT(*) AS total FROM produto WHERE estado = 'pendente'")['total'] ?? 0,
    'releases_pendentes' => db_one($conn, "SELECT COUNT(*) AS total FROM release_musical WHERE estado = 'pendente'")['total'] ?? 0,
    'mensagens_abertas' => db_one($conn, "SELECT COUNT(*) AS total FROM mensagem_admin WHERE estado = 'aberta'")['total'] ?? 0,
    'resets_pendentes' => db_one($conn, "SELECT COUNT(*) AS total FROM pedido_reset_password WHERE estado IN ('pendente', 'em_analise')")['total'] ?? 0,
    'encomendas' => db_one($conn, "SELECT COUNT(*) AS total FROM encomenda")['total'] ?? 0
];
$attentionTotal = (int)$stats['produtos_pendentes'] + (int)$stats['releases_pendentes'] + (int)$stats['mensagens_abertas'] + (int)$stats['resets_pendentes'];

$finance = db_one(
    $conn,
    "SELECT
        COALESCE(SUM(CASE WHEN e.estado_pagamento = 'pago' AND e.estado_encomenda != 'cancelada' AND ei.estado_item != 'cancelado' THEN ei.total_linha END), 0) AS total_revenue,
        COALESCE(SUM(CASE WHEN e.estado_pagamento = 'pago' AND e.estado_encomenda != 'cancelada' AND ei.estado_item != 'cancelado' THEN ei.comissao_valor END), 0) AS total_commission,
        COALESCE(SUM(CASE WHEN e.estado_pagamento = 'pago' AND e.estado_encomenda != 'cancelada' AND ei.estado_item != 'cancelado' THEN ei.valor_artista END), 0) AS total_artist_base
     FROM encomenda e
     JOIN encomenda_item ei ON ei.idEncomenda = e.idEncomenda"
);
$paidOrderStats = db_one(
    $conn,
    "SELECT
        COUNT(DISTINCT e.idEncomenda) AS total_paid_orders,
        COALESCE(SUM(ei.total_linha) / NULLIF(COUNT(DISTINCT e.idEncomenda), 0), 0) AS average_order_value
     FROM encomenda e
     JOIN encomenda_item ei ON ei.idEncomenda = e.idEncomenda
     WHERE e.estado_pagamento = 'pago'
       AND e.estado_encomenda != 'cancelada'
       AND ei.estado_item != 'cancelado'"
);

$orderStates = db_one(
    $conn,
    "SELECT
        SUM(estado_encomenda = 'pendente') AS pendente,
        SUM(estado_encomenda = 'em_preparacao') AS em_preparacao,
        SUM(estado_encomenda = 'enviada') AS enviada,
        SUM(estado_encomenda = 'entregue') AS entregue,
        SUM(estado_encomenda = 'cancelada') AS cancelada
     FROM encomenda"
);

$monthlyPerformance = db_all(
    $conn,
    "SELECT
        DATE_FORMAT(e.created_at, '%Y-%m') AS period_key,
        DATE_FORMAT(e.created_at, '%m/%Y') AS period_label,
        COUNT(DISTINCT e.idEncomenda) AS total_orders,
        COALESCE(SUM(ei.comissao_valor), 0) AS total_commission,
        COALESCE(SUM(ei.total_linha), 0) AS total_revenue
     FROM encomenda e
     JOIN encomenda_item ei ON ei.idEncomenda = e.idEncomenda
     WHERE e.created_at >= DATE_SUB(CURDATE(), INTERVAL 5 MONTH)
       AND e.estado_pagamento = 'pago'
       AND e.estado_encomenda != 'cancelada'
       AND ei.estado_item != 'cancelado'
     GROUP BY DATE_FORMAT(e.created_at, '%Y-%m'), DATE_FORMAT(e.created_at, '%m/%Y')
     ORDER BY period_key ASC"
);

$maxMonthlyRevenue = 0.0;
foreach ($monthlyPerformance as $entry) {
    $maxMonthlyRevenue = max($maxMonthlyRevenue, (float)$entry['total_revenue']);
}

$orderStateCards = [
    ['label' => order_status_label('pendente'), 'value' => (int)($orderStates['pendente'] ?? 0)],
    ['label' => order_status_label('em_preparacao'), 'value' => (int)($orderStates['em_preparacao'] ?? 0)],
    ['label' => order_status_label('enviada'), 'value' => (int)($orderStates['enviada'] ?? 0)],
    ['label' => order_status_label('entregue'), 'value' => (int)($orderStates['entregue'] ?? 0)],
    ['label' => order_status_label('cancelada'), 'value' => (int)($orderStates['cancelada'] ?? 0)]
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
$chartCount = max(1, count($monthlyPerformance) - 1);
foreach ($monthlyPerformance as $index => $entry) {
    $x = $chartPadding + ($chartCount > 0 ? ($index / $chartCount) * ($chartWidth - ($chartPadding * 2)) : 0);
    $ratio = $maxMonthlyRevenue > 0 ? ((float)$entry['total_revenue'] / $maxMonthlyRevenue) : 0;
    $y = ($chartHeight - $chartPadding) - ($ratio * ($chartHeight - ($chartPadding * 2)));
    $chartPoints[] = round($x, 1) . ',' . round($y, 1);
}
if ($chartPoints) {
    $firstX = explode(',', $chartPoints[0])[0];
    $lastX = explode(',', $chartPoints[count($chartPoints) - 1])[0];
    $chartAreaPoints = array_merge([$firstX . ',' . ($chartHeight - $chartPadding)], $chartPoints, [$lastX . ',' . ($chartHeight - $chartPadding)]);
}

$pendingProducts = db_all(
    $conn,
    "SELECT p.idProduto, p.nomeProduto, p.precoAtual, c.nome AS artista
     FROM produto p
     JOIN cliente c ON c.idCliente = p.idCliente
     WHERE p.estado = 'pendente'
     ORDER BY p.created_at DESC
     LIMIT 5"
);

$pendingReleases = db_all(
    $conn,
    "SELECT r.idRelease, r.titulo, r.tipo, c.nome AS artista
     FROM release_musical r
     JOIN cliente c ON c.idCliente = r.idCliente
     WHERE r.estado = 'pendente'
     ORDER BY r.created_at DESC
     LIMIT 5"
);

$openMessages = db_all(
    $conn,
    "SELECT m.idMensagem, m.assunto, c.nome AS cliente_nome, m.created_at
     FROM mensagem_admin m
     JOIN cliente c ON c.idCliente = m.idCliente
     WHERE m.estado = 'aberta'
     ORDER BY m.created_at DESC
     LIMIT 5"
);

$recentOrders = db_all(
    $conn,
    "SELECT e.idEncomenda, e.total_final, e.estado_encomenda, e.estado_pagamento, c.nome AS cliente_nome
     FROM encomenda e
     JOIN cliente c ON c.idCliente = e.idCliente
     ORDER BY e.created_at DESC
     LIMIT 4"
);

include 'admin_header.php';
?>

<section class="admin-dashboard-v4">
  <header class="dash-v4-top">
    <div>
      <span class="admin-kicker" data-admin-t="dash_kicker">Greenerry Control</span>
      <h2 data-admin-t="dash_title">Dashboard</h2>
      <p data-admin-t="dash_intro">Revenue, approvals, support, and orders in one clean command view.</p>
    </div>
    <div class="dash-v4-actions">
      <a href="reports.php" class="btn btn-ghost btn-sm" data-admin-t="nav_reports">Reports</a>
      <a href="reports.php?export=excel" class="btn btn-dark btn-sm" data-admin-t="reports_export_excel">Exportar Excel</a>
    </div>
  </header>

  <div class="dash-v4-kpis">
    <article><span data-admin-t="stat_paid_revenue">Receita paga</span><strong><?= h(format_eur((float)($finance['total_revenue'] ?? 0))) ?></strong><small><?= (int)($paidOrderStats['total_paid_orders'] ?? 0) ?> <span data-admin-t="dash_paid_orders_note">paid orders</span></small></article>
    <article><span data-admin-t="stat_attention">Por rever</span><strong><?= $attentionTotal ?></strong><small data-admin-t="dash_review_queue_note">Products, releases, messages, resets</small></article>
    <article><span data-admin-t="stat_platform_commission">Comissao da plataforma</span><strong><?= h(format_eur((float)($finance['total_commission'] ?? 0))) ?></strong><small data-admin-t="dash_platform_margin_note">Platform margin</small></article>
    <article><span data-admin-t="stat_average_order">Ticket medio</span><strong><?= h(format_eur((float)($paidOrderStats['average_order_value'] ?? 0))) ?></strong><small data-admin-t="dash_average_order_note">Average paid order</small></article>
  </div>

  <div class="dash-v4-grid">
    <section class="dash-v4-card dash-v4-main-chart">
      <div class="dash-v4-card-head">
        <div>
          <span class="admin-kicker" data-admin-t="dash_revenue_trend">Revenue trend</span>
          <h3><?= h(format_eur((float)($finance['total_revenue'] ?? 0))) ?></h3>
        </div>
        <span class="admin-card-note" data-admin-t="box_last_six_months">Ultimos 6 meses</span>
      </div>
      <?php if (!$monthlyPerformance): ?>
        <p data-admin-t="empty_monthly">Sem atividade suficiente para desenhar a evolucao mensal.</p>
      <?php else: ?>
        <svg class="dash-v4-line-chart" viewBox="0 0 <?= $chartWidth ?> <?= $chartHeight ?>" role="img" aria-label="Revenue trend chart">
          <defs>
            <linearGradient id="dashRevenueGlow" x1="0" x2="0" y1="0" y2="1">
              <stop offset="0%" stop-color="#c9d0db" stop-opacity=".42"/>
              <stop offset="100%" stop-color="#c9d0db" stop-opacity="0"/>
            </linearGradient>
          </defs>
          <g class="chart-grid-lines">
            <line x1="28" y1="48" x2="592" y2="48"/>
            <line x1="28" y1="92" x2="592" y2="92"/>
            <line x1="28" y1="136" x2="592" y2="136"/>
            <line x1="28" y1="180" x2="592" y2="180"/>
          </g>
          <?php if ($chartAreaPoints): ?>
            <polygon points="<?= h(implode(' ', $chartAreaPoints)) ?>" fill="url(#dashRevenueGlow)"/>
            <polyline points="<?= h(implode(' ', $chartPoints)) ?>" fill="none" stroke="#c9d0db" stroke-width="5" stroke-linecap="round" stroke-linejoin="round"/>
            <?php foreach ($chartPoints as $point): ?>
              <?php [$cx, $cy] = explode(',', $point); ?>
              <circle cx="<?= h($cx) ?>" cy="<?= h($cy) ?>" r="5"/>
            <?php endforeach; ?>
          <?php endif; ?>
        </svg>
        <div class="dash-v4-months">
          <?php foreach ($monthlyPerformance as $entry): ?>
            <span><?= h($entry['period_label']) ?></span>
          <?php endforeach; ?>
        </div>
      <?php endif; ?>
    </section>

    <section class="dash-v4-card dash-v4-donut-card">
      <div class="dash-v4-card-head">
        <div>
          <span class="admin-kicker" data-admin-t="nav_orders">Orders</span>
          <h3><?= (int)$totalOrderStates ?></h3>
        </div>
      </div>
      <div class="dash-v4-donut" style="<?= h($orderDonutStyle) ?>">
        <div><strong><?= (int)$totalOrderStates ?></strong><span data-admin-t="orders_total">total</span></div>
      </div>
      <div class="dash-v4-legend">
        <?php foreach ($orderStateCards as $index => $stateCard): ?>
          <?php $percent = $totalOrderStates > 0 ? (int)round(((int)$stateCard['value'] / $totalOrderStates) * 100) : 0; ?>
          <div><i style="background: <?= h($orderColors[$index]) ?>"></i><span><?= h($stateCard['label']) ?></span><strong><?= $percent ?>%</strong></div>
        <?php endforeach; ?>
      </div>
    </section>

    <section class="dash-v4-card dash-v4-bars-card">
      <div class="dash-v4-card-head">
        <div>
          <span class="admin-kicker" data-admin-t="dash_monthly_performance">Monthly performance</span>
          <h3 data-admin-t="stat_paid_revenue">Paid revenue</h3>
        </div>
      </div>
      <div class="dash-v4-bars">
        <?php foreach ($monthlyPerformance as $entry): ?>
          <?php $height = $maxMonthlyRevenue > 0 ? max(14, (int)round(((float)$entry['total_revenue'] / $maxMonthlyRevenue) * 100)) : 14; ?>
          <div>
            <span><?= h(format_eur((float)$entry['total_revenue'])) ?></span>
            <i style="height: <?= $height ?>%"></i>
            <strong><?= h($entry['period_label']) ?></strong>
          </div>
        <?php endforeach; ?>
      </div>
    </section>

    <section class="dash-v4-card dash-v4-table-card">
      <div class="dash-v4-card-head">
        <div>
          <span class="admin-kicker" data-admin-t="dash_latest_orders">Latest orders</span>
          <h3 data-admin-t="dash_recent_orders">Encomendas recentes</h3>
        </div>
        <a href="orders.php" class="btn btn-ghost btn-sm" data-admin-t="btn_view_all">Ver tudo</a>
      </div>
      <div class="dash-v4-rows">
        <?php foreach ($recentOrders as $order): ?>
          <a href="orders.php">
            <span>#<?= (int)$order['idEncomenda'] ?></span>
            <strong><?= h($order['cliente_nome']) ?></strong>
            <em><?= h(payment_status_label((string)$order['estado_pagamento'])) ?></em>
            <b><?= h(format_eur((float)$order['total_final'])) ?></b>
          </a>
        <?php endforeach; ?>
      </div>
    </section>
  </div>

  <section class="dash-v4-queue">
    <a href="products.php"><span data-admin-t="nav_products">Produtos</span><strong><?= (int)$stats['produtos_pendentes'] ?></strong><small data-admin-t="dash_need_review">precisam de revisao</small></a>
    <a href="releases.php"><span data-admin-t="nav_releases">Lancamentos</span><strong><?= (int)$stats['releases_pendentes'] ?></strong><small data-admin-t="dash_need_review">precisam de revisao</small></a>
    <a href="messages.php"><span data-admin-t="nav_messages">Mensagens</span><strong><?= (int)$stats['mensagens_abertas'] ?></strong><small data-admin-t="dash_need_reply">por responder</small></a>
    <a href="password_requests.php"><span data-admin-t="nav_password">Password reset</span><strong><?= (int)$stats['resets_pendentes'] ?></strong><small data-admin-t="dash_open_requests">open requests</small></a>
  </section>
</section>

<?php include 'admin_footer.php'; ?>
