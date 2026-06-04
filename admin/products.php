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

    if ($feedback === '' && $productId > 0 && $action === 'guardar') {
        $name = trim((string)($_POST['nomeProduto'] ?? ''));
        $categoryId = (int)($_POST['idCategoria'] ?? 0);
        $price = max(0.01, (float)($_POST['precoAtual'] ?? 0));
        $stock = max(0, (int)($_POST['stock_total'] ?? 0));
        $state = (string)($_POST['estado'] ?? 'pendente');
        $allowedProductStates = ['pendente', 'aprovado', 'rejeitado', 'inativo'];
        if ($name === '' || $categoryId <= 0 || !in_array($state, $allowedProductStates, true)) {
            $feedback = tr('error.api_invalid_request');
        } else {
            $nameSafe = db_escape($conn, $name);
            $stateSafe = db_escape($conn, $state);
            $active = $state === 'aprovado' ? 1 : 0;
            $currentImages = product_images($conn, $productId);
            $images = [];
            $requestedExistingImages = $_POST['existing_images'] ?? [];
            if (is_array($requestedExistingImages)) {
                foreach ($requestedExistingImages as $requestedImage) {
                    $cleanImage = clean_product_image_name((string)$requestedImage);
                    if ($cleanImage !== '' && in_array($cleanImage, $currentImages, true)) {
                        $images[] = $cleanImage;
                    }
                }
            } else {
                $images = $currentImages;
            }
            $imageFiles = $_FILES['imagens'] ?? null;
            if ($imageFiles && is_array($imageFiles['name'] ?? null)) {
                foreach ($imageFiles['name'] as $index => $sourceName) {
                    if ($sourceName === '') continue;
                    $file = [
                        'name' => $sourceName,
                        'type' => $imageFiles['type'][$index] ?? '',
                        'tmp_name' => $imageFiles['tmp_name'][$index] ?? '',
                        'error' => $imageFiles['error'][$index] ?? UPLOAD_ERR_NO_FILE,
                        'size' => $imageFiles['size'][$index] ?? 0,
                    ];
                    $imageError = validate_uploaded_image($file);
                    if ($imageError) {
                        $feedback = $imageError;
                        break;
                    }
                    [$savedImage, $saveErr] = save_uploaded_file($file, 'img', 'product_admin_' . $productId, ['jpg', 'jpeg', 'png', 'webp'], GREENERRY_MAX_IMAGE_BYTES);
                    if ($saveErr) {
                        $feedback = $saveErr;
                        break;
                    }
                    $images[] = $savedImage;
                }
            }
            if ($feedback === '' && !$images) {
                $feedback = current_lang() === 'en' ? 'Add at least one product image.' : 'Adiciona pelo menos uma imagem do produto.';
            } elseif ($feedback === '' && count($images) > GREENERRY_MAX_PRODUCT_IMAGES) {
                $feedback = current_lang() === 'en'
                    ? 'A product can have at most ' . GREENERRY_MAX_PRODUCT_IMAGES . ' images.'
                    : 'Um produto pode ter no maximo ' . GREENERRY_MAX_PRODUCT_IMAGES . ' imagens.';
            }
        }

        if ($feedback === '') {
            mysqli_query(
                $conn,
                "UPDATE produto
                 SET nomeProduto = '{$nameSafe}',
                     idCategoria = {$categoryId},
                     precoAtual = {$price},
                     stock_total = {$stock},
                     estado = '{$stateSafe}',
                     ativo = {$active}
                 WHERE idProduto = {$productId}"
            );
            save_product_images($conn, $productId, $images);
            delete_orphan_asset_files($conn, 'img', array_diff($currentImages, $images));
            cleanup_unused_uploaded_assets($conn);
            $feedback = tr('success.product_updated');
        }
    }

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

$pendingPerPage = 6;
$pendingPage = max(1, (int)($_GET['pending_page'] ?? 1));
$totalPending = (int)(db_one($conn, "SELECT COUNT(*) AS total FROM produto WHERE estado = 'pendente'")['total'] ?? 0);
$pendingTotalPages = max(1, (int)ceil($totalPending / $pendingPerPage));
$pendingPage = min($pendingPage, $pendingTotalPages);
$pendingOffset = ($pendingPage - 1) * $pendingPerPage;

$pending = db_all(
    $conn,
    "SELECT p.*, c.nome AS artista, cat.nomeCategoria
     FROM produto p
     JOIN cliente c ON c.idCliente = p.idCliente
     JOIN categoria cat ON cat.idCategoria = p.idCategoria
     WHERE p.estado = 'pendente'
     ORDER BY p.criado_em DESC
     LIMIT {$pendingPerPage} OFFSET {$pendingOffset}"
);

