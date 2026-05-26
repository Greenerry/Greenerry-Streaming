<?php
require_once '../includes/config.php';
require_user_login();

$uid = current_user_id();
$feedback = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $error = verify_csrf_request() ?? '';
    $orderId = (int)($_POST['order_id'] ?? 0);
    $action = $_POST['action'] ?? '';

    if (!$error && $orderId > 0 && in_array($action, ['prepare', 'ship', 'deliver', 'cancel'], true)) {
        // Artists can only update the order lines that belong to their own merch.
        $ownedItems = db_all(
            $conn,
            "SELECT ei.*, p.usa_tamanhos
             FROM encomenda_item ei
             JOIN produto p ON p.idProduto = ei.idProduto
             WHERE ei.idEncomenda = {$orderId}
               AND ei.idArtista = {$uid}
               AND ei.estado_item != 'cancelado'"
        );

        if ($ownedItems) {
            switch ($action) {
                case 'prepare':
                    $nextState = 'em_preparacao';
                    break;
                case 'ship':
                    $nextState = 'enviado';
                    break;
                case 'deliver':
                    $nextState = 'entregue';
                    break;
                case 'cancel':
                    $nextState = 'cancelado';
                    break;
                default:
                    $nextState = '';
                    break;
            }

            // Item updates and the parent order state rollup must stay together.
            mysqli_begin_transaction($conn);

            try {
                foreach ($ownedItems as $item) {
                    mysqli_query(
                        $conn,
                        "UPDATE encomenda_item
                         SET estado_item = '{$nextState}'
                         WHERE idEncomendaItem = " . (int)$item['idEncomendaItem']
                    );

                    if ($action === 'cancel') {
                        // Cancelled items return stock to both total and size-specific buckets.
                        mysqli_query(
                            $conn,
                            "UPDATE produto
                             SET stock_total = stock_total + " . (int)$item['quantidade'] . "
                             WHERE idProduto = " . (int)$item['idProduto']
                        );

                        if (!empty($item['idTamanho'])) {
                            mysqli_query(
                                $conn,
                                "UPDATE produto_tamanho_stock
                                 SET stock = stock + " . (int)$item['quantidade'] . "
                                 WHERE idProduto = " . (int)$item['idProduto'] . "
                                   AND idTamanho = " . (int)$item['idTamanho']
                            );
                        }
                    }
                }

                // Recalculate the visible order state from all item states.
                $summary = db_one(
                    $conn,
                    "SELECT
                        SUM(estado_item = 'pendente') AS pendentes,
                        SUM(estado_item = 'em_preparacao') AS em_preparacao,
                        SUM(estado_item = 'enviado') AS enviados,
                        SUM(estado_item = 'entregue') AS entregues,
                        SUM(estado_item = 'cancelado') AS cancelados,
                        COUNT(*) AS total
                     FROM encomenda_item
                     WHERE idEncomenda = {$orderId}"
                );

                $totalItems = (int)$summary['total'];
                $cancelledItems = (int)$summary['cancelados'];
                $activeItems = max(0, $totalItems - $cancelledItems);

                $orderState = 'pendente';
                if ($cancelledItems === $totalItems) {
                    $orderState = 'cancelada';
                } elseif ((int)$summary['entregues'] === $activeItems) {
                    $orderState = 'entregue';
                } elseif ((int)$summary['enviados'] + (int)$summary['entregues'] === $activeItems) {
                    $orderState = 'enviada';
                } elseif ((int)$summary['em_preparacao'] > 0) {
                    $orderState = 'em_preparacao';
                }

                $paymentUpdate = $orderState === 'cancelada' ? ", estado_pagamento = 'reembolsado'" : '';
                mysqli_query($conn, "UPDATE encomenda SET estado_encomenda = '{$orderState}'{$paymentUpdate} WHERE idEncomenda = {$orderId}");
                $orderOwner = db_one($conn, "SELECT idCliente FROM encomenda WHERE idEncomenda = {$orderId} LIMIT 1");
                if ($orderOwner) {
                    create_notification(
                        $conn,
                        (int)$orderOwner['idCliente'],
                        current_lang() === 'en' ? 'Order updated' : 'Encomenda atualizada',
                        (current_lang() === 'en' ? 'Order #' : 'A encomenda #') . $orderId . (current_lang() === 'en' ? ' is now ' : ' está agora ') . order_status_label($orderState) . '.',
                        'encomenda'
                    );
                }
                mysqli_commit($conn);

                $feedback = tr('success.order_artist_updated');
            } catch (Throwable $e) {
                mysqli_rollback($conn);
                $error = tr('error.order_update');
            }
        } else {
            $error = tr('error.order_no_editable_items');
        }
    }
}

