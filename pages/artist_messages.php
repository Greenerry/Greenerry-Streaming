<?php
require_once '../includes/config.php';
require_user_login();

$uid = current_user_id();
$feedback = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $error = verify_csrf_request() ?? '';
    $orderId = (int)($_POST['order_id'] ?? 0);
    $productId = (int)($_POST['product_id'] ?? 0);
    $buyerId = (int)($_POST['buyer_id'] ?? 0);
    $message = trim((string)($_POST['message'] ?? ''));

    if (!$error && ($orderId <= 0 || $productId <= 0 || $buyerId <= 0 || $message === '')) {
        $error = current_lang() === 'en' ? 'Write a reply for the buyer.' : 'Escreve uma resposta para o comprador.';
    }

    $thread = null;
    if (!$error) {
        $thread = db_one_prepared(
            $conn,
            "SELECT e.idCliente AS comprador, ei.idArtista, ei.nome_produto
             FROM encomenda e
             JOIN encomenda_item ei ON ei.idEncomenda = e.idEncomenda
             WHERE e.idEncomenda = ?
               AND e.idCliente = ?
               AND ei.idProduto = ?
               AND ei.idArtista = ?
             LIMIT 1",
            'iiii',
            [$orderId, $buyerId, $productId, $uid]
        );
        if (!$thread) {
            $error = tr('error.api_invalid_request');
        }
    }

    if (!$error && $thread) {
        db_prepared(
            $conn,
            "INSERT INTO encomenda_mensagem (idEncomenda, idProduto, idComprador, idArtista, remetente, mensagem)
             VALUES (?, ?, ?, ?, 'artista', ?)",
            'iiiis',
            [$orderId, $productId, $buyerId, $uid, $message]
        );
        db_prepared(
            $conn,
            "UPDATE encomenda_mensagem
             SET lida = 1
             WHERE idEncomenda = ?
               AND idProduto = ?
               AND idComprador = ?
               AND idArtista = ?
               AND remetente = 'comprador'",
            'iiii',
            [$orderId, $productId, $buyerId, $uid]
        );
        create_notification(
            $conn,
            $buyerId,
            current_lang() === 'en' ? 'Seller replied' : 'Resposta do vendedor',
            (current_lang() === 'en' ? 'Order #' : 'Encomenda #') . $orderId . ': ' . mb_substr($message, 0, 120),
            'encomenda'
        );
        $feedback = current_lang() === 'en' ? 'Reply sent.' : 'Resposta enviada.';
    }
}

$threads = db_all_prepared(
    $conn,
    "SELECT
        grouped.idEncomenda,
        grouped.idProduto,
        grouped.idComprador,
        grouped.last_message_id,
        grouped.total_messages,
        grouped.unread_count,
        last_msg.mensagem AS last_message,
        last_msg.remetente AS last_sender,
        last_msg.criado_em AS last_at,
        buyer.nome AS buyer_name,
        buyer.email AS buyer_email,
        ei.nome_produto,
        ei.estado_item,
        ei.quantidade,
        e.estado_encomenda,
        e.total_final
     FROM (
        SELECT
          idEncomenda,
          idProduto,
          idComprador,
          MAX(idMensagemEncomenda) AS last_message_id,
          COUNT(*) AS total_messages,
          SUM(remetente = 'comprador' AND lida = 0) AS unread_count
        FROM encomenda_mensagem
        WHERE idArtista = ?
        GROUP BY idEncomenda, idProduto, idComprador
     ) grouped
     JOIN encomenda_mensagem last_msg ON last_msg.idMensagemEncomenda = grouped.last_message_id
     JOIN cliente buyer ON buyer.idCliente = grouped.idComprador
     JOIN encomenda e ON e.idEncomenda = grouped.idEncomenda
     JOIN encomenda_item ei ON ei.idEncomenda = grouped.idEncomenda
       AND ei.idProduto = grouped.idProduto
       AND ei.idArtista = ?
     ORDER BY grouped.unread_count DESC, last_msg.criado_em DESC",
    'ii',
    [$uid, $uid]
);

$openThreads = 0;
$waitingBuyer = 0;
foreach ($threads as $threadRow) {
    if ((int)$threadRow['unread_count'] > 0) {
        $openThreads++;
    }
    if ((string)$threadRow['last_sender'] === 'comprador') {
        $waitingBuyer++;
    }
}

include '../includes/header.php';
?>

