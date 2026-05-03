<?php
require_once '../includes/config.php';
require_admin_login();

$feedback = '';
$error = '';
$orderStates = ['pendente', 'em_preparacao', 'enviada', 'entregue', 'cancelada'];
$paymentStates = ['pendente', 'pago', 'falhado', 'reembolsado'];
$itemStateMap = [
    'pendente' => 'pendente',
    'em_preparacao' => 'em_preparacao',
    'enviada' => 'enviado',
    'entregue' => 'entregue',
    'cancelada' => 'cancelado',
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $error = verify_csrf_request() ?? '';
    $orderId = (int)($_POST['order_id'] ?? 0);
    $newOrderState = (string)($_POST['estado_encomenda'] ?? '');
    $newPaymentState = (string)($_POST['estado_pagamento'] ?? '');

    if ($error === '' && ($orderId <= 0 || !in_array($newOrderState, $orderStates, true) || !in_array($newPaymentState, $paymentStates, true))) {
        $error = tr('error.api_invalid_request');
    }

    $currentOrder = $error === '' ? db_one($conn, "SELECT estado_encomenda FROM encomenda WHERE idEncomenda = {$orderId} LIMIT 1") : null;
    if ($error === '' && !$currentOrder) {
        $error = tr('error.api_invalid_request');
    }

    if ($error === '' && (string)$currentOrder['estado_encomenda'] === 'cancelada') {
        $error = tr('error.cancelled_order_locked');
    }

    if ($error === '') {
        $itemState = $itemStateMap[$newOrderState];
        if ($newOrderState === 'cancelada' && $newPaymentState === 'pago') {
            $newPaymentState = 'reembolsado';
        }

        mysqli_begin_transaction($conn);
        $ok = mysqli_query($conn, "UPDATE encomenda SET estado_encomenda = '{$newOrderState}', estado_pagamento = '{$newPaymentState}' WHERE idEncomenda = {$orderId}")
            && mysqli_query($conn, "UPDATE encomenda_item SET estado_item = '{$itemState}' WHERE idEncomenda = {$orderId}");

        if ($ok) {
            mysqli_commit($conn);
            $feedback = tr('success.order_admin_updated');
        } else {
            mysqli_rollback($conn);
            $error = tr('error.order_update');
        }
    }
}

$orders = db_all(
    $conn,
    "SELECT e.*, c.nome AS cliente_nome, c.email,
            COUNT(ei.idEncomendaItem) AS total_items
     FROM encomenda e
     JOIN cliente c ON c.idCliente = e.idCliente
     LEFT JOIN encomenda_item ei ON ei.idEncomenda = e.idEncomenda
     GROUP BY e.idEncomenda
     ORDER BY e.created_at DESC
     LIMIT 80"
);

$stats = db_one(
    $conn,
    "SELECT
        COUNT(*) AS total_orders,
        SUM(estado_encomenda = 'pendente') AS pending_orders,
        SUM(estado_encomenda = 'cancelada') AS cancelled_orders,
        COALESCE(SUM(CASE WHEN estado_pagamento = 'pago' AND estado_encomenda != 'cancelada' THEN total_final END), 0) AS paid_total
     FROM encomenda"
) ?? [];

include 'admin_header.php';
?>

<div class="admin-top">
  <div>
    <h2 data-admin-t="orders_title">Encomendas</h2>
  </div>
</div>

<?php if ($feedback): ?>
  <div class="alert alert-ok"><?= h($feedback) ?></div>
<?php endif; ?>
<?php if ($error): ?>
  <div class="alert alert-err"><?= h($error) ?></div>
<?php endif; ?>

<div class="stats-grid">
  <div class="stat"><div class="stat-val"><?= (int)($stats['total_orders'] ?? 0) ?></div><div class="stat-lbl" data-admin-t="orders_total">Total</div></div>
  <div class="stat"><div class="stat-val"><?= (int)($stats['pending_orders'] ?? 0) ?></div><div class="stat-lbl" data-admin-t="state_pending">Pendentes</div></div>
  <div class="stat"><div class="stat-val"><?= (int)($stats['cancelled_orders'] ?? 0) ?></div><div class="stat-lbl" data-admin-t="state_cancelled">Canceladas</div></div>
  <div class="stat"><div class="stat-val"><?= h(format_eur((float)($stats['paid_total'] ?? 0))) ?></div><div class="stat-lbl" data-admin-t="orders_paid_value">Valor pago</div></div>
</div>

<div class="admin-search-row">
  <label class="sbar">
    <svg width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><circle cx="11" cy="11" r="7"/><path d="m21 21-4.3-4.3"/></svg>
    <input type="search" data-admin-search="orders-search" placeholder="Pesquisar..." data-admin-tp="admin_search_placeholder">
  </label>
</div>

<div id="orders-search" data-admin-search-scope>
<section class="acard-box">
  <div class="acard-box-head">
    <h4 data-admin-t="orders_recent">Encomendas recentes</h4>
  </div>

  <div class="tbl-wrap">
    <table>
      <thead>
        <tr>
          <th>ID</th>
          <th data-admin-t="orders_customer">Cliente</th>
          <th data-admin-t="orders_items">Items</th>
          <th data-admin-t="orders_total_value">Total</th>
          <th data-admin-t="orders_order_state">Estado</th>
          <th data-admin-t="orders_payment_state">Pagamento</th>
          <th data-admin-t="orders_action">Acao</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($orders as $order): ?>
          <?php $locked = (string)$order['estado_encomenda'] === 'cancelada'; ?>
          <tr>
            <td>#<?= (int)$order['idEncomenda'] ?></td>
            <td><strong><?= h($order['cliente_nome']) ?></strong><br><span><?= h($order['email']) ?></span></td>
            <td><?= (int)$order['total_items'] ?></td>
            <td><?= h(format_eur((float)$order['total_final'])) ?></td>
            <td><span class="badge <?= h(state_badge_class((string)$order['estado_encomenda'])) ?>"><?= h(order_status_label((string)$order['estado_encomenda'])) ?></span></td>
            <td><span class="badge <?= h(state_badge_class((string)$order['estado_pagamento'])) ?>"><?= h(payment_status_label((string)$order['estado_pagamento'])) ?></span></td>
            <td>
              <?php if ($locked): ?>
                <span class="admin-card-note" data-admin-t="orders_locked">Bloqueada</span>
              <?php else: ?>
                <form method="post" class="admin-table-form">
                  <?= csrf_input() ?>
                  <input type="hidden" name="order_id" value="<?= (int)$order['idEncomenda'] ?>">
                  <select name="estado_encomenda" class="finput">
                    <?php foreach ($orderStates as $state): ?>
                      <option value="<?= h($state) ?>" <?= $state === (string)$order['estado_encomenda'] ? 'selected' : '' ?>><?= h(order_status_label($state)) ?></option>
                    <?php endforeach; ?>
                  </select>
                  <select name="estado_pagamento" class="finput">
                    <?php foreach ($paymentStates as $state): ?>
                      <option value="<?= h($state) ?>" <?= $state === (string)$order['estado_pagamento'] ? 'selected' : '' ?>><?= h(payment_status_label($state)) ?></option>
                    <?php endforeach; ?>
                  </select>
                  <button type="submit" class="btn btn-ghost btn-sm" data-admin-t="categories_save">Guardar</button>
                </form>
              <?php endif; ?>
            </td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</section>
</div>

<?php include 'admin_footer.php'; ?>