// Show only orders containing merch sold by the current artist.
$orders = db_all(
    $conn,
    "SELECT
        e.idEncomenda,
        e.criado_em,
        e.estado_encomenda,
        e.total_final,
        e.observacoes,
        c.nome AS cliente_nome,
        me.telefone,
        me.morada,
        me.cidade,
        me.codigo_postal
     FROM encomenda e
     JOIN encomenda_item ei ON ei.idEncomenda = e.idEncomenda
     JOIN cliente c ON c.idCliente = e.idCliente
     LEFT JOIN morada_encomenda me ON me.idEncomenda = e.idEncomenda
     WHERE ei.idArtista = {$uid}
     GROUP BY e.idEncomenda, e.criado_em, e.estado_encomenda, e.total_final, e.observacoes, c.nome, me.telefone, me.morada, me.cidade, me.codigo_postal
     ORDER BY e.criado_em DESC"
);

$orderCounts = [
    'all' => count($orders),
    'pendente' => 0,
    'em_preparacao' => 0,
    'enviada' => 0,
    'entregue' => 0,
    'cancelada' => 0,
];
foreach ($orders as $orderRow) {
    $state = (string)$orderRow['estado_encomenda'];
    if (isset($orderCounts[$state])) {
        $orderCounts[$state]++;
    }
}

include '../includes/header.php';
?>

