<?php
require_once '../includes/config.php';
require_admin_permission('admins');

$feedback = '';
$error = '';
$roles = [
    'Administrador' => [
        'pt' => 'Administrador',
        'en' => 'Administrator',
        'key' => 'admins_role_admin',
        'help_pt' => 'Pode gerir catálogo, utilizadores, mensagens, relatórios e definições.',
        'help_en' => 'Can manage catalog, users, messages, reports, and settings.',
        'help_key' => 'admins_role_admin_help',
    ],
    'Produtos' => [
        'pt' => 'Gestor de produtos',
        'en' => 'Product manager',
        'key' => 'admins_role_products',
        'help_pt' => 'Aprova merch, gere categorias e stock visual.',
        'help_en' => 'Approves merch, manages categories and product catalog.',
        'help_key' => 'admins_role_products_help',
    ],
    'Musica' => [
        'pt' => 'Revisor de música',
        'en' => 'Music reviewer',
        'key' => 'admins_role_music',
        'help_pt' => 'Aprova lançamentos e ouve faixas submetidas.',
        'help_en' => 'Approves releases and reviews submitted tracks.',
        'help_key' => 'admins_role_music_help',
    ],
    'Suporte' => [
        'pt' => 'Suporte',
        'en' => 'Support',
        'key' => 'admins_role_support',
        'help_pt' => 'Responde mensagens dos utilizadores.',
        'help_en' => 'Replies to user support messages.',
        'help_key' => 'admins_role_support_help',
    ],
    'Relatorios' => [
        'pt' => 'Relatórios',
        'en' => 'Reports',
        'key' => 'admins_role_reports',
        'help_pt' => 'Consulta dashboards, vendas e exportações.',
        'help_en' => 'Views dashboards, sales, and exports.',
        'help_key' => 'admins_role_reports_help',
    ],
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $error = verify_csrf_request() ?? '';
    $action = (string)($_POST['action'] ?? '');

    if ($error === '' && $action === 'create') {
        $name = trim((string)($_POST['nome'] ?? ''));
        $email = trim((string)($_POST['email'] ?? ''));
        $password = (string)($_POST['password'] ?? '');
        $role = (string)($_POST['cargo'] ?? 'Administrador');

        $error = validate_nome($name) ?? validate_email($email) ?? validate_password($password) ?? '';
        if ($error === '' && !isset($roles[$role])) {
            $error = tr('error.api_invalid_request');
        }
        if ($error === '' && db_one_prepared($conn, "SELECT idAdmin FROM admin WHERE email = ? LIMIT 1", 's', [$email])) {
            $error = tr('error.admin_email_exists');
        }
        if ($error === '') {
            $hash = password_hash($password, PASSWORD_DEFAULT);
            db_prepared(
                $conn,
                "INSERT INTO admin (nome, email, palavra_passe, cargo, ativo) VALUES (?, ?, ?, ?, 1)",
                'ssss',
                [$name, $email, $hash, $role]
            );
            $feedback = tr('success.admin_created');
        }
    } elseif ($error === '' && $action === 'update') {
        $adminId = (int)($_POST['admin_id'] ?? 0);
        $role = (string)($_POST['cargo'] ?? 'Administrador');
        $active = (int)($_POST['ativo'] ?? 0) === 1 ? 1 : 0;
        $target = db_one($conn, "SELECT * FROM admin WHERE idAdmin = {$adminId} LIMIT 1");

        if (!$target || !isset($roles[$role])) {
            $error = tr('error.api_invalid_request');
        } elseif (is_super_admin($target) && ((string)$target['cargo'] !== $role || $active !== 1)) {
            $error = tr('error.super_admin_locked');
        } elseif ((int)$target['idAdmin'] === current_admin_id() && $active === 0) {
            $error = tr('error.admin_self_deactivate');
        } else {
            db_prepared(
                $conn,
                "UPDATE admin SET cargo = ?, ativo = ? WHERE idAdmin = ?",
                'sii',
                [$role, $active, $adminId]
            );
            $feedback = tr('success.admin_updated');
        }
    }
}

