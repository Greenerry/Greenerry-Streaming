<?php
require_once '../includes/config.php';
require_admin_login();

$adminId = current_admin_id();
$feedback = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $productId = (int)($_POST['product_id'] ?? 0);
    $action = $_POST['action'] ?? '';
    $reason = trim($_POST['reason'] ?? '');
    $reasonSafe = db_escape($conn, $reason);

    if ($productId > 0 && in_array($action, ['aprovar', 'rejeitar', 'inativar', 'reativar'], true)) {
        if ($action === 'aprovar') {
            mysqli_query($conn, "UPDATE produto SET estado = 'aprovado', motivo_rejeicao = NULL, idAdminAprovacao = {$adminId}, aprovado_em = NOW(), ativo = 1 WHERE idProduto = {$productId}");
            $feedback = 'Produto aprovado com sucesso.';
        } elseif ($action === 'rejeitar') {
            mysqli_query($conn, "UPDATE produto SET estado = 'rejeitado', motivo_rejeicao = '{$reasonSafe}', idAdminAprovacao = {$adminId}, aprovado_em = NOW(), ativo = 0 WHERE idProduto = {$productId}");
            $feedback = 'Produto rejeitado.';
        } elseif ($action === 'inativar') {
            mysqli_query($conn, "UPDATE produto SET estado = 'inativo', ativo = 0 WHERE idProduto = {$productId}");
            $feedback = 'Produto inativado.';
        } elseif ($action === 'reativar') {
            mysqli_query($conn, "UPDATE produto SET estado = 'aprovado', ativo = 1 WHERE idProduto = {$productId}");
            $feedback = 'Produto reativado.';
        }
    }
}

$pending = db_all(
    $conn,
    "SELECT p.*, c.nome AS artista, cat.nomeCategoria
     FROM produto p
     JOIN cliente c ON c.idCliente = p.idCliente
     JOIN categoria cat ON cat.idCategoria = p.idCategoria
     WHERE p.estado = 'pendente'
     ORDER BY p.created_at DESC"
);

$allProducts = db_all(
    $conn,
    "SELECT p.*, c.nome AS artista, cat.nomeCategoria
     FROM produto p
     JOIN cliente c ON c.idCliente = p.idCliente
     JOIN categoria cat ON cat.idCategoria = p.idCategoria
     ORDER BY p.created_at DESC"
);

$productStats = [
    'pendentes' => 0,
    'aprovados' => 0,
    'rejeitados' => 0,
    'inativos' => 0,
];

foreach ($allProducts as $product) {
    $state = (string)($product['estado'] ?? '');
    if ($state === 'pendente') {
        $productStats['pendentes']++;
    } elseif ($state === 'aprovado') {
        $productStats['aprovados']++;
    } elseif ($state === 'rejeitado') {
        $productStats['rejeitados']++;
    } elseif ($state === 'inativo') {
        $productStats['inativos']++;
    }
}

include 'admin_header.php';
?>

<div class="admin-top">
  <div>
    <h2 data-admin-t="products_title">Produtos</h2>
  </div>
</div>

<?php if ($feedback): ?>
  <div class="alert alert-ok"><?= h($feedback) ?></div>
<?php endif; ?>

<div class="stats-grid">
  <div class="stat"><div class="stat-val"><?= (int)$productStats['pendentes'] ?></div><div class="stat-lbl">Pendentes</div></div>
  <div class="stat"><div class="stat-val"><?= (int)$productStats['aprovados'] ?></div><div class="stat-lbl">Aprovados</div></div>
  <div class="stat"><div class="stat-val"><?= (int)$productStats['rejeitados'] ?></div><div class="stat-lbl">Rejeitados</div></div>
  <div class="stat"><div class="stat-val"><?= (int)$productStats['inativos'] ?></div><div class="stat-lbl">Inativos</div></div>
</div>

