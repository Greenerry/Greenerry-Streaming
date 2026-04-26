<?php
require_once '../includes/config.php';
require_admin_login();

$stats = [
    'clientes' => db_one($conn, "SELECT COUNT(*) AS total FROM cliente WHERE estado = 'ativo'")['total'] ?? 0,
    'produtos_pendentes' => db_one($conn, "SELECT COUNT(*) AS total FROM produto WHERE estado = 'pendente'")['total'] ?? 0,
    'releases_pendentes' => db_one($conn, "SELECT COUNT(*) AS total FROM release_musical WHERE estado = 'pendente'")['total'] ?? 0,
    'mensagens_abertas' => db_one($conn, "SELECT COUNT(*) AS total FROM mensagem_admin WHERE estado = 'aberta'")['total'] ?? 0,
    'resets_pendentes' => db_one($conn, "SELECT COUNT(*) AS total FROM pedido_reset_password WHERE estado IN ('pendente', 'em_analise')")['total'] ?? 0,
    'encomendas' => db_one($conn, "SELECT COUNT(*) AS total FROM encomenda")['total'] ?? 0
];

$finance = db_one(
    $conn,
    "SELECT
        COALESCE(SUM(CASE WHEN estado_pagamento = 'pago' THEN total_final END), 0) AS total_revenue,
        COALESCE(SUM(CASE WHEN estado_pagamento = 'pago' THEN comissao_total END), 0) AS total_commission,
        COALESCE(SUM(CASE WHEN estado_pagamento = 'pago' THEN subtotal END), 0) AS total_artist_base
     FROM encomenda"
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

$topArtists = db_all(
    $conn,
    "SELECT
        c.nome,
        COUNT(*) AS total_linhas,
        COALESCE(SUM(ei.quantidade), 0) AS total_itens,
        COALESCE(SUM(ei.valor_artista), 0) AS total_artist_value
     FROM encomenda_item ei
     JOIN cliente c ON c.idCliente = ei.idArtista
     GROUP BY ei.idArtista, c.nome
     ORDER BY total_artist_value DESC, total_itens DESC
     LIMIT 4"
);

$monthlyPerformance = db_all(
    $conn,
    "SELECT
        DATE_FORMAT(created_at, '%Y-%m') AS period_key,
        DATE_FORMAT(created_at, '%m/%Y') AS period_label,
        COUNT(*) AS total_orders,
        COALESCE(SUM(comissao_total), 0) AS total_commission,
        COALESCE(SUM(total_final), 0) AS total_revenue
     FROM encomenda
     WHERE created_at >= DATE_SUB(CURDATE(), INTERVAL 5 MONTH)
     GROUP BY DATE_FORMAT(created_at, '%Y-%m'), DATE_FORMAT(created_at, '%m/%Y')
     ORDER BY period_key ASC"
);

$maxMonthlyRevenue = 0.0;
foreach ($monthlyPerformance as $entry) {
    $maxMonthlyRevenue = max($maxMonthlyRevenue, (float)$entry['total_revenue']);
}

$orderStateCards = [
    ['label' => 'Pendentes', 'value' => (int)($orderStates['pendente'] ?? 0)],
    ['label' => 'Em preparacao', 'value' => (int)($orderStates['em_preparacao'] ?? 0)],
    ['label' => 'Enviadas', 'value' => (int)($orderStates['enviada'] ?? 0)],
    ['label' => 'Entregues', 'value' => (int)($orderStates['entregue'] ?? 0)],
    ['label' => 'Canceladas', 'value' => (int)($orderStates['cancelada'] ?? 0)]
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

include 'admin_header.php';
?>

<div class="admin-top">
  <div>
    <h2 data-admin-t="dash_title">Painel</h2>
  </div>
</div>

<section class="admin-summary-grid">
  <div class="admin-summary-card admin-summary-card--lead">
    <span class="admin-kicker" data-admin-t="dash_kicker">Greenerry Control</span>
    <h3 data-admin-t="dash_hero_title">Tudo o que importa, num so painel.</h3>
  </div>
  <div class="admin-summary-card admin-finance-stat">
    <span data-admin-t="stat_paid_revenue">Receita paga</span>
    <strong><?= h(format_eur((float)($finance['total_revenue'] ?? 0))) ?></strong>
  </div>
  <div class="admin-summary-card admin-finance-stat">
    <span data-admin-t="stat_platform_commission">Comissao da plataforma</span>
    <strong><?= h(format_eur((float)($finance['total_commission'] ?? 0))) ?></strong>
  </div>
  <div class="admin-summary-card admin-finance-stat">
    <span data-admin-t="stat_artist_base">Base para artistas</span>
    <strong><?= h(format_eur((float)($finance['total_artist_base'] ?? 0))) ?></strong>
  </div>
</section>

<div class="stats-grid">
  <div class="stat"><div class="stat-val"><?= (int)$stats['clientes'] ?></div><div class="stat-lbl" data-admin-t="card_active_clients">Clientes ativos</div></div>
  <div class="stat"><div class="stat-val"><?= (int)$stats['produtos_pendentes'] ?></div><div class="stat-lbl" data-admin-t="card_pending_products">Produtos pendentes</div></div>
  <div class="stat"><div class="stat-val"><?= (int)$stats['releases_pendentes'] ?></div><div class="stat-lbl" data-admin-t="card_pending_releases">Lancamentos pendentes</div></div>
  <div class="stat"><div class="stat-val"><?= (int)$stats['mensagens_abertas'] ?></div><div class="stat-lbl" data-admin-t="card_open_messages">Mensagens abertas</div></div>
  <div class="stat"><div class="stat-val"><?= (int)$stats['resets_pendentes'] ?></div><div class="stat-lbl" data-admin-t="card_reset_requests">Pedidos de reset</div></div>
  <div class="stat"><div class="stat-val"><?= (int)$stats['encomendas'] ?></div><div class="stat-lbl" data-admin-t="card_orders">Encomendas</div></div>
</div>

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
      <h4 data-admin-t="box_top_artists">Artistas com maior retorno</h4>
      <span class="admin-card-note" data-admin-t="box_by_value">Por valor acumulado</span>
    </div>
    <?php if (!$topArtists): ?>
      <p data-admin-t="empty_top_artists">Ainda nao existem vendas suficientes para calcular o ranking.</p>
    <?php else: ?>
      <div class="simple-list">
        <?php foreach ($topArtists as $artist): ?>
          <div class="simple-list-item simple-list-item--stacked">
            <div>
              <strong><?= h($artist['nome']) ?></strong>
              <p><?= (int)$artist['total_itens'] ?> itens vendidos</p>
            </div>
            <div class="admin-inline-values">
              <span><?= h(format_eur((float)$artist['total_artist_value'])) ?></span>
              <small><?= (int)$artist['total_linhas'] ?> linhas</small>
            </div>
          </div>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>
  </section>
</div>

<?php include 'admin_footer.php'; ?>
