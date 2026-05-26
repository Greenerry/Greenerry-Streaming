<?php
require_once '../includes/config.php';
require_admin_permission('products');

$adminId = current_admin_id();
$feedback = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $feedback = verify_csrf_request() ?? '';
    $productId = (int)($_POST['product_id'] ?? 0);
    $action = $_POST['action'] ?? '';
    $reason = trim($_POST['reason'] ?? '');
    $reasonSafe = db_escape($conn, $reason);

    if ($feedback === '' && $productId > 0 && in_array($action, ['aprovar', 'rejeitar', 'inativar', 'reativar'], true)) {
        if ($action === 'aprovar') {
            mysqli_query($conn, "UPDATE produto SET estado = 'aprovado', motivo_rejeicao = NULL, idAdminAprovacao = {$adminId}, aprovado_em = NOW(), ativo = 1 WHERE idProduto = {$productId}");
            send_product_review_email($conn, $productId, $action);
            notify_product_review($conn, $productId, $action);
            $feedback = tr('success.product_approved');
        } elseif ($action === 'rejeitar') {
            mysqli_query($conn, "UPDATE produto SET estado = 'rejeitado', motivo_rejeicao = '{$reasonSafe}', idAdminAprovacao = {$adminId}, aprovado_em = NOW(), ativo = 0 WHERE idProduto = {$productId}");
            send_product_review_email($conn, $productId, $action, $reason);
            notify_product_review($conn, $productId, $action, $reason);
            $feedback = tr('success.product_rejected');
        } elseif ($action === 'inativar') {
            mysqli_query($conn, "UPDATE produto SET estado = 'inativo', ativo = 0, bloqueado_admin = 1 WHERE idProduto = {$productId}");
            notify_product_review($conn, $productId, $action);
            $feedback = tr('success.product_deactivated');
        } elseif ($action === 'reativar') {
            mysqli_query($conn, "UPDATE produto SET estado = 'aprovado', ativo = 1, bloqueado_admin = 0 WHERE idProduto = {$productId}");
            notify_product_review($conn, $productId, $action);
            $feedback = tr('success.product_reactivated');
        }
    }
}

$adminProductsPerPage = 50;
$adminProductsPage = max(1, (int)($_GET['page'] ?? 1));
$totalAdminProducts = (int)(db_one($conn, "SELECT COUNT(*) AS total FROM produto")['total'] ?? 0);
$adminProductsTotalPages = max(1, (int)ceil($totalAdminProducts / $adminProductsPerPage));
$adminProductsPage = min($adminProductsPage, $adminProductsTotalPages);
$adminProductsOffset = ($adminProductsPage - 1) * $adminProductsPerPage;

$pending = db_all(
    $conn,
    "SELECT p.*, c.nome AS artista, cat.nomeCategoria
     FROM produto p
     JOIN cliente c ON c.idCliente = p.idCliente
     JOIN categoria cat ON cat.idCategoria = p.idCategoria
     WHERE p.estado = 'pendente'
     ORDER BY p.criado_em DESC
     LIMIT 30"
);

$allProducts = db_all(
    $conn,
    "SELECT p.*, c.nome AS artista, cat.nomeCategoria
     FROM produto p
     JOIN cliente c ON c.idCliente = p.idCliente
     JOIN categoria cat ON cat.idCategoria = p.idCategoria
     ORDER BY p.criado_em DESC
     LIMIT {$adminProductsPerPage} OFFSET {$adminProductsOffset}"
);

$productStats = [
    'pendentes' => 0,
    'aprovados' => 0,
    'rejeitados' => 0,
    'inativos' => 0,
];

foreach (db_all($conn, "SELECT estado, COUNT(*) AS total FROM produto GROUP BY estado") as $row) {
    $state = (string)($row['estado'] ?? '');
    if (isset($productStats[$state . 's'])) {
        $productStats[$state . 's'] = (int)$row['total'];
    } elseif ($state === 'pendente') {
        $productStats['pendentes'] = (int)$row['total'];
    } elseif ($state === 'aprovado') {
        $productStats['aprovados'] = (int)$row['total'];
    } elseif ($state === 'rejeitado') {
        $productStats['rejeitados'] = (int)$row['total'];
    } elseif ($state === 'inativo') {
        $productStats['inativos'] = (int)$row['total'];
    }
}

include 'admin_header.php';
?>

<div class="admin-top">
  <div>
    <span class="admin-page-kicker" data-admin-t="products_kicker">Catalog review</span>
    <h2 data-admin-t="products_title">Produtos</h2>
    <p data-admin-t="products_intro">Aprova, bloqueia e acompanha todo o merch da Greenerry.</p>
  </div>
  <div class="stats-grid admin-top-stats">
    <button type="button" class="stat stat-button" data-admin-stat-filter="products-search" data-filter-value="pendente"><div class="stat-val"><?= (int)$productStats['pendentes'] ?></div><div class="stat-lbl" data-admin-t="state_pending">Pendentes</div></button>
    <button type="button" class="stat stat-button" data-admin-stat-filter="products-search" data-filter-value="aprovado"><div class="stat-val"><?= (int)$productStats['aprovados'] ?></div><div class="stat-lbl" data-admin-t="state_approved">Aprovados</div></button>
    <button type="button" class="stat stat-button" data-admin-stat-filter="products-search" data-filter-value="rejeitado"><div class="stat-val"><?= (int)$productStats['rejeitados'] ?></div><div class="stat-lbl" data-admin-t="state_rejected">Rejeitados</div></button>
    <button type="button" class="stat stat-button" data-admin-stat-filter="products-search" data-filter-value="inativo"><div class="stat-val"><?= (int)$productStats['inativos'] ?></div><div class="stat-lbl" data-admin-t="state_inactive">Inativos</div></button>
  </div>
