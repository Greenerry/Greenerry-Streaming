<?php
require_once '../includes/config.php';
require_user_login();

$uid = current_user_id();
$ok = '';
$err = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $err = verify_csrf_request() ?? '';
    $orderId = (int)($_POST['order_id'] ?? 0);
    $productId = (int)($_POST['product_id'] ?? 0);
    $message = trim((string)($_POST['message'] ?? ''));

    if (!$err && ($orderId <= 0 || $productId <= 0 || $message === '')) {
        $err = current_lang() === 'en' ? 'Write a message for the seller.' : 'Escreve uma mensagem para o vendedor.';
    }

    $thread = null;
    if (!$err) {
        $thread = db_one_prepared(
            $conn,
            "SELECT e.idCliente AS comprador, ei.idArtista
             FROM encomenda e
             JOIN encomenda_item ei ON ei.idEncomenda = e.idEncomenda
             WHERE e.idEncomenda = ?
               AND e.idCliente = ?
               AND ei.idProduto = ?
             LIMIT 1",
            'iii',
            [$orderId, $uid, $productId]
        );
        if (!$thread) {
            $err = tr('error.api_invalid_request');
        }
    }

    if (!$err && $thread) {
        db_prepared(
            $conn,
            "INSERT INTO encomenda_mensagem (idEncomenda, idProduto, idComprador, idArtista, remetente, mensagem)
             VALUES (?, ?, ?, ?, 'comprador', ?)",
            'iiiis',
            [$orderId, $productId, $uid, (int)$thread['idArtista'], $message]
        );
        create_notification(
            $conn,
            (int)$thread['idArtista'],
            current_lang() === 'en' ? 'Buyer message' : 'Mensagem do comprador',
            (current_lang() === 'en' ? 'Order #' : 'Encomenda #') . $orderId . ': ' . mb_substr($message, 0, 120),
            'encomenda'
        );
        $ok = current_lang() === 'en' ? 'Message sent to the seller.' : 'Mensagem enviada ao vendedor.';
    }
}

$orders = db_all_prepared(
    $conn,
    "SELECT e.*, me.morada, me.cidade, me.codigo_postal, me.pais
     FROM encomenda e
     LEFT JOIN morada_encomenda me ON me.idEncomenda = e.idEncomenda
     WHERE e.idCliente = ?
     ORDER BY e.criado_em DESC",
    'i',
    [$uid]
);

include '../includes/header.php';
?>

