<?php
require_once '../includes/config.php';
require_admin_login();

$feedback = '';
$error = '';
$allowedStates = ['ativo', 'inativo', 'bloqueado'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $error = verify_csrf_request() ?? '';
    $userId = (int)($_POST['user_id'] ?? 0);
    $state = (string)($_POST['estado'] ?? '');

    if ($error === '' && ($userId <= 0 || !in_array($state, $allowedStates, true))) {
        $error = tr('error.api_invalid_request');
    }

    if ($error === '') {
        $stateSafe = db_escape($conn, $state);
        if (mysqli_query($conn, "UPDATE cliente SET estado = '{$stateSafe}' WHERE idCliente = {$userId}")) {
            $feedback = tr('success.user_state_updated');
        } else {
            $error = tr('error.user_update');
        }
    }
}

$users = db_all(
    $conn,
    "SELECT c.*,
            COUNT(DISTINCT p.idProduto) AS total_products,
            COUNT(DISTINCT r.idRelease) AS total_releases,
            COUNT(DISTINCT e.idEncomenda) AS total_orders
     FROM cliente c
     LEFT JOIN produto p ON p.idCliente = c.idCliente
     LEFT JOIN release_musical r ON r.idCliente = c.idCliente
     LEFT JOIN encomenda e ON e.idCliente = c.idCliente
     GROUP BY c.idCliente
     ORDER BY c.created_at DESC
     LIMIT 120"
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
    <h2 data-admin-t="users_title">Utilizadores</h2>
  </div>
</div>

<?php if ($feedback): ?>
  <div class="alert alert-ok"><?= h($feedback) ?></div>
<?php endif; ?>
<?php if ($error): ?>
  <div class="alert alert-err"><?= h($error) ?></div>
<?php endif; ?>

<div class="stats-grid">
  <div class="stat"><div class="stat-val"><?= (int)$stats['ativos'] ?></div><div class="stat-lbl" data-admin-t="users_active">Ativos</div></div>
  <div class="stat"><div class="stat-val"><?= (int)$stats['artistas'] ?></div><div class="stat-lbl" data-admin-t="users_artists">Artistas</div></div>
  <div class="stat"><div class="stat-val"><?= (int)$stats['inativos'] ?></div><div class="stat-lbl" data-admin-t="state_inactive">Inativos</div></div>
  <div class="stat"><div class="stat-val"><?= (int)$stats['bloqueados'] ?></div><div class="stat-lbl" data-admin-t="state_blocked">Bloqueados</div></div>
</div>

<div class="admin-search-row">
  <label class="sbar">
    <svg width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><circle cx="11" cy="11" r="7"/><path d="m21 21-4.3-4.3"/></svg>
    <input type="search" data-admin-search="users-search" placeholder="Pesquisar..." data-admin-tp="admin_search_placeholder">
  </label>
</div>

<div id="users-search" data-admin-search-scope>
<section class="acard-box">
  <div class="acard-box-head">
    <h4 data-admin-t="users_all">Todos os utilizadores</h4>
  </div>

  <div class="tbl-wrap">
    <table>
      <thead>
        <tr>
          <th>ID</th>
          <th data-admin-t="users_name">Nome</th>
          <th>Email</th>
          <th data-admin-t="nav_products">Produtos</th>
          <th data-admin-t="nav_releases">Lancamentos</th>
          <th data-admin-t="card_orders">Encomendas</th>
          <th data-admin-t="categories_state">Estado</th>
          <th data-admin-t="orders_action">Acao</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($users as $user): ?>
          <tr>
            <td>#<?= (int)$user['idCliente'] ?></td>
            <td><strong><?= h($user['nome']) ?></strong><br><span><?= h($user['slug'] ?? '') ?></span></td>
            <td><?= h($user['email']) ?></td>
            <td><?= (int)$user['total_products'] ?></td>
            <td><?= (int)$user['total_releases'] ?></td>
            <td><?= (int)$user['total_orders'] ?></td>
            <td><span class="badge <?= h(state_badge_class((string)$user['estado'])) ?>"><?= h(order_status_label((string)$user['estado'])) ?></span></td>
            <td>
              <form method="post" class="admin-table-form">
                <?= csrf_input() ?>
                <input type="hidden" name="user_id" value="<?= (int)$user['idCliente'] ?>">
                <select name="estado" class="finput">
                  <?php foreach ($allowedStates as $state): ?>
                    <option value="<?= h($state) ?>" <?= $state === (string)$user['estado'] ? 'selected' : '' ?>><?= h(order_status_label($state)) ?></option>
                  <?php endforeach; ?>
                </select>
                <button type="submit" class="btn btn-ghost btn-sm" data-admin-t="categories_save">Guardar</button>
              </form>
            </td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</section>
</div>

<?php include 'admin_footer.php'; ?>
