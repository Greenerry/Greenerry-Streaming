<?php
require_once '../includes/config.php';
require_user_login();

$uid = current_user_id();
$ok = '';
$err = '';
$requestedTab = (string)($_GET['tab'] ?? 'edit');
$activeTab = in_array($requestedTab, ['edit', 'orders', 'music', 'merch'], true) ? $requestedTab : 'edit';
$user = db_one($conn, "SELECT * FROM cliente WHERE idCliente = {$uid} LIMIT 1");

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_release'])) {
    $activeTab = 'music';
    $releaseId = (int)($_POST['release_id'] ?? 0);
    $err = verify_csrf_request() ?? '';

    if (!$err && $releaseId <= 0) {
        $err = tr('error.release_delete');
    }

    if (!$err) {
        $releaseToDelete = db_one($conn, "SELECT idRelease, capa FROM release_musical WHERE idRelease = {$releaseId} AND idCliente = {$uid} LIMIT 1");
        if (!$releaseToDelete) {
            $err = tr('error.release_delete');
        } else {
            // Delete DB rows first, then remove files only after the transaction succeeds.
            $tracksToDelete = db_all($conn, "SELECT idFaixa, ficheiro_audio FROM faixa WHERE idRelease = {$releaseId}");
            $coverToDelete = (string)($releaseToDelete['capa'] ?? '');
            $trackIdsToDelete = array_map(static fn($track) => (int)($track['idFaixa'] ?? 0), $tracksToDelete);
            $trackIdsSql = implode(',', array_filter($trackIdsToDelete));
            $audioFilesToDelete = array_values(array_unique(array_filter(array_map(
                static fn($track) => (string)($track['ficheiro_audio'] ?? ''),
                $tracksToDelete
            ))));

            mysqli_begin_transaction($conn);
            try {
                if ($trackIdsSql !== '' && !mysqli_query($conn, "DELETE FROM favorito_musica WHERE idFaixa IN ({$trackIdsSql})")) {
                    throw new RuntimeException('release favourites delete failed');
                }
                if (!mysqli_query($conn, "DELETE FROM faixa WHERE idRelease = {$releaseId}")) {
                    throw new RuntimeException('release tracks delete failed');
                }
                if (!mysqli_query($conn, "DELETE FROM release_musical WHERE idRelease = {$releaseId} AND idCliente = {$uid}")) {
                    throw new RuntimeException('release delete failed');
                }
                mysqli_commit($conn);

                delete_orphan_asset_file($conn, 'img', $coverToDelete);
                delete_orphan_asset_files($conn, 'audio', $audioFilesToDelete);
                cleanup_unused_uploaded_assets($conn);

                $ok = tr('success.release_deleted');
            } catch (Throwable $e) {
                mysqli_rollback($conn);
                $err = tr('error.release_delete');
            }
        }
    }
} elseif ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_profile'])) {
    // Profile media is optional; existing files are kept unless the user uploads replacements.
    $nome = trim($_POST['nome'] ?? '');
    $telefone = trim($_POST['telefone'] ?? '');
    $bio = trim($_POST['bio'] ?? '');
    $oldFoto = (string)($user['foto'] ?? '');
    $oldBanner = (string)($user['banner'] ?? '');
    $foto = $oldFoto;
    $banner = $oldBanner;

    $err = verify_csrf_request()
        ?? validate_nome($nome)
        ?? validate_phone($telefone);

    if (!$err && isset($_FILES['foto']) && $_FILES['foto']['error'] === UPLOAD_ERR_OK) {
        $err = validate_uploaded_image($_FILES['foto']);
        if (!$err) {
            [$savedFoto, $saveErr] = save_uploaded_file($_FILES['foto'], 'img', 'avatar_' . $uid, ['jpg', 'jpeg', 'png', 'webp'], 5_000_000);
            $err = $saveErr ?? '';
            if (!$err) {
                $foto = $savedFoto;
            }
        }
    }

    if (!$err && isset($_FILES['banner']) && $_FILES['banner']['error'] === UPLOAD_ERR_OK) {
        $err = validate_uploaded_image($_FILES['banner']);
        if (!$err) {
            [$savedBanner, $saveErr] = save_uploaded_file($_FILES['banner'], 'img', 'banner_' . $uid, ['jpg', 'jpeg', 'png', 'webp'], 5_000_000);
            $err = $saveErr ?? '';
            if (!$err) {
                $banner = $savedBanner;
            }
        }
    }

    if (!$err) {
        $nomeSafe = db_escape($conn, $nome);
        $telefoneSafe = db_escape($conn, $telefone);
        $bioSafe = db_escape($conn, $bio);
        $fotoSafe = db_escape($conn, $foto);
        $bannerSafe = db_escape($conn, $banner);
        $slugSafe = db_escape($conn, ensure_unique_cliente_slug($conn, $nome, $uid));

        mysqli_query(
            $conn,
            "UPDATE cliente
             SET nome = '{$nomeSafe}',
                 telefone = " . ($telefone !== '' ? "'{$telefoneSafe}'" : "NULL") . ",
                 bio = " . ($bio !== '' ? "'{$bioSafe}'" : "NULL") . ",
                 foto = " . ($foto !== '' ? "'{$fotoSafe}'" : "NULL") . ",
                 banner = " . ($banner !== '' ? "'{$bannerSafe}'" : "NULL") . ",
                 slug = '{$slugSafe}'
             WHERE idCliente = {$uid}"
        );

        $_SESSION['user_name'] = $nome;
        $user = db_one($conn, "SELECT * FROM cliente WHERE idCliente = {$uid} LIMIT 1");
        if ($oldFoto !== $foto) {
            delete_orphan_asset_file($conn, 'img', $oldFoto);
        }
        if ($oldBanner !== $banner) {
            delete_orphan_asset_file($conn, 'img', $oldBanner);
        }
        cleanup_unused_uploaded_assets($conn);
        $ok = tr('success.profile_updated');
    }
}

