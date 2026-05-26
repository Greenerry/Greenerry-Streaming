<?php
require_once '../includes/config.php';
require_admin_permission('dashboard');

$range = (string)($_GET['range'] ?? '6m');
$allowedRanges = ['7d', '30d', '6m', '1y', 'all'];
if (!in_array($range, $allowedRanges, true)) {
    $range = '6m';
}
$rangeSqlMap = [
    '7d' => 'DATE_SUB(CURDATE(), INTERVAL 7 DAY)',
    '30d' => 'DATE_SUB(CURDATE(), INTERVAL 30 DAY)',
    '6m' => 'DATE_SUB(CURDATE(), INTERVAL 5 MONTH)',
    '1y' => 'DATE_SUB(CURDATE(), INTERVAL 1 YEAR)',
    'all' => "'1970-01-01'",
];
$rangeLabels = [
    '7d' => ['key' => 'range_7d', 'label' => '7 dias'],
    '30d' => ['key' => 'range_30d', 'label' => '30 dias'],
    '6m' => ['key' => 'range_6m', 'label' => '6 meses'],
    '1y' => ['key' => 'range_1y', 'label' => '1 ano'],
    'all' => ['key' => 'range_all', 'label' => 'Tudo'],
];
$dateFromSql = $rangeSqlMap[$range];
$periodKeySql = in_array($range, ['7d', '30d'], true)
    ? "DATE_FORMAT(e.criado_em, '%Y-%m-%d')"
    : "DATE_FORMAT(e.criado_em, '%Y-%m')";
$periodLabelSql = in_array($range, ['7d', '30d'], true)
    ? "DATE_FORMAT(e.criado_em, '%d/%m')"
    : "DATE_FORMAT(e.criado_em, '%m/%Y')";

$stats = [
    'clientes' => db_one($conn, "SELECT COUNT(*) AS total FROM cliente WHERE estado = 'ativo'")['total'] ?? 0,
    'categorias' => db_one($conn, "SELECT COUNT(*) AS total FROM categoria WHERE estado = 'ativo'")['total'] ?? 0,
    'produtos_pendentes' => db_one($conn, "SELECT COUNT(*) AS total FROM produto WHERE estado = 'pendente'")['total'] ?? 0,
    'releases_pendentes' => db_one($conn, "SELECT COUNT(*) AS total FROM release_musical WHERE estado = 'pendente'")['total'] ?? 0,
    'mensagens_abertas' => db_one($conn, "SELECT COUNT(*) AS total FROM mensagem_admin WHERE estado = 'aberta'")['total'] ?? 0,
    'encomendas' => db_one($conn, "SELECT COUNT(*) AS total FROM encomenda")['total'] ?? 0
];
$attentionTotal = (int)$stats['produtos_pendentes'] + (int)$stats['releases_pendentes'] + (int)$stats['mensagens_abertas'];

$finance = db_one(
    $conn,
    "SELECT
        COALESCE(SUM(CASE WHEN e.estado_pagamento = 'pago' AND e.estado_encomenda != 'cancelada' AND ei.estado_item != 'cancelado' THEN ei.total_linha END), 0) AS total_revenue,
        COALESCE(SUM(CASE WHEN e.estado_pagamento = 'pago' AND e.estado_encomenda != 'cancelada' AND ei.estado_item != 'cancelado' THEN ei.comissao_valor END), 0) AS total_commission,
        COALESCE(SUM(CASE WHEN e.estado_pagamento = 'pago' AND e.estado_encomenda != 'cancelada' AND ei.estado_item != 'cancelado' THEN ei.valor_artista END), 0) AS total_artist_base
     FROM encomenda e
     JOIN encomenda_item ei ON ei.idEncomenda = e.idEncomenda
     WHERE e.criado_em >= {$dateFromSql}"
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
       AND ei.estado_item != 'cancelado'
       AND e.criado_em >= {$dateFromSql}"
);