$categories = db_all($conn, "SELECT idCategoria, nomeCategoria FROM categoria ORDER BY nomeCategoria ASC");

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
    <span class="badge badge-red"><?= (int)$totalPending ?></span>
  </div>

  <?php if (!$pending): ?>
    <p data-admin-t="products_empty_pending">Sem produtos pendentes neste momento.</p>
  <?php else: ?>
    <div class="admin-card-list">
      <?php foreach ($pending as $product): ?>
        <?php $productImages = product_images($conn, (int)$product['idProduto']); ?>
            <?php $productImage = $productImages[0] ?? ''; ?>
        <article class="admin-review-card" data-review-type="product" data-review-id="<?= (int)$product['idProduto'] ?>" data-admin-state="<?= h($product['estado']) ?>">
          <div class="admin-review-main">
            <div class="admin-review-meta">
              <span class="badge badge-light" style="margin-bottom: 12px;"><?= h($product['nomeCategoria']) ?></span>
              <strong style="display: block; font-size: 1.15rem; margin-bottom: 8px;"><?= h($product['nomeProduto']) ?></strong>
              
              <div class="admin-review-meta-grid">
                <div class="admin-review-meta-item">
                  <span data-admin-t="label_artist">Artista</span>
                  <strong><?= h($product['artista']) ?></strong>
                </div>
                <div class="admin-review-meta-item">
                  <span data-admin-t="label_price">Preço</span>
                  <strong><?= number_format((float)$product['precoAtual'], 2, ',', '.') ?> EUR</strong>
                </div>
                <div class="admin-review-meta-item">
                  <span data-admin-t="label_vat">IVA</span>
                  <strong><?= number_format((float)$product['iva_percentual'], 2, ',', '.') ?>%</strong>
                </div>
                <div class="admin-review-meta-item">
                  <span data-admin-t="label_commission">Comissão</span>
                  <strong><?= number_format((float)$product['comissao_percentual'], 2, ',', '.') ?>%</strong>
                </div>
                <div class="admin-review-meta-item">
                  <span data-admin-t="label_total_stock">Stock total</span>
                  <strong><?= (int)$product['stock_total'] ?></strong>
                </div>
              </div>

              <?php if (!empty($product['descricaoProduto'])): ?>
                <p style="margin-top: 12px; font-size: 0.88rem; line-height: 1.5; color: var(--admin-soft);"><?= h($product['descricaoProduto']) ?></p>
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
    
    <?php if ($pendingTotalPages > 1): ?>
      <?php $otherPageParam = isset($_GET['page']) ? '&page=' . (int)$_GET['page'] : ''; ?>
      <nav class="pager" aria-label="Pending Pagination">
        <?= $pendingPage > 1 ? '<a class="btn btn-ghost btn-sm" href="products.php?pending_page=' . (int)($pendingPage - 1) . $otherPageParam . '#products-search" data-admin-t="pagination_previous">Anterior</a>' : '<span class="btn btn-ghost btn-sm is-disabled" data-admin-t="pagination_previous">Anterior</span>' ?>
        <span class="pager-status" data-admin-page-status data-page-current="<?= (int)$pendingPage ?>" data-page-total="<?= (int)$pendingTotalPages ?>">Pagina <?= (int)$pendingPage ?> de <?= (int)$pendingTotalPages ?></span>
        <?= $pendingPage < $pendingTotalPages ? '<a class="btn btn-ghost btn-sm" href="products.php?pending_page=' . (int)($pendingPage + 1) . $otherPageParam . '#products-search" data-admin-t="pagination_next">Seguinte</a>' : '<span class="btn btn-ghost btn-sm is-disabled" data-admin-t="pagination_next">Seguinte</span>' ?>
      </nav>
    <?php endif; ?>
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
            <th data-admin-t="label_product">Produto</th>
            <th data-admin-t="label_artist">Artista</th>
            <th data-admin-t="label_category">Categoria</th>
            <th data-admin-t="label_price">Preco</th>
            <th data-admin-t="categories_state">Estado</th>
            <th data-admin-t="orders_action">Acao</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($allProducts as $product): ?>
            <?php $productImages = product_images($conn, (int)$product['idProduto']); ?>
            <?php $productImage = $productImages[0] ?? ''; ?>
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
                <div class="admin-row-actions">
                <details class="admin-inline-editor">
                  <summary class="btn btn-ghost btn-sm" data-admin-t="btn_edit">Editar</summary>
                  <form method="post" class="admin-inline-edit-form" enctype="multipart/form-data">
                    <?= csrf_input() ?>
                    <input type="hidden" name="product_id" value="<?= (int)$product['idProduto'] ?>">
                    <label><span data-admin-t="label_product">Produto</span><input name="nomeProduto" class="finput" value="<?= h($product['nomeProduto']) ?>" required></label>
                    <label><span data-admin-t="label_category">Categoria</span><select name="idCategoria" class="finput">
                      <?php foreach ($categories as $category): ?>
                        <option value="<?= (int)$category['idCategoria'] ?>" <?= (int)$category['idCategoria'] === (int)$product['idCategoria'] ? 'selected' : '' ?>><?= h($category['nomeCategoria']) ?></option>
                      <?php endforeach; ?>
                    </select></label>
                    <div class="admin-inline-edit-pair">
                      <label><span data-admin-t="label_price">Preco</span><input type="number" step="0.01" min="0.01" name="precoAtual" class="finput" value="<?= h((string)$product['precoAtual']) ?>" required></label>
                      <label><span data-admin-t="label_total_stock">Stock total</span><input type="number" min="0" name="stock_total" class="finput" value="<?= (int)$product['stock_total'] ?>"></label>
                    </div>
                    <label><span data-admin-t="categories_state">Estado</span><select name="estado" class="finput">
                      <?php foreach (['pendente', 'aprovado', 'rejeitado', 'inativo'] as $state): ?>
                        <option value="<?= h($state) ?>" <?= $state === (string)$product['estado'] ? 'selected' : '' ?>><?= h(order_status_label($state)) ?></option>
                      <?php endforeach; ?>
                    </select></label>
                    <div>
                      <span class="admin-modal-label" data-admin-t="products_image">Imagem</span>
                      <div class="admin-inline-media-grid">
                        <?php foreach ($productImages as $image): ?>
                          <label class="admin-inline-media-item">
                            <img src="../assets/img/<?= h($image) ?>" alt="">
                            <span><input type="checkbox" name="existing_images[]" value="<?= h($image) ?>" checked> <span data-admin-t="btn_keep">Manter</span></span>
                          </label>
                        <?php endforeach; ?>
                      </div>
                    </div>
                    <label><span data-admin-t="btn_add_images">Adicionar imagens</span><input type="file" name="imagens[]" class="finput" accept=".jpg,.jpeg,.png,.webp" multiple></label>
                    <div class="admin-action-buttons">
                      <button type="submit" name="action" value="guardar" class="btn btn-dark btn-sm" data-admin-t="btn_save_changes">Guardar alteracoes</button>
                    </div>
                  </form>
                </details>
                <form method="post">
                  <?= csrf_input() ?>
                  <input type="hidden" name="product_id" value="<?= (int)$product['idProduto'] ?>">
                  <?php if ($product['estado'] === 'aprovado' && (int)$product['ativo'] === 1): ?>
                    <button type="submit" name="action" value="inativar" class="btn btn-ghost btn-sm" data-confirm="Inativar este produto?" data-admin-t="btn_deactivate">Inativar</button>
                  <?php elseif ($product['estado'] !== 'pendente'): ?>
                    <button type="submit" name="action" value="reativar" class="btn btn-ghost btn-sm" data-admin-t="btn_reactivate">Reativar</button>
                  <?php else: ?>
                    <span class="color-text3" data-admin-t="state_in_review">Em revisao</span>
                  <?php endif; ?>
                </form>
                </div>
              </td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
    <?php if ($adminProductsTotalPages > 1): ?>
      <?php $otherPendingPageParam = isset($_GET['pending_page']) ? '&pending_page=' . (int)$_GET['pending_page'] : ''; ?>
      <nav class="pager" aria-label="Pagination">
        <?= $adminProductsPage > 1 ? '<a class="btn btn-ghost btn-sm" href="products.php?page=' . (int)($adminProductsPage - 1) . $otherPendingPageParam . '" data-admin-t="pagination_previous">Anterior</a>' : '<span class="btn btn-ghost btn-sm is-disabled" data-admin-t="pagination_previous">Anterior</span>' ?>
        <span class="pager-status" data-admin-page-status data-page-current="<?= (int)$adminProductsPage ?>" data-page-total="<?= (int)$adminProductsTotalPages ?>">Pagina <?= (int)$adminProductsPage ?> de <?= (int)$adminProductsTotalPages ?></span>
        <?= $adminProductsPage < $adminProductsTotalPages ? '<a class="btn btn-ghost btn-sm" href="products.php?page=' . (int)($adminProductsPage + 1) . $otherPendingPageParam . '" data-admin-t="pagination_next">Seguinte</a>' : '<span class="btn btn-ghost btn-sm is-disabled" data-admin-t="pagination_next">Seguinte</span>' ?>
      </nav>
    <?php endif; ?>
  <?php endif; ?>
</section>
</div>

<?php include 'admin_footer.php'; ?>