$ordersPerPage = 10;
$releasesPerPage = 10;
$productsPerPage = 10;
// Each profile tab paginates independently through query-string keys.
$ordersPage = max(1, (int)($_GET['orders_page'] ?? 1));
$releasesPage = max(1, (int)($_GET['releases_page'] ?? 1));
$productsPage = max(1, (int)($_GET['products_page'] ?? 1));

$totalOrders = (int)(db_one($conn, "SELECT COUNT(*) AS total FROM encomenda WHERE idCliente = {$uid}")['total'] ?? 0);
$totalOwnReleases = (int)(db_one($conn, "SELECT COUNT(*) AS total FROM release_musical WHERE idCliente = {$uid}")['total'] ?? 0);
$totalOwnProducts = (int)(db_one($conn, "SELECT COUNT(*) AS total FROM produto WHERE idCliente = {$uid}")['total'] ?? 0);
$ordersTotalPages = max(1, (int)ceil($totalOrders / $ordersPerPage));
$releasesTotalPages = max(1, (int)ceil($totalOwnReleases / $releasesPerPage));
$productsTotalPages = max(1, (int)ceil($totalOwnProducts / $productsPerPage));
$ordersPage = min($ordersPage, $ordersTotalPages);
$releasesPage = min($releasesPage, $releasesTotalPages);
$productsPage = min($productsPage, $productsTotalPages);
$ordersOffset = ($ordersPage - 1) * $ordersPerPage;
$releasesOffset = ($releasesPage - 1) * $releasesPerPage;
$productsOffset = ($productsPage - 1) * $productsPerPage;

$profilePageUrl = static function (string $tab, string $key, int $targetPage): string {
    $query = $_GET;
    $query['tab'] = $tab;
    $query[$key] = $targetPage;
    return 'profile.php?' . http_build_query($query);
};

$orders = db_all(
    $conn,
    "SELECT e.*, COUNT(ei.idEncomendaItem) AS total_itens
     FROM encomenda e
     LEFT JOIN encomenda_item ei ON ei.idEncomenda = e.idEncomenda
     WHERE e.idCliente = {$uid}
     GROUP BY e.idEncomenda
     ORDER BY e.criado_em DESC
     LIMIT {$ordersPerPage} OFFSET {$ordersOffset}"
);

$releases = db_all(
    $conn,
    "SELECT r.*, COUNT(f.idFaixa) AS total_faixas
     FROM release_musical r
     LEFT JOIN faixa f ON f.idRelease = r.idRelease
     WHERE r.idCliente = {$uid}
     GROUP BY r.idRelease
     ORDER BY r.criado_em DESC
     LIMIT {$releasesPerPage} OFFSET {$releasesOffset}"
);