$monthlyPerformance = db_all(
    $conn,
    "SELECT
        {$periodKeySql} AS period_key,
        {$periodLabelSql} AS period_label,
        COUNT(DISTINCT e.idEncomenda) AS total_orders,
        COALESCE(SUM(ei.comissao_valor), 0) AS total_commission,
        COALESCE(SUM(ei.total_linha), 0) AS total_revenue
     FROM encomenda e
     JOIN encomenda_item ei ON ei.idEncomenda = e.idEncomenda
     WHERE e.criado_em >= {$dateFromSql}
       AND e.estado_pagamento = 'pago'
       AND e.estado_encomenda != 'cancelada'
       AND ei.estado_item != 'cancelado'
     GROUP BY {$periodKeySql}, {$periodLabelSql}
     ORDER BY period_key ASC"
);

$maxMonthlyRevenue = 0.0;
foreach ($monthlyPerformance as $entry) {
    $maxMonthlyRevenue = max($maxMonthlyRevenue, (float)$entry['total_revenue']);
}

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
     ORDER BY p.criado_em DESC
     LIMIT 5"
);

$pendingReleases = db_all(
    $conn,
    "SELECT r.idRelease, r.titulo, r.tipo, c.nome AS artista
     FROM release_musical r
     JOIN cliente c ON c.idCliente = r.idCliente
     WHERE r.estado = 'pendente'
     ORDER BY r.criado_em DESC
     LIMIT 5"
);

$openMessages = db_all(
    $conn,
    "SELECT m.idMensagem, m.assunto, c.nome AS cliente_nome, m.criado_em
     FROM mensagem_admin m
     JOIN cliente c ON c.idCliente = m.idCliente
     WHERE m.estado = 'aberta'
     ORDER BY m.criado_em DESC
     LIMIT 5"
);

$chartTipLabels = current_lang() === 'en'
    ? ['revenue' => 'Revenue', 'commission' => 'Commission', 'orders' => 'Orders', 'products' => 'Products', 'releases' => 'Releases', 'messages' => 'Messages']
    : ['revenue' => 'Receita', 'commission' => 'Comissão', 'orders' => 'Encomendas', 'products' => 'Produtos', 'releases' => 'Lançamentos', 'messages' => 'Mensagens'];

include 'admin_header.php';
?>