$admins = db_all($conn, "SELECT * FROM admin ORDER BY ativo DESC, criado_em DESC");
$stats = [
    'active' => 0,
    'inactive' => 0,
    'limited' => 0,
];
foreach ($admins as $row) {
    ((int)$row['ativo'] === 1) ? $stats['active']++ : $stats['inactive']++;
    if (!in_array(admin_role_key($row), ['super', 'admin'], true)) {
        $stats['limited']++;
    }
}

include 'admin_header.php';
?>

<div class="admin-top">
  <div>
    <span class="admin-page-kicker" data-admin-t="admins_kicker">Team access</span>
    <h2 data-admin-t="admins_title">Admins</h2>
    <p data-admin-t="admins_intro">Cria contas de equipa, pausa acessos e define o cargo de cada admin.</p>
  </div>
  <div class="stats-grid admin-top-stats admin-top-stats--three">
    <button type="button" class="stat stat-button" data-admin-stat-filter="admins-search" data-filter-value="ativo"><div class="stat-val"><?= (int)$stats['active'] ?></div><div class="stat-lbl" data-admin-t="state_active">Ativos</div></button>
    <button type="button" class="stat stat-button" data-admin-stat-filter="admins-search" data-filter-value="inativo"><div class="stat-val"><?= (int)$stats['inactive'] ?></div><div class="stat-lbl" data-admin-t="state_inactive">Inativos</div></button>
    <button type="button" class="stat stat-button" data-admin-stat-filter="admins-search" data-filter-value="limitado"><div class="stat-val"><?= (int)$stats['limited'] ?></div><div class="stat-lbl" data-admin-t="admins_limited">Limitados</div></button>
  </div>
</div>

<?php if ($feedback): ?><div class="alert alert-ok"><?= h($feedback) ?></div><?php endif; ?>
<?php if ($error): ?><div class="alert alert-err"><?= h($error) ?></div><?php endif; ?>

<details class="acard-box admin-collapse-panel admin-create-admin-card">
  <summary class="acard-box-head admin-collapse-summary">
    <div>
      <span class="admin-kicker" data-admin-t="admins_new_kicker">Novo admin</span>
      <h4 data-admin-t="admins_new_title">Criar acesso</h4>
    </div>
  </summary>
  <form method="post" class="stack-form admin-admin-create-form">
    <?= csrf_input() ?>
    <input type="hidden" name="action" value="create">
    <div class="frow">
      <div class="fg">
        <label class="flabel" data-admin-t="users_name">Nome</label>
        <input type="text" name="nome" class="finput" required>
      </div>
      <div class="fg">
        <label class="flabel">Email</label>
        <input type="email" name="email" class="finput" required>
      </div>
      <div class="fg">
        <label class="flabel" data-admin-t="login_password">Password</label>
        <input type="password" name="password" class="finput" required>
      </div>
      <div class="fg admin-role-field">
        <label class="flabel" data-admin-t="admins_role">Cargo</label>
        <select name="cargo" class="finput">
          <?php foreach ($roles as $value => $labels): ?>
            <option value="<?= h($value) ?>" data-admin-t="<?= h($labels['key']) ?>"><?= h($labels[current_lang()] ?? $labels['pt']) ?></option>
          <?php endforeach; ?>
        </select>
      </div>
    </div>
    <div class="admin-role-guide">
      <?php foreach ($roles as $value => $labels): ?>
        <div>
          <strong data-admin-t="<?= h($labels['key']) ?>"><?= h($labels[current_lang()] ?? $labels['pt']) ?></strong>
          <span data-admin-t="<?= h($labels['help_key']) ?>"><?= h($labels['help_' . current_lang()] ?? $labels['help_pt']) ?></span>
        </div>
      <?php endforeach; ?>
    </div>
    <button type="submit" class="btn btn-dark" data-admin-t="admins_create">Criar admin</button>
  </form>
