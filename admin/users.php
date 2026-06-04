<?php
require_once '../includes/config.php';
require_admin_permission('users');

$feedback = '';
$error = '';
$allowedStates = ['ativo', 'inativo', 'bloqueado'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $error = verify_csrf_request() ?? '';
    $userId = (int)($_POST['user_id'] ?? 0);
    $state = (string)($_POST['estado'] ?? '');
    $name = trim((string)($_POST['nome'] ?? ''));
    $email = trim((string)($_POST['email'] ?? ''));

    if ($error === '' && ($userId <= 0 || !in_array($state, $allowedStates, true) || $name === '' || !filter_var($email, FILTER_VALIDATE_EMAIL))) {
        $error = tr('error.api_invalid_request');
    }

    if ($error === '') {
        $stateSafe = db_escape($conn, $state);
        $nameSafe = db_escape($conn, $name);
        $emailSafe = db_escape($conn, $email);
        if (mysqli_query($conn, "UPDATE cliente SET nome = '{$nameSafe}', email = '{$emailSafe}', estado = '{$stateSafe}' WHERE idCliente = {$userId}")) {
            $feedback = tr('success.user_state_updated');
        } else {
            $error = tr('error.user_update');
        }
    }
}

$perPage = 20;
$page = max(1, (int)($_GET['page'] ?? 1));
$totalUsers = (int)(db_one($conn, "SELECT COUNT(*) AS total FROM cliente")['total'] ?? 0);
$totalPages = max(1, (int)ceil($totalUsers / $perPage));
$page = min($page, $totalPages);
$offset = ($page - 1) * $perPage;

$users = db_all(
    $conn,
    "SELECT c.*,
            COUNT(DISTINCT p.idProduto) AS total_products,
            COUNT(DISTINCT CASE WHEN r.estado = 'aprovado' THEN r.idRelease END) AS total_releases,
            COUNT(DISTINCT e.idEncomenda) AS total_orders
     FROM cliente c
     LEFT JOIN produto p ON p.idCliente = c.idCliente
     LEFT JOIN release_musical r ON r.idCliente = c.idCliente
     LEFT JOIN encomenda e ON e.idCliente = c.idCliente
     GROUP BY c.idCliente
     ORDER BY c.criado_em DESC
     LIMIT {$perPage} OFFSET {$offset}"
);

$stats = [
    'ativos' => 0,
    'inativos' => 0,
    'bloqueados' => 0,
    'artistas' => 0,
];
foreach ($users as $user) {
    $state = (string)$user['estado'];
    if ($state === 'ativo') {
        $stats['ativos']++;
    } elseif ($state === 'inativo') {
        $stats['inativos']++;
    } elseif ($state === 'bloqueado') {
        $stats['bloqueados']++;
    }
    if ((int)$user['total_products'] > 0 || (int)$user['total_releases'] > 0) {
        $stats['artistas']++;
    }
}

include 'admin_header.php';
?>

<div class="admin-top">
  <div>
    <span class="admin-page-kicker" data-admin-t="users_kicker">Accounts</span>
    <h2 data-admin-t="users_title">Utilizadores</h2>
    <p data-admin-t="users_intro">Ve atividade, artistas e estado das contas de cliente.</p>
  </div>
  <div class="stats-grid admin-top-stats">
    <button type="button" class="stat stat-button" data-admin-stat-filter="users-search" data-filter-value="ativo"><div class="stat-val"><?= (int)$stats['ativos'] ?></div><div class="stat-lbl" data-admin-t="users_active">Ativos</div></button>
    <button type="button" class="stat stat-button" data-admin-stat-filter="users-search" data-filter-value="artista"><div class="stat-val"><?= (int)$stats['artistas'] ?></div><div class="stat-lbl" data-admin-t="users_artists">Artistas</div></button>
    <button type="button" class="stat stat-button" data-admin-stat-filter="users-search" data-filter-value="inativo"><div class="stat-val"><?= (int)$stats['inativos'] ?></div><div class="stat-lbl" data-admin-t="state_inactive">Inativos</div></button>
    <button type="button" class="stat stat-button" data-admin-stat-filter="users-search" data-filter-value="bloqueado"><div class="stat-val"><?= (int)$stats['bloqueados'] ?></div><div class="stat-lbl" data-admin-t="state_blocked">Bloqueados</div></button>
  </div>
</div>

<?php if ($feedback): ?>
  <div class="alert alert-ok"><?= h($feedback) ?></div>
<?php endif; ?>
<?php if ($error): ?>
  <div class="alert alert-err"><?= h($error) ?></div>
<?php endif; ?>

