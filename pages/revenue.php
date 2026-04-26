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

include '../includes/header.php';
?>

<section class="content-shell">
  <div class="wrap">
    <div class="page-intro">
      <span class="slabel">Rendimento</span>
      <h2>Resumo das vendas</h2>
      <p>Os ganhos sao calculados a partir do valor artistico registado em cada item da encomenda.</p>
    </div>

    <div class="stats-grid">
      <div class="stat">
        <div class="stat-val"><?= h(format_eur((float)($summary['total_artist_value'] ?? 0))) ?></div>
        <div class="stat-lbl">Valor recebido</div>
      </div>
      <div class="stat">
        <div class="stat-val"><?= (int)($summary['total_orders'] ?? 0) ?></div>
        <div class="stat-lbl">Encomendas entregues</div>
      </div>
      <div class="stat">
        <div class="stat-val"><?= (int)($summary['total_items'] ?? 0) ?></div>
        <div class="stat-lbl">Itens vendidos</div>
      </div>
      <div class="stat">
        <div class="stat-val"><?= h(format_eur((float)($summary['total_commission'] ?? 0))) ?></div>
        <div class="stat-lbl">Comissao da plataforma</div>
      </div>
    </div>

    <div class="card surface-card mt8">
      <div class="card-body">
        <h3 class="section-card-title">Movimentos recentes</h3>

        <?php if (!$sales): ?>
          <p>Ainda nao tens vendas registadas.</p>
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
                    <td><?= h(format_eur((float)$sale['valor_artista'])) ?></td>
                    <td><?= h(format_eur((float)$sale['comissao_valor'])) ?></td>
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