</details>

<div id="admins-search" data-admin-search-scope>
<section class="acard-box admin-team-card">
  <div class="acard-box-head">
    <h4 data-admin-t="admins_all">Equipa admin</h4>
    <div class="admin-card-head-tools">
      <label class="sbar admin-section-search">
        <svg width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><circle cx="11" cy="11" r="7"/><path d="m21 21-4.3-4.3"/></svg>
        <input type="search" data-admin-search="admins-search" placeholder="Pesquisar..." data-admin-tp="admin_search_placeholder">
      </label>
      <span class="badge badge-light"><?= count($admins) ?></span>
    </div>
  </div>

  <div class="tbl-wrap admin-team-table-wrap">
    <table>
      <thead>
        <tr>
          <th>ID</th>
          <th data-admin-t="users_name">Nome</th>
          <th>Email</th>
          <th data-admin-t="admins_role">Cargo</th>
          <th data-admin-t="categories_state">Estado</th>
          <th data-admin-t="orders_action">Acao</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($admins as $row): ?>
          <?php
          $state = (int)$row['ativo'] === 1 ? 'ativo' : 'inativo';
          $rowIsSuper = is_super_admin($row);
          $roleKey = $roles[(string)$row['cargo']]['key'] ?? '';
          $roleHelpKey = $roles[(string)$row['cargo']]['help_key'] ?? '';
          $roleLabel = $rowIsSuper
              ? (current_lang() === 'en' ? 'Super admin' : 'Admin principal')
              : ($roles[(string)$row['cargo']][current_lang()] ?? $row['cargo']);
          ?>
          <tr data-admin-state="<?= h($state . ' ' . admin_role_key($row) . ' ' . $row['cargo']) ?>">
            <td>#<?= (int)$row['idAdmin'] ?></td>
            <td><strong><?= h($row['nome']) ?></strong><br><span><?= h(date('d/m/Y', strtotime($row['criado_em']))) ?></span></td>
            <td><?= h($row['email']) ?></td>
            <td>
              <span class="badge badge-light" <?= $rowIsSuper ? 'data-admin-t="admins_role_super"' : ($roleKey ? 'data-admin-t="' . h($roleKey) . '"' : '') ?>><?= h($roleLabel) ?></span>
              <?php if ($rowIsSuper): ?>
                <small class="admin-role-note" data-admin-t="admins_owner_account">Conta dona</small>
              <?php endif; ?>
            </td>
            <td><span class="badge <?= h(state_badge_class($state)) ?>"><?= h(order_status_label($state)) ?></span></td>
            <td>
              <?php if ($rowIsSuper): ?>
                <div class="admin-locked-admin">
                  <span data-admin-t="admins_locked">Bloqueado</span>
                  <small data-admin-t="admins_locked_help">Esta conta mantem o controlo total.</small>
                </div>
              <?php else: ?>
                <form method="post" class="admin-team-action-form">
                  <?= csrf_input() ?>
                  <input type="hidden" name="action" value="update">
                  <input type="hidden" name="admin_id" value="<?= (int)$row['idAdmin'] ?>">
                  <select name="cargo" class="finput">
                    <?php foreach ($roles as $value => $labels): ?>
                      <option value="<?= h($value) ?>" <?= $value === (string)$row['cargo'] ? 'selected' : '' ?> data-admin-t="<?= h($labels['key']) ?>"><?= h($labels[current_lang()] ?? $labels['pt']) ?></option>
                    <?php endforeach; ?>
                  </select>
                  <select name="ativo" class="finput">
                    <option value="1" <?= (int)$row['ativo'] === 1 ? 'selected' : '' ?> data-admin-t="state_active">Ativo</option>
                    <option value="0" <?= (int)$row['ativo'] !== 1 ? 'selected' : '' ?> data-admin-t="state_inactive">Inativo</option>
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
