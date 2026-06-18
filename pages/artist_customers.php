<?php
// Page purpose: Shows artist customer information connected to purchases.
// Keep this page simple: prepare data first, then render the view.
require_once '../includes/config.php';
require_user_login();

$uid = current_user_id();
$perPage = 10;
$pageNumber = max(1, (int)($_GET['page'] ?? 1));
$totalCustomers = (int)(db_one_prepared(
    $conn,
    "SELECT COUNT(DISTINCT e.idCliente) AS total
     FROM encomenda_item ei
     JOIN encomenda e ON e.idEncomenda = ei.idEncomenda
     WHERE ei.idArtista = ?",
    'i',
    [$uid]
)['total'] ?? 0);
$totalPages = max(1, (int)ceil($totalCustomers / $perPage));
$pageNumber = min($pageNumber, $totalPages);
$offset = ($pageNumber - 1) * $perPage;
$customers = db_all_prepared(
    $conn,
    "SELECT
        c.idCliente,
        c.nome,
        c.email,
        c.foto,
        COUNT(DISTINCT e.idEncomenda) AS orders_count,
        COALESCE(SUM(CASE WHEN ei.estado_item != 'cancelado' THEN ei.valor_artista ELSE 0 END), 0) AS artist_value,
        MAX(e.criado_em) AS last_order_at,
        SUM(CASE WHEN em.remetente = 'comprador' THEN 1 ELSE 0 END) AS buyer_messages
     FROM encomenda_item ei
     JOIN encomenda e ON e.idEncomenda = ei.idEncomenda
     JOIN cliente c ON c.idCliente = e.idCliente
     LEFT JOIN encomenda_mensagem em ON em.idEncomenda = ei.idEncomenda
       AND em.idProduto = ei.idProduto
       AND em.idComprador = c.idCliente
       AND em.idArtista = ?
     WHERE ei.idArtista = ?
     GROUP BY c.idCliente, c.nome, c.email, c.foto
     ORDER BY last_order_at DESC
     LIMIT {$perPage} OFFSET {$offset}",
    'ii',
    [$uid, $uid]
);

include '../includes/header.php';
?>

<section class="artist-dashboard-v4 artist-animate">
  <header class="artist-dash-top">
    <div>
      <h2 data-t="nav_artist_customers">Customers</h2>
    </div>
  </header>

  <article class="artist-dash-card artist-admin-table-card">
    <div class="artist-dash-card-head">
      <div><h3 data-t="artist_customers_buyer_list">Buyer list</h3></div>
      <div class="admin-card-head-tools">
        <label class="sbar admin-section-search">
          <svg width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><circle cx="11" cy="11" r="7"/><path d="m21 21-4.3-4.3"/></svg>
          <input type="search" data-artist-search="artist-customers" data-tp="artist_customers_search" placeholder="Search customers">
        </label>
        <span class="badge badge-light"><?= $totalCustomers ?> <span data-t="nav_artist_customers">customers</span></span>
      </div>
    </div>
    <?php if (!$customers): ?>
      <p class="artist-dash-empty" data-t="artist_customers_empty">No customers yet.</p>
    <?php else: ?>
      <div class="tbl-wrap" data-artist-search-scope="artist-customers">
        <table>
          <thead>
            <tr>
              <th>ID</th>
              <th data-t="artist_table_customer">Customer</th>
              <th>Email</th>
              <th data-t="artist_table_orders">Orders</th>
              <th data-t="artist_table_messages">Messages</th>
              <th data-t="artist_table_value">Value</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($customers as $customer): ?>
              <tr data-artist-search-row>
                <td>#<?= (int)$customer['idCliente'] ?></td>
                <td>
                  <span class="artist-customer-cell">
                    <span class="artist-customer-avatar">
                      <?php if (!empty($customer['foto'])): ?>
                        <img src="<?= h(asset_url('img', $customer['foto'])) ?>" alt="">
                      <?php else: ?>
                        <?= h(mb_strtoupper(mb_substr((string)$customer['nome'], 0, 1))) ?>
                      <?php endif; ?>
                    </span>
                    <strong><?= h($customer['nome']) ?></strong>
                  </span>
                </td>
                <td><?= h($customer['email']) ?></td>
                <td><?= (int)$customer['orders_count'] ?></td>
                <td><?= (int)$customer['buyer_messages'] ?></td>
                <td><strong><?= h(format_eur((float)$customer['artist_value'])) ?></strong></td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
      <?php if ($totalPages > 1): ?>
        <nav class="pager" aria-label="Pagination">
          <?php if ($pageNumber > 1): ?><a class="btn btn-ghost btn-sm" href="artist_customers.php?page=<?= $pageNumber - 1 ?>" data-t="pagination_previous">Anterior</a><?php else: ?><span class="btn btn-ghost btn-sm is-disabled" data-t="pagination_previous">Anterior</span><?php endif; ?>
          <span class="pager-status"><span data-t="pagination_page">Página</span> <?= $pageNumber ?> <span data-t="pagination_of">de</span> <?= $totalPages ?></span>
          <?php if ($pageNumber < $totalPages): ?><a class="btn btn-ghost btn-sm" href="artist_customers.php?page=<?= $pageNumber + 1 ?>" data-t="pagination_next">Seguinte</a><?php else: ?><span class="btn btn-ghost btn-sm is-disabled" data-t="pagination_next">Seguinte</span><?php endif; ?>
        </nav>
      <?php endif; ?>
    <?php endif; ?>
  </article>
</section>

<?php include '../includes/footer.php'; ?>
