<?php
// Page purpose: Lets artists manage their merchandising products.
// Keep this page simple: prepare data first, then render the view.
require_once '../includes/config.php';
require_user_login();

$uid = current_user_id();
$products = db_all_prepared(
    $conn,
    "SELECT
        p.*,
        c.nomeCategoria,
        COUNT(ei.idEncomendaItem) AS sold_lines,
        COALESCE(SUM(CASE WHEN ei.estado_item != 'cancelado' THEN ei.quantidade ELSE 0 END), 0) AS units_sold,
        COALESCE(SUM(CASE WHEN ei.estado_item = 'entregue' THEN ei.valor_artista ELSE 0 END), 0) AS delivered_revenue
     FROM produto p
     LEFT JOIN categoria c ON c.idCategoria = p.idCategoria
     LEFT JOIN encomenda_item ei ON ei.idProduto = p.idProduto
     WHERE p.idCliente = ?
     GROUP BY p.idProduto
     ORDER BY p.criado_em DESC",
    'i',
    [$uid]
);

include '../includes/header.php';
?>

<section class="artist-dashboard-v4 artist-animate">
  <header class="artist-dash-top">
    <div>
      <h2 data-t="nav_artist_products">Products</h2>
    </div>
  </header>

  <article class="artist-dash-card artist-admin-table-card">
    <div class="artist-dash-card-head">
      <div><h3 data-t="artist_products_catalog">Product catalog</h3></div>
      <div class="admin-card-head-tools">
        <label class="sbar admin-section-search">
          <svg width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><circle cx="11" cy="11" r="7"/><path d="m21 21-4.3-4.3"/></svg>
          <input type="search" data-artist-search="artist-products" data-tp="artist_products_search" placeholder="Search products">
        </label>
        <span class="badge badge-light"><?= count($products) ?> <span data-t="artist_total">total</span></span>
      </div>
    </div>
    <?php if (!$products): ?>
      <p class="artist-dash-empty" data-t="artist_products_empty_submitted">No products submitted yet.</p>
    <?php else: ?>
      <div class="tbl-wrap" data-artist-search-scope="artist-products">
        <table>
          <thead>
            <tr>
              <th>ID</th>
              <th data-t="artist_table_image">Image</th>
              <th data-t="artist_table_product">Product</th>
              <th data-t="artist_table_category">Category</th>
              <th data-t="artist_table_stock">Stock</th>
              <th data-t="artist_table_sold">Sold</th>
              <th data-t="artist_table_status">Status</th>
              <th data-t="artist_table_action">Action</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($products as $product): ?>
              <?php
              $image = product_main_image($conn, (int)$product['idProduto']);
              $canToggle = in_array((string)$product['estado'], ['aprovado', 'inativo'], true) && (int)($product['bloqueado_admin'] ?? 0) !== 1;
              $isActive = (int)($product['ativo'] ?? 0) === 1;
              ?>
              <tr data-artist-row data-artist-search-row>
                <td>#<?= (int)$product['idProduto'] ?></td>
                <td>
                  <div class="admin-table-thumb">
                    <?php if ($image): ?>
                      <img src="<?= h(asset_url('img', $image)) ?>" alt="">
                    <?php else: ?>
                      <span data-t="artist_no_image">No image</span>
                    <?php endif; ?>
                  </div>
                </td>
                <td><strong><?= h($product['nomeProduto']) ?></strong></td>
                <td>
                  <?php if (!empty($product['nomeCategoria'])): ?>
                    <span data-product-category="<?= h($product['nomeCategoria']) ?>"><?= h(category_label((string)$product['nomeCategoria'])) ?></span>
                  <?php else: ?>
                    <span data-t="profile_no_category"><?= h(current_lang() === 'en' ? 'No category' : 'Sem categoria') ?></span>
                  <?php endif; ?>
                </td>
                <td><?= (int)$product['stock_total'] ?></td>
                <td><?= (int)$product['units_sold'] ?></td>
                <td><span class="badge <?= h(state_badge_class($product['estado'])) ?>" data-state-label="<?= h($product['estado']) ?>"><?= h(order_status_label($product['estado'])) ?></span></td>
                <td>
                  <div class="artist-actions-cell">
                    <a class="btn btn-ghost btn-sm" href="upload_merch.php?edit=<?= (int)$product['idProduto'] ?>" data-t="artist_action_edit">Edit</a>
                    <button type="button" class="btn btn-ghost btn-sm js-toggle-item" data-type="merch" data-id="<?= (int)$product['idProduto'] ?>" <?= $canToggle ? '' : 'disabled' ?>>
                      <span data-t="<?= $isActive ? 'artist_action_deactivate' : 'artist_action_activate' ?>"><?= $isActive ? 'Deactivate' : 'Activate' ?></span>
                    </button>
                  </div>
                </td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    <?php endif; ?>
  </article>
</section>

<script>
function artistText(key, fallback) {
  const activeLang = typeof lang !== 'undefined' ? lang : (localStorage.getItem('g_lang') || 'pt');
  return (typeof T !== 'undefined' && T[activeLang] && T[activeLang][key]) || fallback;
}

document.querySelectorAll('.js-toggle-item').forEach((button) => {
  if (button.dataset.toggleReady === '1') return;
  button.dataset.toggleReady = '1';
  button.addEventListener('click', async () => {
    button.disabled = true;
    const body = new URLSearchParams({ type: button.dataset.type, id: button.dataset.id, csrf_token: window.CSRF_TOKEN || '' });
    try {
      const response = await fetch((window.SITE_BASE || '..') + '/api/toggle_item.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: body.toString()
      });
      const result = await response.json();
      if (!response.ok || !result.success) throw new Error(result.error || artistText('artist_update_failed', 'Update failed'));
      const enabled = Number(result.enabled) === 1;
      const label = button.querySelector('[data-t]');
      const labelKey = enabled ? 'artist_action_deactivate' : 'artist_action_activate';
      if (label) {
        label.dataset.t = labelKey;
        label.textContent = artistText(labelKey, enabled ? 'Deactivate' : 'Activate');
      }
      const stateLabel = button.closest('[data-artist-row]')?.querySelector('[data-state-label]');
      if (stateLabel) {
        stateLabel.dataset.stateLabel = enabled ? 'aprovado' : 'inativo';
        stateLabel.textContent = enabled ? artistText('status_approved', 'Approved') : artistText('status_inactive', 'Inactive');
      }
      button.disabled = false;
    } catch (error) {
      button.disabled = false;
      if (typeof toast === 'function') toast(error.message || artistText('artist_update_failed', 'Update failed'));
    }
  });
});
</script>

<?php include '../includes/footer.php'; ?>