<section class="artist-dashboard-v4 artist-animate">
  <header class="artist-dash-top">
    <div>
      <h2 data-t="artist_messages_title">Messages</h2>
    </div>
  </header>

  <?php if ($error): ?><div class="alert alert-err"><?= h($error) ?></div><?php endif; ?>

  <div class="artist-dash-kpis">
    <a href="#artist-order-messages"><span data-t="artist_messages_threads">Threads</span><strong><?= count($threads) ?></strong></a>
    <a href="#artist-order-messages"><span data-t="artist_messages_needs_reply">Needs reply</span><strong><?= (int)$waitingBuyer ?></strong></a>
    <a href="#artist-order-messages"><span data-t="artist_messages_unread">Unread</span><strong><?= (int)$openThreads ?></strong></a>
    <a href="orders.php"><span data-t="artist_messages_orders">Orders</span><strong><?= count($threads) ?></strong></a>
  </div>

  <?php if (!$threads): ?>
    <article class="artist-dash-card">
      <p class="artist-dash-empty" data-t="artist_messages_empty">No buyer messages yet.</p>
    </article>
  <?php else: ?>
    <div class="artist-dash-card-head">
      <div><h3 data-t="artist_messages_conversations">Order conversations</h3></div>
      <label class="artist-search-field">
        <svg width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><circle cx="11" cy="11" r="7"/><path d="m21 21-4.3-4.3"/></svg>
        <input type="search" data-artist-search="artist-messages" data-tp="artist_messages_search" placeholder="Search messages">
      </label>
    </div>
    <div class="artist-message-list" id="artist-order-messages" data-artist-search-scope="artist-messages">
      <?php foreach ($threads as $thread): ?>
        <?php
        $messages = db_all_prepared(
            $conn,
            "SELECT remetente, mensagem, criado_em
             FROM encomenda_mensagem
             WHERE idEncomenda = ?
               AND idProduto = ?
               AND idComprador = ?
               AND idArtista = ?
             ORDER BY criado_em ASC",
            'iiii',
            [(int)$thread['idEncomenda'], (int)$thread['idProduto'], (int)$thread['idComprador'], $uid]
        );
        $productImage = product_main_image($conn, (int)$thread['idProduto']);
        ?>
        <article class="artist-dash-card artist-message-card" data-artist-search-row>
          <div class="artist-message-head">
            <div class="artist-message-product">
              <span class="artist-mini-thumb artist-message-product-thumb">
                <?php if ($productImage): ?><img src="<?= h(asset_url('img', $productImage)) ?>" alt="<?= h($thread['nome_produto']) ?>"><?php else: ?><span><?= h(mb_substr((string)$thread['nome_produto'], 0, 1)) ?></span><?php endif; ?>
              </span>
              <div>
                <span class="artist-dash-kicker"><span data-t="artist_messages_order">Order</span> #<?= (int)$thread['idEncomenda'] ?></span>
                <h2><?= h($thread['buyer_name']) ?></h2>
                <p><?= h($thread['nome_produto']) ?> / <?= h(count_label((int)$thread['quantidade'], 'unit')) ?></p>
              </div>
            </div>
            <div class="artist-message-meta">
              <?php if ((int)$thread['unread_count'] > 0): ?><b><?= (int)$thread['unread_count'] ?> <span data-t="artist_messages_new">new</span></b><?php endif; ?>
              <span class="badge <?= h(state_badge_class($thread['estado_item'])) ?>" data-status-label="<?= h($thread['estado_item']) ?>"><?= h(order_status_label($thread['estado_item'])) ?></span>
            </div>
          </div>

          <div class="order-message-thread artist-message-thread">
            <?php foreach ($messages as $messageRow): ?>
              <div class="order-message-bubble <?= $messageRow['remetente'] === 'artista' ? 'from-me' : '' ?>">
                <span data-t="<?= $messageRow['remetente'] === 'artista' ? 'message_you' : 'message_buyer' ?>"><?= $messageRow['remetente'] === 'artista' ? h(current_lang() === 'en' ? 'You' : 'Tu') : h(current_lang() === 'en' ? 'Buyer' : 'Comprador') ?></span>
                <p><?= nl2br(h($messageRow['mensagem'])) ?></p>
              </div>
            <?php endforeach; ?>
            <form method="post" class="order-message-form">
              <?= csrf_input() ?>
              <input type="hidden" name="order_id" value="<?= (int)$thread['idEncomenda'] ?>">
              <input type="hidden" name="product_id" value="<?= (int)$thread['idProduto'] ?>">
              <input type="hidden" name="buyer_id" value="<?= (int)$thread['idComprador'] ?>">
              <textarea name="message" class="finput" rows="2" maxlength="1200" data-tp="artist_messages_reply_placeholder" placeholder="Reply to the buyer"></textarea>
              <button type="submit" class="btn btn-dark btn-sm" data-t="artist_messages_reply">Reply</button>
            </form>
          </div>
        </article>
      <?php endforeach; ?>
    </div>
  <?php endif; ?>
</section>

<?php include '../includes/footer.php'; ?>
