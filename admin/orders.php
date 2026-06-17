<?php
// Admin page purpose: Lets administrators review and update customer orders.
// Keep this admin file simple: check access, load data, then render the view.
require_once '../includes/config.php';
require_admin_permission('orders');

$orders = db_all(
    $conn,
    "SELECT
        e.*,
        c.nome AS cliente_nome,
        c.email AS cliente_email,
        c.foto AS cliente_foto,
        me.nome_destinatario,
        me.morada,
        me.cidade,
        me.codigo_postal,
        me.pais,
        me.telefone,
        COUNT(ei.idEncomendaItem) AS total_itens,
        COUNT(DISTINCT ei.idArtista) AS total_artistas
     FROM encomenda e
     JOIN cliente c ON c.idCliente = e.idCliente
     LEFT JOIN morada_encomenda me ON me.idEncomenda = e.idEncomenda
     LEFT JOIN encomenda_item ei ON ei.idEncomenda = e.idEncomenda
     GROUP BY e.idEncomenda, c.nome, c.email, c.foto, me.nome_destinatario, me.morada, me.cidade, me.codigo_postal, me.pais, me.telefone
     ORDER BY e.criado_em DESC"
);

$orderCounts = ['all' => count($orders), 'pendente' => 0, 'em_preparacao' => 0, 'enviada' => 0, 'entregue' => 0, 'cancelada' => 0];
foreach ($orders as $orderRow) {
    $state = (string)$orderRow['estado_encomenda'];
    if (isset($orderCounts[$state])) {
        $orderCounts[$state]++;
    }
}

include 'admin_header.php';
?>