// Product rows keep category labels beside the moderation state shown in the table.
$products = db_all(
    $conn,
    "SELECT p.*, cat.nomeCategoria
     FROM produto p
     LEFT JOIN categoria cat ON cat.idCategoria = p.idCategoria
     WHERE p.idCliente = {$uid}
     ORDER BY p.criado_em DESC
     LIMIT {$productsPerPage} OFFSET {$productsOffset}"
);

$totalFollowers = (int)(db_one($conn, "SELECT COUNT(*) AS total FROM seguir_artista WHERE idArtista = {$uid}")['total'] ?? 0);
$totalFollowing = (int)(db_one($conn, "SELECT COUNT(*) AS total FROM seguir_artista WHERE idSeguidor = {$uid}")['total'] ?? 0);

include '../includes/header.php';
?>

<section class="artist-hero">
  <div class="artist-hero-backdrop">
    <?php if (!empty($user['banner'])): ?>
      <img src="<?= h(asset_url('img', $user['banner'])) ?>" alt="<?= h($user['nome']) ?>">
    <?php endif; ?>
  </div>
  <div class="artist-hero-overlay"></div>
  <div class="artist-hero-content wrap">
    <div class="artist-hero-avatar avatar">
      <?php if (!empty($user['foto'])): ?>
        <img src="<?= h(asset_url('img', $user['foto'])) ?>" alt="<?= h($user['nome']) ?>">
      <?php endif; ?>
    </div>
    <div>
      <span class="badge" data-t="profile_badge">A minha conta</span>
      <h1><?= h($user['nome']) ?></h1>
      <p><?= h($user['bio'] ?: '') ?></p>
      <div class="artist-follow-stats">
        <a href="artist.php?id=<?= (int)$uid ?>&followers=1" class="artist-follow-count">
          <?= (int)$totalFollowers ?>&nbsp;<span data-t="artist_stat_followers">Seguidores</span>
        </a>
        <a href="artist.php?id=<?= (int)$uid ?>&following=1" class="artist-follow-count">
          <?= (int)$totalFollowing ?>&nbsp;<span data-t="artist_stat_following">A seguir</span>
        </a>
      </div>
    </div>
  </div>
</section>