<section class="content-shell">
  <div class="wrap">
    <div class="page-intro">
      <span class="slabel" data-t="orders_label">Pedidos</span>
      <h2 data-t="orders_title">Encomendas do teu merch</h2>
      <p data-t="orders_intro">Acompanha o estado das encomendas e marca o progresso de cada pedido.</p>
    </div>

    <?php if ($feedback): ?>
      <div class="alert alert-ok"><?= h($feedback) ?></div>
    <?php endif; ?>
    <?php if ($error): ?>
      <div class="alert alert-err"><?= h($error) ?></div>
    <?php endif; ?>

    <?php if (!$orders): ?>
      <div class="card surface-card">
        <div class="card-body text-center">
          <p data-t="orders_empty">Ainda não existem encomendas para os teus produtos.</p>
        </div>
      </div>
    <?php else: ?>
      <div class="orders-filter-bar">
        <label class="sbar orders-search">
          <svg width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><circle cx="11" cy="11" r="7"/><path d="m21 21-4.3-4.3"/></svg>
          <input type="search" id="orders-search" data-tp="orders_search_placeholder" placeholder="Pesquisar pedidos">
        </label>
        <nav class="artist-filter-pills orders-filter-pills" aria-label="Order filters">
          <button type="button" class="on" data-order-filter="all">
            <span data-t="orders_filter_all">Todos</span>
            <span class="orders-filter-count"><?= (int)$orderCounts['all'] ?></span>
          </button>
          <button type="button" data-order-filter="pendente">
            <span data-t="orders_filter_pending">Por confirmar</span>
            <span class="orders-filter-count"><?= (int)$orderCounts['pendente'] ?></span>
          </button>
          <button type="button" data-order-filter="em_preparacao">
            <span data-t="orders_action_prepare">Em preparacao</span>
            <span class="orders-filter-count"><?= (int)$orderCounts['em_preparacao'] ?></span>
          </button>
          <button type="button" data-order-filter="enviada">
            <span data-t="orders_filter_shipped">Enviada</span>
            <span class="orders-filter-count"><?= (int)$orderCounts['enviada'] ?></span>
          </button>
          <button type="button" data-order-filter="entregue">
            <span data-t="orders_filter_delivered">Entregue</span>
            <span class="orders-filter-count"><?= (int)$orderCounts['entregue'] ?></span>
          </button>
          <button type="button" data-order-filter="cancelada">
            <span data-t="orders_filter_cancelled">Cancelada</span>
            <span class="orders-filter-count"><?= (int)$orderCounts['cancelada'] ?></span>
          </button>
        </nav>
      </div>

      <div class="order-stack" id="orders-list">
        <?php foreach ($orders as $order): ?>
          <?php
          $items = db_all(
              $conn,
              "SELECT ei.*, t.etiqueta
               FROM encomenda_item ei
               LEFT JOIN tamanho t ON t.idTamanho = ei.idTamanho
               WHERE ei.idEncomenda = " . (int)$order['idEncomenda'] . "
                 AND ei.idArtista = {$uid}
               ORDER BY ei.idEncomendaItem ASC"
          );
          $hasEditableItems = false;
          foreach ($items as $itemCheck) {
              if ($itemCheck['estado_item'] !== 'cancelado') {
                  $hasEditableItems = true;
                  break;
              }
          }
          ?>
          <details
            class="order-accordion card surface-card order-shell"
            data-order-card
            data-order-status="<?= h($order['estado_encomenda']) ?>"
            data-order-search="<?= h(strtolower('#' . (int)$order['idEncomenda'] . ' ' . $order['cliente_nome'] . ' ' . $order['morada'] . ' ' . $order['cidade'] . ' ' . $order['codigo_postal'] . ' ' . ($order['telefone'] ?? '') . ' ' . ($order['observacoes'] ?? ''))) ?>"
          >
            <summary class="order-accordion-summary">
              <div class="order-accordion-summary-main">
                <span class="badge badge-dark"><span data-t="orders_order_label">Encomenda</span> #<?= (int)$order['idEncomenda'] ?></span>
                <h3 class="order-accordion-title"><?= h($order['cliente_nome']) ?></h3>
                <p class="order-accordion-meta"><?= date('d/m/Y', strtotime($order['criado_em'])) ?></p>
              </div>
              <div class="order-accordion-summary-side">
                <span class="badge <?= h(state_badge_class($order['estado_encomenda'])) ?>" data-status-label="<?= h($order['estado_encomenda']) ?>"><?= h(order_status_label($order['estado_encomenda'])) ?></span>
                <strong class="order-accordion-total"><?= h(format_eur((float)$order['total_final'])) ?></strong>
                <span class="order-accordion-chevron" aria-hidden="true">
                  <svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="m6 9 6 6 6-6"/></svg>
                </span>
              </div>
            </summary>

            <div class="order-accordion-body">
              <div class="order-delivery-card">
                <div class="order-delivery-item">
                  <span class="order-delivery-label" data-t="checkout_address">Morada</span>
                  <p><?= h($order['morada']) ?></p>
                </div>
                <?php if (!empty($order['telefone'])): ?>
                  <div class="order-delivery-item">
                    <span class="order-delivery-label" data-t="checkout_phone">Telefone</span>
                    <p><a href="tel:<?= h(preg_replace('/\s+/', '', $order['telefone'])) ?>" class="auth-link"><?= h($order['telefone']) ?></a></p>
                  </div>
                <?php endif; ?>
                <div class="order-delivery-item">
                  <span class="order-delivery-label" data-t="checkout_city">Cidade</span>
                  <p><?= h($order['cidade']) ?> <span class="order-delivery-postal"><?= h($order['codigo_postal']) ?></span></p>
                </div>
              </div>

              <?php if (!empty($order['observacoes'])): ?>
                <div class="order-customer-note">
                  <span class="slabel"><?= current_lang() === 'en' ? 'Customer instructions' : 'Instrucoes do cliente' ?></span>
                  <p><?= nl2br(h($order['observacoes'])) ?></p>
                </div>
              <?php endif; ?>

              <div class="simple-list">
                <?php foreach ($items as $item): ?>
                  <?php $productImage = product_main_image($conn, (int)$item['idProduto']); ?>
                  <div class="simple-list-item">
                    <div class="order-product-info">
                      <div class="profile-thumb order-product-thumb">
                        <?php if ($productImage !== ''): ?>
                          <img src="<?= h(asset_url('img', $productImage)) ?>" alt="<?= h($item['nome_produto']) ?>">
                        <?php else: ?>
                          <span data-t="products_no_image">Sem imagem</span>
                        <?php endif; ?>
                      </div>
                      <div>
                        <strong><?= h($item['nome_produto']) ?></strong>
                        <p>
                          <?= h(count_label((int)$item['quantidade'], 'unit')) ?>
                          <?php if (!empty($item['etiqueta'])): ?>
                            &bull; <span data-t="product_size">Tamanho</span> <?= h($item['etiqueta']) ?>
                          <?php endif; ?>
                        </p>
                      </div>
                    </div>
                    <div class="order-line-side">
                      <span class="badge <?= h(state_badge_class($item['estado_item'])) ?>" data-status-label="<?= h($item['estado_item']) ?>"><?= h(order_status_label($item['estado_item'])) ?></span>
                      <span><?= h(format_eur($item['estado_item'] === 'cancelado' ? 0.0 : (float)$item['valor_artista'])) ?></span>
                    </div>
                  </div>
                <?php endforeach; ?>
              </div>

              <div class="order-actions-bar mt6">
                <?php if ($hasEditableItems && $order['estado_encomenda'] !== 'cancelada'): ?>
                  <form method="post" class="order-actions-form">
                    <?= csrf_input() ?>
                    <input type="hidden" name="order_id" value="<?= (int)$order['idEncomenda'] ?>">
                    <button type="submit" name="action" value="prepare" class="btn btn-ghost btn-sm" data-t="orders_action_prepare">Em preparacao</button>
                    <button type="submit" name="action" value="ship" class="btn btn-ghost btn-sm" data-t="orders_action_ship">Marcar enviada</button>
                    <button type="submit" name="action" value="deliver" class="btn btn-dark btn-sm" data-t="orders_action_deliver">Marcar entregue</button>
                    <button type="submit" name="action" value="cancel" class="btn btn-danger btn-sm" data-t="orders_action_cancel">Cancelar itens</button>
                  </form>
                <?php else: ?>
                  <p class="order-actions-note color-text3" data-t="orders_cancelled_locked">Itens cancelados ficam bloqueados e não podem ser alterados.</p>
                <?php endif; ?>
                <a
                  href="receipt.php?id=<?= (int)$order['idEncomenda'] ?>"
                  class="btn btn-ghost btn-sm order-receipt-btn"
                  target="_blank"
                  rel="noopener"
                ><?= h(tr('orders.view_receipt')) ?></a>
              </div>
            </div>
          </details>
        <?php endforeach; ?>
      </div>
      <p class="empty-copy is-hidden" id="orders-filter-empty" data-t="orders_filter_empty">Nenhuma encomenda corresponde ao filtro.</p>
    <?php endif; ?>
  </div>
</section>

<?php include '../includes/footer.php'; ?>