<section class="admin-orders-page">
  <div class="admin-top">
    <div>
      <span class="admin-page-kicker" data-admin-t="orders_kicker">Operações</span>
      <h2 data-admin-t="orders_title">Encomendas</h2>
      <p data-admin-t="orders_intro">Acompanha compras, clientes, artistas, moradas, mensagens e estados de entrega.</p>
    </div>
    <div class="stats-grid admin-top-stats admin-top-stats--five">
      <button type="button" class="stat stat-button" data-admin-stat-filter="orders-search" data-filter-value="pendente"><div class="stat-val"><?= (int)$orderCounts['pendente'] ?></div><div class="stat-lbl" data-admin-t="state_pending">Pendentes</div></button>
      <button type="button" class="stat stat-button" data-admin-stat-filter="orders-search" data-filter-value="em_preparacao"><div class="stat-val"><?= (int)$orderCounts['em_preparacao'] ?></div><div class="stat-lbl" data-admin-t="status_preparing">Em preparação</div></button>
      <button type="button" class="stat stat-button" data-admin-stat-filter="orders-search" data-filter-value="enviada"><div class="stat-val"><?= (int)$orderCounts['enviada'] ?></div><div class="stat-lbl" data-admin-t="status_sent">Enviadas</div></button>
      <button type="button" class="stat stat-button" data-admin-stat-filter="orders-search" data-filter-value="entregue"><div class="stat-val"><?= (int)$orderCounts['entregue'] ?></div><div class="stat-lbl" data-admin-t="status_delivered">Entregues</div></button>
      <button type="button" class="stat stat-button" data-admin-stat-filter="orders-search" data-filter-value="cancelada"><div class="stat-val"><?= (int)$orderCounts['cancelada'] ?></div><div class="stat-lbl" data-admin-t="state_cancelled">Canceladas</div></button>
    </div>
  </div>

  <div id="orders-search" data-admin-search-scope>
    <section class="acard-box admin-orders-card">
      <div class="acard-box-head">
        <h4 data-admin-t="orders_all">Todas as encomendas</h4>
        <div class="admin-card-head-tools">
          <label class="sbar admin-section-search">
            <svg width="15" height="15" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><circle cx="11" cy="11" r="7"/><path d="m21 21-4.3-4.3"/></svg>
            <input type="search" data-admin-search="orders-search" placeholder="Pesquisar..." data-admin-tp="admin_search_placeholder">
          </label>
          <span class="badge badge-light"><?= (int)$orderCounts['all'] ?></span>
        </div>
      </div>

      <?php if (!$orders): ?>
        <p data-admin-t="orders_empty">Ainda não existem encomendas.</p>
      <?php else: ?>
        <div class="admin-order-stack">
          <?php foreach ($orders as $order): ?>
            <?php
            $orderId = (int)$order['idEncomenda'];
            $items = db_all(
                $conn,
                "SELECT ei.*, c.nome AS artista_nome, c.email AS artista_email, t.etiqueta
                 FROM encomenda_item ei
                 JOIN cliente c ON c.idCliente = ei.idArtista
                 LEFT JOIN tamanho t ON t.idTamanho = ei.idTamanho
                 WHERE ei.idEncomenda = {$orderId}
                 ORDER BY ei.idEncomendaItem ASC"
            );
            $messages = db_all(
                $conn,
                "SELECT em.*, comprador.nome AS comprador_nome, artista.nome AS artista_nome, ei.nome_produto
                 FROM encomenda_mensagem em
                 JOIN cliente comprador ON comprador.idCliente = em.idComprador
                 JOIN cliente artista ON artista.idCliente = em.idArtista
                 LEFT JOIN encomenda_item ei ON ei.idEncomenda = em.idEncomenda AND ei.idProduto = em.idProduto AND ei.idArtista = em.idArtista
                 WHERE em.idEncomenda = {$orderId}
                 ORDER BY em.criado_em ASC"
            );
            $searchBits = ['#' . $orderId, (string)$order['cliente_nome'], (string)$order['cliente_email'], (string)$order['estado_encomenda'], (string)$order['estado_pagamento'], (string)$order['morada'], (string)$order['cidade'], (string)$order['codigo_postal'], (string)$order['pais']];
            foreach ($items as $itemForSearch) {
                $searchBits[] = (string)$itemForSearch['nome_produto'];
                $searchBits[] = (string)$itemForSearch['artista_nome'];
                $searchBits[] = (string)$itemForSearch['estado_item'];
            }
            $steps = ['pendente', 'em_preparacao', 'enviada', 'entregue'];
            $currentIndex = array_search((string)$order['estado_encomenda'], $steps, true);
            $currentIndex = $currentIndex === false ? 0 : $currentIndex;
            ?>
            <details class="admin-order-card<?= (string)$order['estado_encomenda'] === 'cancelada' ? ' is-cancelled' : '' ?>" data-admin-state="<?= h(mb_strtolower(implode(' ', $searchBits))) ?>">
              <summary class="admin-order-summary">
                <div class="admin-order-buyer">
                  <span class="admin-user-avatar">
                    <?php if (!empty($order['cliente_foto'])): ?><img src="../assets/img/<?= h($order['cliente_foto']) ?>" alt=""><?php else: ?><?= h(mb_substr((string)$order['cliente_nome'], 0, 1)) ?><?php endif; ?>
                  </span>
                  <span>
                    <strong><span data-admin-t="orders_order_label">Encomenda</span> #<?= $orderId ?></strong>
                    <small><?= h($order['cliente_nome']) ?> · <?= h($order['cliente_email']) ?></small>
                  </span>
                </div>
                <div class="admin-order-summary-meta">
                  <span class="badge <?= h(state_badge_class((string)$order['estado_encomenda'])) ?>"><?= h(order_status_label((string)$order['estado_encomenda'])) ?></span>
                  <span class="badge badge-light"><?= h(payment_status_label((string)$order['estado_pagamento'])) ?> / <?= h(payment_method_label((string)$order['metodo_pagamento'])) ?></span>
                  <strong><?= h(format_eur((float)$order['total_final'])) ?></strong>
                  <small><?= h(date('d/m/Y H:i', strtotime((string)$order['criado_em']))) ?></small>
                </div>
              </summary>

              <div class="admin-order-body">
                <div class="admin-order-progress<?= (string)$order['estado_encomenda'] === 'cancelada' ? ' is-cancelled' : '' ?>">
                  <?php if ((string)$order['estado_encomenda'] === 'cancelada'): ?>
                    <span class="on"><?= h(order_status_label('cancelada')) ?></span>
                  <?php else: ?>
                    <?php foreach ($steps as $stepIndex => $step): ?>
                      <span class="<?= $stepIndex <= $currentIndex ? 'on' : '' ?>"><?= h(order_status_label($step)) ?></span>
                    <?php endforeach; ?>
                  <?php endif; ?>
                </div>

                <div class="admin-order-detail-grid">
                  <div class="admin-order-info-panel">
                    <span class="admin-kicker" data-admin-t="orders_delivery">Entrega</span>
                    <p><strong><?= h($order['nome_destinatario'] ?: $order['cliente_nome']) ?></strong></p>
                    <p><?= h((string)$order['morada']) ?></p>
                    <p><?= h(trim((string)$order['codigo_postal'] . ' ' . (string)$order['cidade'])) ?></p>
                    <p><?= h($order['pais'] ?: 'Portugal') ?></p>
                    <?php if (!empty($order['telefone'])): ?><p><?= h($order['telefone']) ?></p><?php endif; ?>
                  </div>
                  <div class="admin-order-info-panel">
                    <span class="admin-kicker" data-admin-t="orders_summary">Resumo</span>
                    <p><strong><?= (int)$order['total_itens'] ?></strong> <span data-admin-t="orders_items">itens</span></p>
                    <p><strong><?= (int)$order['total_artistas'] ?></strong> <span data-admin-t="orders_artists">artistas</span></p>
                    <p><?= h(format_eur((float)$order['subtotal'])) ?> <span data-admin-t="orders_subtotal">subtotal</span></p>
                    <p><?= h(format_eur((float)$order['iva_total'])) ?> IVA</p>
                    <a class="btn btn-ghost btn-sm" href="../pages/receipt.php?id=<?= $orderId ?>" target="_blank" rel="noopener" data-admin-t="orders_invoice">Abrir fatura</a>
                  </div>
                </div>

                <div class="admin-order-items">
                  <?php foreach ($items as $item): ?>
                    <?php $productImage = product_main_image($conn, (int)$item['idProduto']); ?>
                    <div class="admin-order-item">
                      <div class="admin-table-product">
                        <span class="admin-table-thumb">
                          <?php if ($productImage !== ''): ?><img src="../assets/img/<?= h($productImage) ?>" alt=""><?php else: ?><span data-admin-t="products_no_image">Sem imagem</span><?php endif; ?>
                        </span>
                        <span>
                          <strong><?= h($item['nome_produto']) ?></strong>
                          <small><?= h($item['artista_nome']) ?> · <?= h($item['artista_email']) ?></small>
                          <small><?= (int)$item['quantidade'] ?> unidade(s)<?= !empty($item['etiqueta']) ? ' · ' . h($item['etiqueta']) : '' ?></small>
                        </span>
                      </div>
                      <div class="admin-order-item-side">
                        <span class="badge <?= h(state_badge_class((string)$item['estado_item'])) ?>"><?= h(order_status_label((string)$item['estado_item'])) ?></span>
                        <strong><?= h(format_eur((float)$item['total_linha'])) ?></strong>
                        <small><span data-admin-t="label_commission">Comissão</span> <?= h(format_eur((float)$item['comissao_valor'])) ?> · <span data-admin-t="label_artist">Artista</span> <?= h(format_eur((float)$item['valor_artista'])) ?></small>
                      </div>
                    </div>
                  <?php endforeach; ?>
                </div>

                <?php if ($messages): ?>
                  <div class="admin-order-messages">
                    <span class="admin-kicker" data-admin-t="orders_messages">Mensagens da encomenda</span>
                    <?php foreach ($messages as $message): ?>
                      <div class="admin-order-message">
                        <strong><?= h($message['remetente'] === 'comprador' ? $message['comprador_nome'] : $message['artista_nome']) ?></strong>
                        <small><?= h($message['nome_produto'] ?? '') ?> · <?= h(date('d/m/Y H:i', strtotime((string)$message['criado_em']))) ?></small>
                        <p><?= nl2br(h($message['mensagem'])) ?></p>
                      </div>
                    <?php endforeach; ?>
                  </div>
                <?php endif; ?>
              </div>
            </details>
          <?php endforeach; ?>
        </div>
      <?php endif; ?>
    </section>
  </div>
</section>

<?php include 'admin_footer.php'; ?>