<section class="content-shell">
  <div class="wrap">
    <div class="tabs">
      <button class="tab <?= $activeTab === 'edit' ? 'on' : '' ?>" data-tab="edit" data-t="profile_tab_profile">Perfil</button>
      <button class="tab <?= $activeTab === 'orders' ? 'on' : '' ?>" data-tab="orders" data-t="profile_tab_orders">Compras</button>
      <button class="tab <?= $activeTab === 'music' ? 'on' : '' ?>" data-tab="music" data-t="profile_tab_releases">Lançamentos</button>
      <button class="tab <?= $activeTab === 'merch' ? 'on' : '' ?>" data-tab="merch" data-t="profile_tab_merch">Merch</button>
    </div>

    <?php if ($err): ?>
      <div class="alert alert-err"><?= h($err) ?></div>
    <?php endif; ?>
    <?php if ($ok): ?>
      <div class="alert alert-ok"><?= h($ok) ?></div>
    <?php endif; ?>

    <div id="tab-edit" class="<?= $activeTab === 'edit' ? '' : 'is-hidden' ?>">
      <div class="two-column-layout">
        <div class="card surface-card no-motion">
          <div class="card-body">
            <h3 class="section-card-title" data-t="profile_edit">Editar perfil</h3>
            <form method="post" enctype="multipart/form-data" class="stack-form">
              <?= csrf_input() ?>
              <div class="fg">
                <label class="flabel" for="nome" data-t="profile_field_name">Nome</label>
                <input id="nome" type="text" name="nome" class="finput" required maxlength="120" data-name-only value="<?= h($user['nome']) ?>">
              </div>

              <div class="fg">
                <label class="flabel" for="telefone" data-t="profile_field_phone">Telefone</label>
                <input id="telefone" type="tel" name="telefone" class="finput" maxlength="16" placeholder="+351 912345678" value="<?= h($user['telefone'] ?? '') ?>">
              </div>

              <div class="fg">
                <label class="flabel" for="bio" data-t="profile_field_bio">Bio</label>
                <textarea id="bio" name="bio" class="finput"><?= h($user['bio'] ?? '') ?></textarea>
              </div>

              <div class="fg">
                <label class="flabel" for="foto" data-t="profile_field_photo">Foto de perfil</label>
                <input id="foto" type="file" name="foto" class="finput" accept=".jpg,.jpeg,.png,.webp">
                <div class="profile-upload-preview" data-image-preview-for="foto" <?= empty($user['foto']) ? 'hidden' : '' ?>>
                  <img src="<?= !empty($user['foto']) ? h(asset_url('img', $user['foto'])) : '' ?>" alt="">
                </div>
              </div>

              <div class="fg">
                <label class="flabel" for="banner" data-t="profile_field_banner">Banner</label>
                <input id="banner" type="file" name="banner" class="finput" accept=".jpg,.jpeg,.png,.webp">
                <div class="profile-upload-preview profile-upload-preview--banner" data-image-preview-for="banner" <?= empty($user['banner']) ? 'hidden' : '' ?>>
                  <img src="<?= !empty($user['banner']) ? h(asset_url('img', $user['banner'])) : '' ?>" alt="">
                </div>
              </div>

              <button type="submit" name="update_profile" class="btn btn-dark" data-t="profile_save">Guardar alteracoes</button>
            </form>
          </div>
        </div>

        <div class="card surface-card">
          <div class="card-body">
            <h3 class="section-card-title" data-t="profile_summary">Resumo</h3>
            <div class="simple-list">
              <div class="simple-list-item">
                <div>
                  <strong data-t="profile_summary_email">Email</strong>
                  <p><?= h($user['email']) ?></p>
                </div>
              </div>
              <div class="simple-list-item">
                <div>
                  <strong data-t="profile_summary_status">Estado</strong>
                  <p data-status-label="<?= h($user['estado']) ?>"><?= h(order_status_label($user['estado'])) ?></p>
                </div>
              </div>
              <div class="simple-list-item">
                <div>
                  <strong data-t="profile_summary_releases">Lançamentos</strong>
                  <p><?= h(count_label($totalOwnReleases, 'record')) ?></p>
                </div>
              </div>
              <div class="simple-list-item">
                <div>
                  <strong data-t="profile_summary_products">Produtos</strong>
                  <p><?= h(count_label($totalOwnProducts, 'record')) ?></p>
                </div>
              </div>
            </div>
          </div>
        </div>

      </div>
    </div>

    <div id="tab-orders" class="<?= $activeTab === 'orders' ? '' : 'is-hidden' ?>">
      <div class="card surface-card">
        <div class="card-body">
          <h3 class="section-card-title" data-t="profile_orders">As minhas compras</h3>
          <?php if (!$orders): ?>
            <p data-t="profile_orders_empty">Ainda não fizeste compras.</p>
          <?php else: ?>
            <div class="tbl-wrap">
              <table>
                <thead>
                  <tr>
                    <th data-t="profile_table_order">Encomenda</th>
                    <th data-t="profile_table_date">Data</th>
                    <th data-t="profile_table_status">Estado</th>
                    <th data-t="profile_table_payment">Pagamento</th>
                    <th data-t="profile_table_total">Total</th>
                    <th></th>
                  </tr>
                </thead>
                <tbody>
                  <?php foreach ($orders as $order): ?>
                    <tr>
                      <td><strong>#<?= (int)$order['idEncomenda'] ?></strong></td>
                      <td><?= date('d/m/Y', strtotime($order['criado_em'])) ?></td>
                      <td><span class="badge <?= h(state_badge_class($order['estado_encomenda'])) ?>" data-status-label="<?= h($order['estado_encomenda']) ?>"><?= h(order_status_label($order['estado_encomenda'])) ?></span></td>
                      <td><span class="badge <?= h(state_badge_class($order['estado_pagamento'])) ?>" data-status-label="<?= h($order['estado_pagamento']) ?>"><?= h(payment_status_label($order['estado_pagamento'])) ?></span></td>
                      <td><?= h(format_eur((float)$order['total_final'])) ?></td>
                      <td><a href="receipt.php?id=<?= (int)$order['idEncomenda'] ?>" class="btn btn-ghost btn-sm" target="_blank" data-t="profile_receipt">Recibo</a></td>
                    </tr>
                  <?php endforeach; ?>
                </tbody>
              </table>
            </div>
            <?php if ($ordersTotalPages > 1): ?>
              <nav class="pager" aria-label="Pagination">
                <?= $ordersPage > 1 ? '<a class="btn btn-ghost btn-sm" href="' . h($profilePageUrl('orders', 'orders_page', $ordersPage - 1)) . '" data-t="pagination_previous">Anterior</a>' : '<span class="btn btn-ghost btn-sm is-disabled" data-t="pagination_previous">Anterior</span>' ?>
                <span class="pager-status"><span data-t="pagination_page">Página</span> <?= (int)$ordersPage ?> <span data-t="pagination_of">de</span> <?= (int)$ordersTotalPages ?></span>
                <?= $ordersPage < $ordersTotalPages ? '<a class="btn btn-ghost btn-sm" href="' . h($profilePageUrl('orders', 'orders_page', $ordersPage + 1)) . '" data-t="pagination_next">Seguinte</a>' : '<span class="btn btn-ghost btn-sm is-disabled" data-t="pagination_next">Seguinte</span>' ?>
              </nav>
            <?php endif; ?>
          <?php endif; ?>
        </div>
      </div>
    </div>

    <div id="tab-music" class="<?= $activeTab === 'music' ? '' : 'is-hidden' ?>">
      <div class="card surface-card">
        <div class="card-body">
          <div class="between mb6">
            <h3 class="section-card-title" data-t="profile_releases">Os meus lançamentos</h3>
            <a href="upload_music.php" class="btn btn-dark btn-sm" data-t="profile_new_release">Novo lançamento</a>
          </div>

          <?php if (!$releases): ?>
            <p data-t="profile_releases_empty">Ainda não submeteste lançamentos.</p>
          <?php else: ?>
            <div class="tbl-wrap">
              <table>
                <thead>
                  <tr>
                    <th data-t="profile_table_cover">Capa</th>
                    <th data-t="profile_table_title">Título</th>
                    <th data-t="profile_table_type">Tipo</th>
                    <th data-t="profile_table_tracks">Faixas</th>
                    <th data-t="profile_table_status">Estado</th>
                    <th data-t="profile_table_action">Acao</th>
                  </tr>
                </thead>
                <tbody>
                  <?php foreach ($releases as $release): ?>
                    <tr>
                      <td>
                        <div class="admin-table-thumb">
                          <?php if (!empty($release['capa'])): ?>
                            <img src="<?= h(asset_url('img', $release['capa'])) ?>" alt="<?= h($release['titulo']) ?>">
                          <?php else: ?>
                            <span data-t="products_no_image">Sem imagem</span>
                          <?php endif; ?>
                        </div>
                      </td>
                      <td>
                        <strong><?= h($release['titulo']) ?></strong>
                        <?php if (!empty($release['motivo_rejeicao'])): ?>
                          <br><span class="color-text3"><?= h($release['motivo_rejeicao']) ?></span>
                        <?php endif; ?>
                      </td>
                      <td data-release-type="<?= h($release['tipo']) ?>"><?= h(release_type_label($release['tipo'])) ?></td>
                      <td><?= (int)$release['total_faixas'] ?></td>
                      <td><span class="badge <?= h(state_badge_class($release['estado'])) ?>" data-status-label="<?= h($release['estado']) ?>"><?= h(order_status_label($release['estado'])) ?></span></td>
                      <td>
                        <div class="profile-table-actions">
                          <a href="upload_music.php?edit=<?= (int)$release['idRelease'] ?>" class="btn btn-ghost btn-sm" data-t="profile_action_edit">Editar</a>
                          <?php if ((int)($release['bloqueado_admin'] ?? 0) === 1): ?>
                            <span class="badge badge-light" data-t="profile_admin_blocked">Bloqueado pelo admin</span>
                          <?php elseif (in_array($release['estado'], ['aprovado', 'inativo'], true)): ?>
                            <button type="button" class="btn btn-ghost btn-sm js-toggle-item" data-type="music" data-id="<?= (int)$release['idRelease'] ?>" data-t="<?= (int)$release['ativo'] === 1 ? 'profile_action_deactivate' : 'profile_action_activate' ?>">
                              <?= (int)$release['ativo'] === 1 ? 'Inativar' : 'Ativar' ?>
                            </button>
                          <?php else: ?>
                            <span class="color-text3"><?= h(tr('misc.no_action')) ?></span>
                          <?php endif; ?>
                          <form method="post" class="inline-delete-form js-delete-release-form" data-confirm="<?= h(tr('confirm.release_delete')) ?>">
                            <?= csrf_input() ?>
                            <input type="hidden" name="release_id" value="<?= (int)$release['idRelease'] ?>">
                            <button type="submit" name="delete_release" value="1" class="btn btn-danger btn-sm" data-t="profile_action_delete_release">Eliminar</button>
                          </form>
                        </div>
                      </td>
                    </tr>
                  <?php endforeach; ?>
                </tbody>
              </table>
            </div>
            <?php if ($releasesTotalPages > 1): ?>
              <nav class="pager" aria-label="Pagination">
                <?= $releasesPage > 1 ? '<a class="btn btn-ghost btn-sm" href="' . h($profilePageUrl('music', 'releases_page', $releasesPage - 1)) . '" data-t="pagination_previous">Anterior</a>' : '<span class="btn btn-ghost btn-sm is-disabled" data-t="pagination_previous">Anterior</span>' ?>
                <span class="pager-status"><span data-t="pagination_page">Página</span> <?= (int)$releasesPage ?> <span data-t="pagination_of">de</span> <?= (int)$releasesTotalPages ?></span>
                <?= $releasesPage < $releasesTotalPages ? '<a class="btn btn-ghost btn-sm" href="' . h($profilePageUrl('music', 'releases_page', $releasesPage + 1)) . '" data-t="pagination_next">Seguinte</a>' : '<span class="btn btn-ghost btn-sm is-disabled" data-t="pagination_next">Seguinte</span>' ?>
              </nav>
            <?php endif; ?>
          <?php endif; ?>
        </div>
      </div>
    </div>

    <div id="tab-merch" class="<?= $activeTab === 'merch' ? '' : 'is-hidden' ?>">
      <div class="card surface-card">
        <div class="card-body">
          <div class="between mb6">
            <h3 class="section-card-title" data-t="profile_products">Os meus produtos</h3>
            <a href="upload_merch.php" class="btn btn-dark btn-sm" data-t="profile_new_product">Novo produto</a>
          </div>

          <?php if (!$products): ?>
            <p data-t="profile_products_empty">Ainda não submeteste merch.</p>
          <?php else: ?>
            <div class="tbl-wrap">
              <table>
                <thead>
                  <tr>
                    <th data-t="profile_table_image">Imagem</th>
                    <th data-t="profile_table_product">Produto</th>
                    <th data-t="profile_table_category">Categoria</th>
                    <th data-t="profile_table_price">Preco</th>
                    <th data-t="profile_table_stock">Stock</th>
                    <th data-t="profile_table_status">Estado</th>
                    <th data-t="profile_table_action">Acao</th>
                  </tr>
                </thead>
                <tbody>
                  <?php foreach ($products as $product): ?>
                    <?php $productImage = product_main_image($conn, (int)$product['idProduto']); ?>
                    <tr>
                      <td>
                        <div class="admin-table-thumb">
                          <?php if ($productImage): ?>
                            <img src="<?= h(asset_url('img', $productImage)) ?>" alt="<?= h($product['nomeProduto']) ?>">
                          <?php else: ?>
                            <span data-t="products_no_image">Sem imagem</span>
                          <?php endif; ?>
                        </div>
                      </td>
                      <td>
                        <strong><?= h($product['nomeProduto']) ?></strong>
                        <?php if (!empty($product['motivo_rejeicao'])): ?>
                          <br><span class="color-text3"><?= h($product['motivo_rejeicao']) ?></span>
                        <?php endif; ?>
                      </td>
                      <td>
                        <?php if (!empty($product['nomeCategoria'])): ?>
                          <span data-product-category="<?= h($product['nomeCategoria']) ?>"><?= h(category_label($product['nomeCategoria'])) ?></span>
                        <?php else: ?>
                          <span data-t="profile_no_category">Sem categoria</span>
                        <?php endif; ?>
                      </td>
                      <td><?= h(format_eur((float)$product['precoAtual'])) ?></td>
                      <td><?= (int)$product['stock_total'] ?></td>
                      <td><span class="badge <?= h(state_badge_class($product['estado'])) ?>" data-status-label="<?= h($product['estado']) ?>"><?= h(order_status_label($product['estado'])) ?></span></td>
                      <td>
                        <div class="profile-table-actions">
                          <a href="upload_merch.php?edit=<?= (int)$product['idProduto'] ?>" class="btn btn-ghost btn-sm" data-t="profile_action_edit">Editar</a>
                          <?php if ((int)($product['bloqueado_admin'] ?? 0) === 1): ?>
                            <span class="badge badge-light" data-t="profile_admin_blocked">Bloqueado pelo admin</span>
                          <?php elseif (in_array($product['estado'], ['aprovado', 'inativo'], true)): ?>
                            <button type="button" class="btn btn-ghost btn-sm js-toggle-item" data-type="merch" data-id="<?= (int)$product['idProduto'] ?>" data-t="<?= (int)$product['ativo'] === 1 ? 'profile_action_deactivate' : 'profile_action_activate' ?>">
                              <?= (int)$product['ativo'] === 1 ? 'Inativar' : 'Ativar' ?>
                            </button>
                          <?php else: ?>
                            <span class="color-text3"><?= h(tr('misc.no_action')) ?></span>
                          <?php endif; ?>
                        </div>
                      </td>
                    </tr>
                  <?php endforeach; ?>
                </tbody>
              </table>
            </div>
            <?php if ($productsTotalPages > 1): ?>
              <nav class="pager" aria-label="Pagination">
                <?= $productsPage > 1 ? '<a class="btn btn-ghost btn-sm" href="' . h($profilePageUrl('merch', 'products_page', $productsPage - 1)) . '" data-t="pagination_previous">Anterior</a>' : '<span class="btn btn-ghost btn-sm is-disabled" data-t="pagination_previous">Anterior</span>' ?>
                <span class="pager-status"><span data-t="pagination_page">Página</span> <?= (int)$productsPage ?> <span data-t="pagination_of">de</span> <?= (int)$productsTotalPages ?></span>
                <?= $productsPage < $productsTotalPages ? '<a class="btn btn-ghost btn-sm" href="' . h($profilePageUrl('merch', 'products_page', $productsPage + 1)) . '" data-t="pagination_next">Seguinte</a>' : '<span class="btn btn-ghost btn-sm is-disabled" data-t="pagination_next">Seguinte</span>' ?>
              </nav>
            <?php endif; ?>
          <?php endif; ?>
        </div>
      </div>
    </div>
  </div>
