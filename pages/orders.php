<?php
require_once '../includes/config.php';
require_user_login();

$uid = current_user_id();
$feedback = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $orderId = (int)($_POST['order_id'] ?? 0);
    $action = $_POST['action'] ?? '';

    if ($orderId > 0 && in_array($action, ['prepare', 'ship', 'deliver', 'cancel'], true)) {
        $ownedItems = db_all(
            $conn,
            "SELECT ei.*, p.usa_tamanhos
             FROM encomenda_item ei
             JOIN produto p ON p.idProduto = ei.idProduto
             WHERE ei.idEncomenda = {$orderId}
               AND ei.idArtista = {$uid}"
        );

        if ($ownedItems) {
            $nextState = match ($action) {
                'prepare' => 'em_preparacao',
                'ship' => 'enviado',
                'deliver' => 'entregue',
                'cancel' => 'cancelado',
            };

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

                $orderState = 'pendente';
                if ((int)$summary['cancelados'] === (int)$summary['total']) {
                    $orderState = 'cancelada';
                } elseif ((int)$summary['entregues'] === (int)$summary['total']) {
                    $orderState = 'entregue';
                } elseif ((int)$summary['enviados'] > 0 || (int)$summary['entregues'] > 0) {
                    $orderState = 'enviada';
                } elseif ((int)$summary['em_preparacao'] > 0) {
                    $orderState = 'em_preparacao';
                }

                mysqli_query($conn, "UPDATE encomenda SET estado_encomenda = '{$orderState}' WHERE idEncomenda = {$orderId}");
                mysqli_commit($conn);

                $feedback = 'Estado da encomenda atualizado.';
            } catch (Throwable $e) {
                mysqli_rollback($conn);
                $error = 'Nao foi possivel atualizar a encomenda.';
            }
        }
    }
}

$orders = db_all(
    $conn,
    "SELECT
        e.idEncomenda,
        e.created_at,
        e.estado_encomenda,
        e.total_final,
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
     GROUP BY e.idEncomenda, e.created_at, e.estado_encomenda, e.total_final, c.nome, me.telefone, me.morada, me.cidade, me.codigo_postal
     ORDER BY e.created_at DESC"
);

include '../includes/header.php';
?>

<section class="content-shell">
  <div class="wrap">
    <div class="page-intro">
      <span class="slabel">Pedidos</span>
      <h2>Encomendas do teu merch</h2>
      <p>Acompanha o estado das encomendas e marca o progresso de cada pedido.</p>
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
          <p>Ainda nao existem encomendas para os teus produtos.</p>
        </div>
      </div>
    <?php else: ?>
      <div class="order-stack">
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
          ?>
          <article class="card surface-card order-shell">
            <div class="card-body">
              <div class="between mb4 order-head">
                <div>
                  <span class="badge badge-dark">Encomenda #<?= (int)$order['idEncomenda'] ?></span>
                  <h3 class="mt4"><?= h($order['cliente_nome']) ?></h3>
                  <p><?= date('d/m/Y', strtotime($order['created_at'])) ?> • <?= h($order['morada']) ?>, <?= h($order['cidade']) ?> <?= h($order['codigo_postal']) ?></p>
                </div>
                <div class="order-status-side">
                  <span class="badge <?= h(state_badge_class($order['estado_encomenda'])) ?>"><?= h(order_status_label($order['estado_encomenda'])) ?></span>
                  <strong><?= h(format_eur((float)$order['total_final'])) ?></strong>
                </div>
              </div>

              <div class="simple-list">
                <?php foreach ($items as $item): ?>
                  <div class="simple-list-item">
                    <div>
                      <strong><?= h($item['nome_produto']) ?></strong>
                      <p>
                        <?= (int)$item['quantidade'] ?> unidade(s)
                        <?php if (!empty($item['etiqueta'])): ?>
                          • Tamanho <?= h($item['etiqueta']) ?>
                        <?php endif; ?>
                      </p>
                    </div>
                    <div class="order-line-side">
                      <span class="badge <?= h(state_badge_class($item['estado_item'])) ?>"><?= h(order_status_label($item['estado_item'])) ?></span>
                      <span><?= h(format_eur((float)$item['valor_artista'])) ?></span>
                    </div>
                  </div>
                <?php endforeach; ?>
              </div>

              <form method="post" class="admin-action-buttons mt6">
                <input type="hidden" name="order_id" value="<?= (int)$order['idEncomenda'] ?>">
                <button type="submit" name="action" value="prepare" class="btn btn-ghost btn-sm">Em preparacao</button>
                <button type="submit" name="action" value="ship" class="btn btn-ghost btn-sm">Marcar enviada</button>
                <button type="submit" name="action" value="deliver" class="btn btn-dark btn-sm">Marcar entregue</button>
                <button type="submit" name="action" value="cancel" class="btn btn-danger btn-sm">Cancelar itens</button>
              </form>
            </div>
          </article>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>
  </div>
</section>

<?php include '../includes/footer.php'; ?>