<section class="acard-box">
  <div class="acard-box-head">
    <h4 data-admin-t="products_pending">Produtos pendentes</h4>
    <span class="badge badge-red"><?= count($pending) ?></span>
  </div>

  <?php if (!$pending): ?>
    <p data-admin-t="products_empty_pending">Sem produtos pendentes neste momento.</p>
  <?php else: ?>
    <div class="admin-card-list">
      <?php foreach ($pending as $product): ?>
        <article class="admin-review-card">
          <div class="admin-review-main">
            <div class="admin-review-meta">
              <span class="badge badge-light"><?= h($product['nomeCategoria']) ?></span>
              <strong><?= h($product['nomeProduto']) ?></strong>
              <p>Artista: <?= h($product['artista']) ?></p>
              <p>Preco: <?= number_format((float)$product['precoAtual'], 2, ',', '.') ?> EUR</p>
              <p>IVA: <?= number_format((float)$product['iva_percentual'], 2, ',', '.') ?>%</p>
              <p>Comissao: <?= number_format((float)$product['comissao_percentual'], 2, ',', '.') ?>%</p>
              <p>Stock total: <?= (int)$product['stock_total'] ?></p>
              <?php if (!empty($product['descricaoProduto'])): ?>
                <p><?= h($product['descricaoProduto']) ?></p>
              <?php endif; ?>
            </div>
            <?php if (!empty($product['imagem'])): ?>
              <img src="../assets/img/<?= h($product['imagem']) ?>" alt="" class="admin-review-image">
            <?php endif; ?>
          </div>

          <form method="post" class="admin-review-actions">
            <input type="hidden" name="product_id" value="<?= (int)$product['idProduto'] ?>">
            <textarea name="reason" class="finput" placeholder="Motivo de rejeicao (opcional para aprovar, recomendado para rejeitar)." data-admin-tp="products_reason_placeholder"></textarea>
            <div class="admin-action-buttons">
              <button type="submit" name="action" value="aprovar" class="btn btn-dark btn-sm" data-admin-t="btn_approve">Aprovar</button>
              <button type="submit" name="action" value="rejeitar" class="btn btn-danger btn-sm" data-admin-t="btn_reject">Rejeitar</button>
            </div>
          </form>
        </article>
      <?php endforeach; ?>
    </div>
  <?php endif; ?>
</section>

<section class="acard-box">
  <div class="acard-box-head">
    <h4 data-admin-t="products_all">Todos os produtos</h4>
  </div>

  <div class="tbl-wrap">
    <table>
      <thead>
        <tr>
          <th>ID</th>
          <th>Produto</th>
          <th>Artista</th>
          <th>Categoria</th>
          <th>Preco</th>
          <th>Estado</th>
          <th>Acao</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($allProducts as $product): ?>
          <tr>
            <td>#<?= (int)$product['idProduto'] ?></td>
            <td>
              <strong><?= h($product['nomeProduto']) ?></strong>
              <?php if (!empty($product['motivo_rejeicao'])): ?>
                <br><span class="color-text3"><?= h($product['motivo_rejeicao']) ?></span>
              <?php endif; ?>
            </td>
            <td><?= h($product['artista']) ?></td>
            <td><?= h($product['nomeCategoria']) ?></td>
            <td><?= number_format((float)$product['precoAtual'], 2, ',', '.') ?> EUR</td>
            <td><span class="badge <?= h(state_badge_class($product['estado'])) ?>"><?= h(order_status_label($product['estado'])) ?></span></td>
            <td>
              <form method="post">
                <input type="hidden" name="product_id" value="<?= (int)$product['idProduto'] ?>">
                <?php if ($product['estado'] === 'aprovado' && (int)$product['ativo'] === 1): ?>
                  <button type="submit" name="action" value="inativar" class="btn btn-ghost btn-sm">Inativar</button>
                <?php elseif ($product['estado'] !== 'pendente'): ?>
                  <button type="submit" name="action" value="reativar" class="btn btn-ghost btn-sm">Reativar</button>
                <?php else: ?>
                  <span class="color-text3">Em revisao</span>
                <?php endif; ?>
              </form>
            </td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</section>

<?php include 'admin_footer.php'; ?>