</section>

<script>
document.querySelectorAll('.tab[data-tab]').forEach((button) => {
  button.addEventListener('click', () => {
    document.querySelectorAll('.tab[data-tab]').forEach((tab) => tab.classList.remove('on'));
    button.classList.add('on');
    ['edit', 'orders', 'music', 'merch'].forEach((name) => {
      const section = document.getElementById('tab-' + name);
      if (section) {
        section.style.display = button.dataset.tab === name ? 'block' : 'none';
      }
    });
  });
});

document.querySelectorAll('.js-toggle-item').forEach((button) => {
  button.addEventListener('click', async () => {
    const body = new URLSearchParams();
    body.set('type', button.dataset.type);
    body.set('id', button.dataset.id);
    if (window.CSRF_TOKEN) body.set('csrf_token', window.CSRF_TOKEN);

    const response = await fetch('../api/toggle_item.php', {
      method: 'POST',
      headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
      body: body.toString()
    });

    const result = await response.json();
    if (result.success) {
      window.location.reload();
      return;
    }

    toast(result.error || <?= json_encode(tr('error.order_update'), JSON_UNESCAPED_UNICODE) ?>);
  });
});

document.querySelectorAll('.js-delete-release-form').forEach((form) => {
  form.addEventListener('submit', (event) => {
    if (!confirm(form.dataset.confirm || 'Delete this release?')) {
      event.preventDefault();
    }
  });
});
</script>

<?php include '../includes/footer.php'; ?>