</div>

<?php if ($feedback): ?>
  <div class="alert alert-ok"><?= h($feedback) ?></div>
<?php endif; ?>

<div id="products-search" data-admin-search-scope>
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
        <?php $productImage = product_main_image($conn, (int)$product['idProduto']); ?>
        <article class="admin-review-card" data-review-type="product" data-review-id="<?= (int)$product['idProduto'] ?>" data-admin-state="<?= h($product['estado']) ?>">
          <div class="admin-review-main">
            <div class="admin-review-meta">
              <span class="badge badge-light"><?= h($product['nomeCategoria']) ?></span>
              <strong><?= h($product['nomeProduto']) ?></strong>
              <p><span data-admin-t="label_artist">Artista</span>: <?= h($product['artista']) ?></p>
              <p><span data-admin-t="label_price">Preco</span>: <?= number_format((float)$product['precoAtual'], 2, ',', '.') ?> EUR</p>
              <p>IVA: <?= number_format((float)$product['iva_percentual'], 2, ',', '.') ?>%</p>
              <p><span data-admin-t="label_commission">Comissão</span>: <?= number_format((float)$product['comissao_percentual'], 2, ',', '.') ?>%</p>
              <p><span data-admin-t="label_total_stock">Stock total</span>: <?= (int)$product['stock_total'] ?></p>
              <?php if (!empty($product['descricaoProduto'])): ?>
                <p><?= h($product['descricaoProduto']) ?></p>
              <?php endif; ?>
            </div>
            <?php if ($productImage): ?>
              <img src="../assets/img/<?= h($productImage) ?>" alt="" class="admin-review-image">
            <?php endif; ?>
          </div>

          <form method="post" class="admin-review-actions">
            <?= csrf_input() ?>
            <input type="hidden" name="product_id" value="<?= (int)$product['idProduto'] ?>">
            <textarea name="reason" class="finput" placeholder="Motivo de rejeição (opcional para aprovar, recomendado para rejeitar)." data-admin-tp="products_reason_placeholder"></textarea>
            <div class="admin-action-buttons">
              <button type="submit" name="action" value="aprovar" class="btn btn-dark btn-sm" data-admin-t="btn_approve">Aprovar</button>
              <button type="submit" name="action" value="rejeitar" class="btn btn-danger btn-sm" data-confirm="Rejeitar este produto?" data-admin-t="btn_reject">Rejeitar</button>
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
    <div class="admin-card-head-tools">
      <label class="sbar admin-section-search">
        <svg width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><circle cx="11" cy="11" r="7"/><path d="m21 21-4.3-4.3"/></svg>
        <input type="search" data-admin-search="products-search" placeholder="Pesquisar..." data-admin-tp="admin_search_placeholder">
      </label>
      <span class="badge badge-light"><?= (int)$totalAdminProducts ?></span>
    </div>
  </div>

  <?php if (!$allProducts): ?>
    <p data-admin-t="products_empty_all">Ainda não existem produtos registados.</p>
  <?php else: ?>
    <div class="tbl-wrap">
      <table>
        <thead>
          <tr>
            <th>ID</th>
            <th data-admin-t="products_image">Imagem</th>
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
            <?php $productImage = product_main_image($conn, (int)$product['idProduto']); ?>
            <tr data-review-type="product" data-review-id="<?= (int)$product['idProduto'] ?>" data-admin-state="<?= h($product['estado']) ?>">
              <td>#<?= (int)$product['idProduto'] ?></td>
              <td>
                <div class="admin-table-thumb">
                  <?php if ($productImage): ?>
                    <img src="../assets/img/<?= h($productImage) ?>" alt="">
                  <?php else: ?>
                    <span data-admin-t="products_no_image">Sem imagem</span>
                  <?php endif; ?>
                </div>
              </td>
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
                  <?= csrf_input() ?>
                  <input type="hidden" name="product_id" value="<?= (int)$product['idProduto'] ?>">
                  <?php if ($product['estado'] === 'aprovado' && (int)$product['ativo'] === 1): ?>
                    <button type="submit" name="action" value="inativar" class="btn btn-ghost btn-sm" data-confirm="Inativar este produto?" data-admin-t="btn_deactivate">Inativar</button>
                  <?php elseif ($product['estado'] !== 'pendente'): ?>
                    <button type="submit" name="action" value="reativar" class="btn btn-ghost btn-sm" data-admin-t="btn_reactivate">Reativar</button>
                  <?php else: ?>
                    <span class="color-text3" data-admin-t="state_in_review">Em revisão</span>
                  <?php endif; ?>
                </form>
              </td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
    <?php if ($adminProductsTotalPages > 1): ?>
      <nav class="pager" aria-label="Pagination">
        <?= $adminProductsPage > 1 ? '<a class="btn btn-ghost btn-sm" href="products.php?page=' . (int)($adminProductsPage - 1) . '" data-admin-t="pagination_previous">Anterior</a>' : '<span class="btn btn-ghost btn-sm is-disabled" data-admin-t="pagination_previous">Anterior</span>' ?>
        <span class="pager-status">Página <?= (int)$adminProductsPage ?> de <?= (int)$adminProductsTotalPages ?></span>
        <?= $adminProductsPage < $adminProductsTotalPages ? '<a class="btn btn-ghost btn-sm" href="products.php?page=' . (int)($adminProductsPage + 1) . '" data-admin-t="pagination_next">Seguinte</a>' : '<span class="btn btn-ghost btn-sm is-disabled" data-admin-t="pagination_next">Seguinte</span>' ?>
      </nav>
    <?php endif; ?>
  <?php endif; ?>
</section>
</div>

<?php include 'admin_footer.php'; ?>
