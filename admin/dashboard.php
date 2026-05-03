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

<section class="admin-dashboard-hero">
  <div>
    <span class="admin-kicker" data-admin-t="dash_kicker">Greenerry Control</span>
    <h2 data-admin-t="dash_title">Painel</h2>
    <p data-admin-t="dash_hero_title">Tudo o que importa, num so painel.</p>
  </div>
  <div class="admin-export-panel">
    <span data-admin-t="reports_export_label">Relatorio executivo</span>
    <strong data-admin-t="reports_export_title">Excel profissional</strong>
    <p data-admin-t="reports_export_desc">Resumo, receita mensal, artistas, categorias e encomendas recentes.</p>
    <a href="reports.php?export=excel" class="btn btn-dark btn-sm" data-admin-t="reports_export_excel">Exportar Excel</a>
  </div>
</section>

<section class="admin-summary-grid">
  <div class="admin-summary-card admin-finance-stat">
    <span data-admin-t="stat_paid_revenue">Receita paga</span>
    <strong><?= h(format_eur((float)($finance['total_revenue'] ?? 0))) ?></strong>
  </div>
  <div class="admin-summary-card admin-finance-stat">
    <span data-admin-t="stat_attention">Por rever</span>
    <strong><?= $attentionTotal ?></strong>
  </div>
  <div class="admin-summary-card admin-finance-stat">
    <span data-admin-t="stat_paid_orders">Encomendas pagas</span>
    <strong><?= (int)($paidOrderStats['total_paid_orders'] ?? 0) ?></strong>
  </div>
  <div class="admin-summary-card admin-finance-stat">
    <span data-admin-t="stat_platform_commission">Comissao da plataforma</span>
    <strong><?= h(format_eur((float)($finance['total_commission'] ?? 0))) ?></strong>
  </div>
  <div class="admin-summary-card admin-finance-stat">
    <span data-admin-t="stat_artist_base">Base para artistas</span>
    <strong><?= h(format_eur((float)($finance['total_artist_base'] ?? 0))) ?></strong>
  </div>
  <div class="admin-summary-card admin-finance-stat">
    <span data-admin-t="stat_average_order">Ticket medio</span>
    <strong><?= h(format_eur((float)($paidOrderStats['average_order_value'] ?? 0))) ?></strong>
  </div>
</section>

<section class="admin-command-grid">
  <a href="products.php" class="admin-command-card">
    <span data-admin-t="nav_products">Produtos</span>
    <strong><?= (int)$stats['produtos_pendentes'] ?></strong>
    <small data-admin-t="dash_need_review">precisam de revisao</small>
  </a>
  <a href="releases.php" class="admin-command-card">
    <span data-admin-t="nav_releases">Lancamentos</span>
    <strong><?= (int)$stats['releases_pendentes'] ?></strong>
    <small data-admin-t="dash_need_review">precisam de revisao</small>
  </a>
  <a href="messages.php" class="admin-command-card">
    <span data-admin-t="nav_messages">Mensagens</span>
    <strong><?= (int)$stats['mensagens_abertas'] ?></strong>
    <small data-admin-t="dash_need_reply">por responder</small>
  </a>
  <a href="orders.php" class="admin-command-card">
    <span data-admin-t="nav_orders">Encomendas</span>
    <strong><?= (int)$stats['encomendas'] ?></strong>
    <small data-admin-t="dash_total_registered">registadas</small>
  </a>
</section>

<div class="admin-insight-grid">
  <section class="acard-box admin-analytics-card">
    <div class="acard-box-head">
      <h4 data-admin-t="box_order_states">Estado das encomendas</h4>
    </div>
    <div class="admin-status-grid">
      <?php foreach ($orderStateCards as $stateCard): ?>
        <div class="admin-status-chip">
          <strong><?= (int)$stateCard['value'] ?></strong>
          <span><?= h($stateCard['label']) ?></span>
        </div>
      <?php endforeach; ?>
    </div>
  </section>

  <section class="acard-box admin-analytics-card">
    <div class="acard-box-head">
      <h4 data-admin-t="box_recent_performance">Performance recente</h4>
      <span class="admin-card-note" data-admin-t="box_last_six_months">Ultimos 6 meses</span>
    </div>
    <?php if (!$monthlyPerformance): ?>
      <p data-admin-t="empty_monthly">Sem atividade suficiente para desenhar a evolucao mensal.</p>
    <?php else: ?>
      <div class="admin-chart">
        <?php foreach ($monthlyPerformance as $entry): ?>
          <?php
          $revenue = (float)$entry['total_revenue'];
          $height = $maxMonthlyRevenue > 0 ? max(18, (int)round(($revenue / $maxMonthlyRevenue) * 140)) : 18;
          ?>
          <div class="admin-chart-col">
            <span class="admin-chart-value"><?= h(format_eur($revenue)) ?></span>
            <div class="admin-chart-bar-wrap">
              <div class="admin-chart-bar" style="height: <?= $height ?>px"></div>
            </div>
            <strong><?= h($entry['period_label']) ?></strong>
            <span><?= (int)$entry['total_orders'] ?> encomendas</span>
          </div>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>
  </section>

  <section class="acard-box admin-analytics-card">
    <div class="acard-box-head">
      <h4 data-admin-t="dash_recent_orders">Encomendas recentes</h4>
      <a href="orders.php" class="btn btn-ghost btn-sm" data-admin-t="btn_view_all">Ver tudo</a>
    </div>
    <?php if (!$recentOrders): ?>
      <p data-admin-t="orders_empty">Sem encomendas registadas.</p>
    <?php else: ?>
      <div class="simple-list">
        <?php foreach ($recentOrders as $order): ?>
          <div class="simple-list-item">
            <div>
              <strong>#<?= (int)$order['idEncomenda'] ?> - <?= h($order['cliente_nome']) ?></strong>
              <p><?= h(order_status_label((string)$order['estado_encomenda'])) ?> / <?= h(payment_status_label((string)$order['estado_pagamento'])) ?></p>
            </div>
            <span><?= h(format_eur((float)$order['total_final'])) ?></span>
          </div>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>
  </section>