<div id="users-search" data-admin-search-scope>
<section class="acard-box">
  <div class="acard-box-head">
    <h4 data-admin-t="users_all">Todos os utilizadores</h4>
    <div class="admin-card-head-tools">
      <label class="sbar admin-section-search">
        <svg width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><circle cx="11" cy="11" r="7"/><path d="m21 21-4.3-4.3"/></svg>
        <input type="search" data-admin-search="users-search" placeholder="Pesquisar..." data-admin-tp="admin_search_placeholder">
      </label>
      <span class="badge badge-light"><?= (int)$totalUsers ?></span>
    </div>
  </div>

  <?php if (!$users): ?>
    <p data-admin-t="users_empty">Sem utilizadores registados.</p>
  <?php else: ?>
    <div class="tbl-wrap">
      <table>
        <thead>
          <tr>
            <th>ID</th>
            <th data-admin-t="users_name">Nome</th>
            <th data-admin-t="label_email">Email</th>
            <th data-admin-t="nav_products">Produtos</th>
            <th data-admin-t="nav_releases">Lançamentos</th>
            <th data-admin-t="card_orders">Encomendas</th>
            <th data-admin-t="categories_state">Estado</th>
            <th data-admin-t="orders_action">Acao</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($users as $user): ?>
            <?php $isArtist = (int)$user['total_products'] > 0 || (int)$user['total_releases'] > 0; ?>
            <tr data-admin-state="<?= h((string)$user['estado'] . ($isArtist ? ' artista' : '')) ?>">
              <td>#<?= (int)$user['idCliente'] ?></td>
              <td>
                <div class="admin-user-cell">
                  <span class="admin-user-avatar">
                    <?php if (!empty($user['foto'])): ?>
                      <img src="<?= h(asset_url('img', $user['foto'])) ?>" alt="">
                    <?php else: ?>
                      <?= h(mb_strtoupper(mb_substr((string)$user['nome'], 0, 1))) ?>
                    <?php endif; ?>
                  </span>
                  <strong><?= h($user['nome']) ?></strong>
                </div>
              </td>
              <td><?= h($user['email']) ?></td>
              <td><?= (int)$user['total_products'] ?></td>
              <td><?= (int)$user['total_releases'] ?></td>
              <td><?= (int)$user['total_orders'] ?></td>
              <td><span class="badge <?= h(state_badge_class((string)$user['estado'])) ?>"><?= h(order_status_label((string)$user['estado'])) ?></span></td>
              <td>
                <details class="admin-inline-editor">
                  <summary class="btn btn-ghost btn-sm" data-admin-t="btn_edit">Editar</summary>
                  <form method="post" class="admin-inline-edit-form">
                    <?= csrf_input() ?>
                    <input type="hidden" name="user_id" value="<?= (int)$user['idCliente'] ?>">
                    <label><span data-admin-t="users_name">Nome</span><input name="nome" class="finput" value="<?= h($user['nome']) ?>" required></label>
                    <label><span data-admin-t="label_email">Email</span><input type="email" name="email" class="finput" value="<?= h($user['email']) ?>" required></label>
                    <label><span data-admin-t="categories_state">Estado</span><select name="estado" class="finput">
                      <?php foreach ($allowedStates as $state): ?>
                        <option value="<?= h($state) ?>" <?= $state === (string)$user['estado'] ? 'selected' : '' ?>><?= h(order_status_label($state)) ?></option>
                      <?php endforeach; ?>
                    </select></label>
                    <button type="submit" class="btn btn-dark btn-sm" data-admin-t="btn_save_changes">Guardar alterações</button>
                  </form>
                </details>
              </td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  <?php endif; ?>
  <?php if ($totalPages > 1): ?>
    <nav class="pager" aria-label="Pagination">
      <?= $page > 1 ? '<a class="btn btn-ghost btn-sm" href="users.php?page=' . ($page - 1) . '" data-users-pager data-admin-t="pagination_previous">Anterior</a>' : '<span class="btn btn-ghost btn-sm is-disabled" data-admin-t="pagination_previous">Anterior</span>' ?>
      <span class="pager-status" data-admin-page-status data-page-current="<?= (int)$page ?>" data-page-total="<?= (int)$totalPages ?>">P?gina <?= (int)$page ?> de <?= (int)$totalPages ?></span>
      <?= $page < $totalPages ? '<a class="btn btn-ghost btn-sm" href="users.php?page=' . ($page + 1) . '" data-users-pager data-admin-t="pagination_next">Seguinte</a>' : '<span class="btn btn-ghost btn-sm is-disabled" data-admin-t="pagination_next">Seguinte</span>' ?>
    </nav>
  <?php endif; ?>
</section>
</div>

<?php include 'admin_footer.php'; ?>