<section class="admin-dashboard-v4">
  <header class="dash-v4-top">
    <div>
      <span class="admin-kicker" data-admin-t="dash_kicker">Greenerry Control</span>
      <h2 data-admin-t="dash_title">Dashboard</h2>
      <p data-admin-t="dash_intro">Revenue, approvals, support, and catalog health in one clean command view.</p>
    </div>
    <div class="dash-v4-actions">
      <nav class="admin-range-pills" aria-label="Dashboard range">
        <?php foreach ($rangeLabels as $rangeKey => $rangeItem): ?>
          <a href="dashboard.php?range=<?= h($rangeKey) ?>" class="<?= $range === $rangeKey ? 'on' : '' ?>" data-admin-t="<?= h($rangeItem['key']) ?>"><?= h($rangeItem['label']) ?></a>
        <?php endforeach; ?>
      </nav>
      <a href="reports.php" class="btn btn-ghost btn-sm" data-admin-t="nav_reports">Reports</a>
      <a href="reports.php?export=excel" class="btn btn-dark btn-sm" data-admin-t="reports_export_excel">Exportar Excel</a>
    </div>
  </header>

  <div class="dash-v4-kpis">
    <a href="reports.php?range=<?= h($range) ?>" class="dash-v4-kpi-link">
      <span data-admin-t="stat_paid_revenue">Receita paga</span>
      <strong><?= h(format_eur((float)($finance['total_revenue'] ?? 0))) ?></strong>
      <small><?= (int)($paidOrderStats['total_paid_orders'] ?? 0) ?> <span data-admin-t="dash_paid_orders_note">paid orders</span></small>
    </a>
    <a href="#review-queue" class="dash-v4-kpi-link">
      <span data-admin-t="stat_attention">Por rever</span>
      <strong><?= $attentionTotal ?></strong>
      <small data-admin-t="dash_review_queue_note">Products, releases, messages</small>
    </a>
    <a href="reports.php?range=<?= h($range) ?>" class="dash-v4-kpi-link">
      <span data-admin-t="stat_platform_commission">Comissão da plataforma</span>
      <strong><?= h(format_eur((float)($finance['total_commission'] ?? 0))) ?></strong>
      <small data-admin-t="dash_platform_margin_note">Platform margin</small>
    </a>
    <a href="reports.php?range=<?= h($range) ?>" class="dash-v4-kpi-link">
      <span data-admin-t="stat_average_order">Ticket medio</span>
      <strong><?= h(format_eur((float)($paidOrderStats['average_order_value'] ?? 0))) ?></strong>
      <small data-admin-t="dash_average_order_note">Average paid order</small>
    </a>
  </div>

  <div class="dash-v4-grid">
    <section class="dash-v4-card dash-v4-main-chart">
      <div class="dash-v4-card-head">
        <div>
          <span class="admin-kicker" data-admin-t="dash_revenue_trend">Revenue trend</span>
          <h3><?= h(format_eur((float)($finance['total_revenue'] ?? 0))) ?></h3>
        </div>
        <span class="admin-card-note" data-admin-t="<?= h($rangeLabels[$range]['key']) ?>"><?= h($rangeLabels[$range]['label']) ?></span>
      </div>
      <?php if (count($monthlyPerformance) < 2): ?>
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
            <?php foreach ($chartPoints as $index => $point): ?>
              <?php [$cx, $cy] = explode(',', $point); ?>
              <?php $entry = $monthlyPerformance[$index] ?? null; ?>
              <circle cx="<?= h($cx) ?>" cy="<?= h($cy) ?>" r="6" tabindex="0">
                <?php if ($entry): ?>
                  <title><?= h($entry['period_label'] . ': ' . $chartTipLabels['revenue'] . ' ' . format_eur((float)$entry['total_revenue']) . ' / ' . $chartTipLabels['commission'] . ' ' . format_eur((float)$entry['total_commission'])) ?></title>
                <?php endif; ?>
              </circle>
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
          <span class="admin-kicker" data-admin-t="box_catalog_health">Catalog</span>
          <h3><?= (int)$stats['produtos_pendentes'] + (int)$stats['releases_pendentes'] ?></h3>
        </div>
      </div>
      <?php $catalogDonutStyle = '--dash-filled:' . ($attentionTotal > 0 ? '100%' : '0%') . ';'; ?>
      <div class="dash-v4-donut admin-chart-tip" style="<?= h($catalogDonutStyle) ?>" data-chart-tip="<?= h($chartTipLabels['products'] . ': ' . (int)$stats['produtos_pendentes'] . ' | ' . $chartTipLabels['releases'] . ': ' . (int)$stats['releases_pendentes'] . ' | ' . $chartTipLabels['messages'] . ': ' . (int)$stats['mensagens_abertas']) ?>">
        <div><strong><?= (int)$attentionTotal ?></strong><span data-admin-t="stat_attention">Por rever</span></div>
      </div>
      <div class="dash-v4-legend">
        <div><i></i><span data-admin-t="nav_products">Produtos</span><strong><?= (int)$stats['produtos_pendentes'] ?></strong></div>
        <div><i></i><span data-admin-t="nav_releases">Lançamentos</span><strong><?= (int)$stats['releases_pendentes'] ?></strong></div>
        <div><i></i><span data-admin-t="nav_messages">Mensagens</span><strong><?= (int)$stats['mensagens_abertas'] ?></strong></div>
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
          <div class="admin-chart-tip" data-chart-tip="<?= h($entry['period_label'] . ' | ' . $chartTipLabels['revenue'] . ': ' . format_eur((float)$entry['total_revenue']) . ' | ' . $chartTipLabels['commission'] . ': ' . format_eur((float)$entry['total_commission']) . ' | ' . $chartTipLabels['orders'] . ': ' . (int)$entry['total_orders']) ?>">
            <span><?= h(format_eur((float)$entry['total_revenue'])) ?></span>
            <i style="height: <?= $height ?>%"></i>
            <strong><?= h($entry['period_label']) ?></strong>
          </div>
        <?php endforeach; ?>
      </div>
    </section>

  </div>

  <section class="dash-v4-queue" id="review-queue">
    <a href="products.php"><span data-admin-t="nav_products">Produtos</span><strong><?= (int)$stats['produtos_pendentes'] ?></strong><small data-admin-t="dash_need_review">precisam de revisão</small></a>
    <a href="releases.php"><span data-admin-t="nav_releases">Lançamentos</span><strong><?= (int)$stats['releases_pendentes'] ?></strong><small data-admin-t="dash_need_review">precisam de revisão</small></a>
    <a href="messages.php"><span data-admin-t="nav_messages">Mensagens</span><strong><?= (int)$stats['mensagens_abertas'] ?></strong><small data-admin-t="dash_need_reply">por responder</small></a>
  </section>

  <section class="dash-review-grid">
    <div class="dash-v4-card">
      <div class="dash-v4-card-head">
        <div>
          <span class="admin-kicker" data-admin-t="box_products_review">Produtos por aprovar</span>
          <h3><?= count($pendingProducts) ?></h3>
        </div>
        <a href="products.php" class="admin-card-note" data-admin-t="btn_view_all">Ver tudo</a>
      </div>
      <div class="dash-review-list">
        <?php if (!$pendingProducts): ?>
          <span><small data-admin-t="empty_pending_products">Sem produtos pendentes.</small></span>
        <?php else: ?>
          <?php foreach ($pendingProducts as $product): ?>
            <a href="products.php">
              <strong><?= h($product['nomeProduto']) ?></strong>
              <small><?= h($product['artista']) ?></small>
            </a>
          <?php endforeach; ?>
        <?php endif; ?>
      </div>
    </div>

    <div class="dash-v4-card">
      <div class="dash-v4-card-head">
        <div>
          <span class="admin-kicker" data-admin-t="box_releases_review">Lançamentos por aprovar</span>
          <h3><?= count($pendingReleases) ?></h3>
        </div>
        <a href="releases.php" class="admin-card-note" data-admin-t="btn_view_all">Ver tudo</a>
      </div>
      <div class="dash-review-list">
        <?php if (!$pendingReleases): ?>
          <span><small data-admin-t="empty_pending_releases">Sem lançamentos pendentes.</small></span>
        <?php else: ?>
          <?php foreach ($pendingReleases as $release): ?>
            <a href="releases.php">
              <strong><?= h($release['titulo']) ?></strong>
              <small><?= h($release['artista']) ?></small>
            </a>
          <?php endforeach; ?>
        <?php endif; ?>
      </div>
    </div>

    <div class="dash-v4-card">
      <div class="dash-v4-card-head">
        <div>
          <span class="admin-kicker" data-admin-t="box_open_messages">Mensagens em aberto</span>
          <h3><?= count($openMessages) ?></h3>
        </div>
        <a href="messages.php" class="admin-card-note" data-admin-t="btn_view_all">Ver tudo</a>
      </div>
      <div class="dash-review-list">
        <?php if (!$openMessages): ?>
          <span><small data-admin-t="empty_open_messages">Sem mensagens em aberto.</small></span>
        <?php else: ?>
          <?php foreach ($openMessages as $message): ?>
            <a href="messages.php">
              <strong><?= h($message['assunto']) ?></strong>
              <small><?= h($message['cliente_nome']) ?></small>
            </a>
          <?php endforeach; ?>
        <?php endif; ?>
      </div>
    </div>
  </section>

</section>

<?php include 'admin_footer.php'; ?>