</div>

<div class="dashboard-grid">
  <section class="acard-box">
    <div class="acard-box-head">
      <h4 data-admin-t="box_products_review">Produtos por aprovar</h4>
      <a href="products.php" class="btn btn-ghost btn-sm" data-admin-t="btn_view_all">Ver tudo</a>
    </div>
    <?php if (!$pendingProducts): ?>
      <p data-admin-t="empty_pending_products">Sem produtos pendentes.</p>
    <?php else: ?>
      <div class="simple-list">
        <?php foreach ($pendingProducts as $item): ?>
          <div class="simple-list-item">
            <div>
              <strong><?= h($item['nomeProduto']) ?></strong>
              <p><?= h($item['artista']) ?></p>
            </div>
            <span><?= number_format((float)$item['precoAtual'], 2, ',', '.') ?> EUR</span>
          </div>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>
  </section>

  <section class="acard-box">
    <div class="acard-box-head">
      <h4 data-admin-t="box_releases_review">Lancamentos por aprovar</h4>
      <a href="releases.php" class="btn btn-ghost btn-sm" data-admin-t="btn_view_all">Ver tudo</a>
    </div>
    <?php if (!$pendingReleases): ?>
      <p data-admin-t="empty_pending_releases">Sem lancamentos pendentes.</p>
    <?php else: ?>
      <div class="simple-list">
        <?php foreach ($pendingReleases as $item): ?>
          <div class="simple-list-item">
            <div>
              <strong><?= h($item['titulo']) ?></strong>
              <p><?= h($item['artista']) ?> - <?= h($item['tipo']) ?></p>
            </div>
            <span>#<?= (int)$item['idRelease'] ?></span>
          </div>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>
  </section>

  <section class="acard-box">
    <div class="acard-box-head">
      <h4 data-admin-t="box_open_messages">Mensagens em aberto</h4>
      <a href="messages.php" class="btn btn-ghost btn-sm" data-admin-t="btn_reply">Responder</a>
    </div>
    <?php if (!$openMessages): ?>
      <p data-admin-t="empty_open_messages">Sem mensagens em aberto.</p>
    <?php else: ?>
      <div class="simple-list">
        <?php foreach ($openMessages as $item): ?>
          <div class="simple-list-item">
            <div>
              <strong><?= h($item['assunto']) ?></strong>
              <p><?= h($item['cliente_nome']) ?></p>
            </div>
            <span><?= date('d/m/Y', strtotime($item['created_at'])) ?></span>
          </div>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>
  </section>

  <section class="acard-box">
    <div class="acard-box-head">
      <h4 data-admin-t="box_catalog_health">Catalogo</h4>
      <a href="categories.php" class="btn btn-ghost btn-sm" data-admin-t="nav_categories">Categorias</a>
    </div>
    <div class="simple-list">
      <div class="simple-list-item">
        <strong data-admin-t="card_active_clients">Clientes ativos</strong>
        <span><?= (int)$stats['clientes'] ?></span>
      </div>
      <div class="simple-list-item">
        <strong data-admin-t="card_orders">Encomendas</strong>
        <span><?= (int)$stats['encomendas'] ?></span>
      </div>
      <div class="simple-list-item">
        <strong data-admin-t="card_active_categories">Categorias ativas</strong>
        <span><?= (int)$stats['categorias'] ?></span>
      </div>
    </div>
  </section>
</div>

<?php include 'admin_footer.php'; ?>