<section class="content-shell">
  <div class="wrap">
    <div class="page-intro">
      <span class="slabel" data-t="my_orders_label">Compras</span>
      <h2 data-t="my_orders_title">As minhas encomendas</h2>
      <p data-t="my_orders_intro">Acompanha o estado, abre o recibo e fala com o vendedor sobre cada artigo.</p>
    </div>

    <?php if ($ok): ?><div class="alert alert-ok"><?= h($ok) ?></div><?php endif; ?>
    <?php if ($err): ?><div class="alert alert-err"><?= h($err) ?></div><?php endif; ?>

    <?php if (!$orders): ?>
      <div class="cart-empty-state">
        <div class="cart-empty-icon">Bag</div>
        <h3 data-t="profile_orders_empty">Ainda não fizeste compras.</h3>
        <a href="shop.php" class="btn btn-ghost btn-sm" data-t="shop_title">Merch de artistas</a>
      </div>
    <?php else: ?>
      <div class="order-stack">
        <?php foreach ($orders as $order): ?>
          <?php
          $items = db_all_prepared(
              $conn,
              "SELECT ei.*, p.idProduto, c.nome AS artista_nome, c.email AS artista_email, t.etiqueta
               FROM encomenda_item ei
               JOIN produto p ON p.idProduto = ei.idProduto
               JOIN cliente c ON c.idCliente = ei.idArtista
               LEFT JOIN tamanho t ON t.idTamanho = ei.idTamanho
               WHERE ei.idEncomenda = ?
               ORDER BY ei.idEncomendaItem ASC",
              'i',
              [(int)$order['idEncomenda']]
          );
          ?>
          <details class="order-accordion card surface-card order-shell" open>
            <summary class="order-accordion-summary">
              <div class="order-accordion-summary-main">
                <span class="badge badge-dark"><span data-t="orders_order_label">Encomenda</span> #<?= (int)$order['idEncomenda'] ?></span>
                <h3 class="order-accordion-title"><?= h(format_eur((float)$order['total_final'])) ?></h3>
                <p class="order-accordion-meta"><?= date('d/m/Y', strtotime($order['criado_em'])) ?></p>
              </div>
              <div class="order-accordion-summary-side">
                <span class="badge <?= h(state_badge_class($order['estado_encomenda'])) ?>" data-status-label="<?= h($order['estado_encomenda']) ?>"><?= h(order_status_label($order['estado_encomenda'])) ?></span>
                <a href="receipt.php?id=<?= (int)$order['idEncomenda'] ?>" class="btn btn-ghost btn-sm" target="_blank" rel="noopener" data-t="profile_receipt">Recibo</a>
              </div>
            </summary>
            <div class="order-accordion-body">
              <div class="order-tracking">
                <?php foreach (['pendente', 'em_preparacao', 'enviada', 'entregue'] as $step): ?>
                  <?php $isOn = array_search($step, ['pendente', 'em_preparacao', 'enviada', 'entregue'], true) <= array_search((string)$order['estado_encomenda'], ['pendente', 'em_preparacao', 'enviada', 'entregue'], true); ?>
                  <span class="<?= $isOn ? 'on' : '' ?>" data-status-label="<?= h($step) ?>"><?= h(order_status_label($step)) ?></span>
                <?php endforeach; ?>
              </div>

              <?php if (!empty($order['morada'])): ?>
                <div class="order-delivery-card">
                  <div class="order-delivery-item"><span class="order-delivery-label" data-t="checkout_address">Morada</span><p><?= h($order['morada']) ?></p></div>
                  <div class="order-delivery-item"><span class="order-delivery-label" data-t="checkout_city">Cidade</span><p><?= h($order['cidade']) ?> <?= h($order['codigo_postal']) ?></p></div>
                  <div class="order-delivery-item"><span class="order-delivery-label" data-t="checkout_country">País</span><p><?= h($order['pais']) ?></p></div>
                </div>
              <?php endif; ?>

              <div class="simple-list">
                <?php foreach ($items as $item): ?>
                  <?php
                  $productImage = product_main_image($conn, (int)$item['idProduto']);
                  $messages = db_all_prepared(
                      $conn,
                      "SELECT * FROM encomenda_mensagem
                       WHERE idEncomenda = ?
                         AND idProduto = ?
                         AND idComprador = ?
                       ORDER BY criado_em ASC",
                      'iii',
                      [(int)$order['idEncomenda'], (int)$item['idProduto'], $uid]
                  );
                  ?>
                  <div class="buyer-order-item">
                    <div class="simple-list-item">
                      <div class="order-product-info">
                        <div class="profile-thumb order-product-thumb">
                          <?php if ($productImage): ?><img src="<?= h(asset_url('img', $productImage)) ?>" alt=""><?php endif; ?>
                        </div>
                        <div>
                          <strong><?= h($item['nome_produto']) ?></strong>
                          <p><?= h($item['artista_nome']) ?> · <?= h(count_label((int)$item['quantidade'], 'unit')) ?><?= $item['etiqueta'] ? ' · ' . h($item['etiqueta']) : '' ?></p>
                        </div>
                      </div>
                      <span class="badge <?= h(state_badge_class($item['estado_item'])) ?>" data-status-label="<?= h($item['estado_item']) ?>"><?= h(order_status_label($item['estado_item'])) ?></span>
                    </div>

                    <div class="order-message-thread">
                      <?php foreach ($messages as $messageRow): ?>
                        <div class="order-message-bubble <?= $messageRow['remetente'] === 'comprador' ? 'from-me' : '' ?>">
                          <span data-t="<?= $messageRow['remetente'] === 'comprador' ? 'message_you' : 'message_seller' ?>"><?= $messageRow['remetente'] === 'comprador' ? 'Tu' : 'Vendedor' ?></span>
                          <p><?= nl2br(h($messageRow['mensagem'])) ?></p>
                        </div>
                      <?php endforeach; ?>
                      <form method="post" class="order-message-form">
                        <?= csrf_input() ?>
                        <input type="hidden" name="order_id" value="<?= (int)$order['idEncomenda'] ?>">
                        <input type="hidden" name="product_id" value="<?= (int)$item['idProduto'] ?>">
                        <textarea name="message" class="finput" rows="2" maxlength="1200" data-tp="my_orders_message_placeholder" placeholder="Pergunta ao vendedor sobre esta encomenda"></textarea>
                        <button type="submit" class="btn btn-dark btn-sm" data-t="my_orders_contact_seller">Contactar vendedor</button>
                      </form>
                    </div>
                  </div>
                <?php endforeach; ?>
              </div>
            </div>
          </details>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>
  </div>
</section>

<?php include '../includes/footer.php'; ?>
