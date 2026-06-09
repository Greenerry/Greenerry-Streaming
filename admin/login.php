<?php
require_once '../includes/config.php';

if (is_admin_logged_in()) {
    header('Location: ' . admin_default_page(current_admin($conn)));
    exit;
}

$err = '';
$emailValue = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $entryKey = trim((string)($_POST['admin_entry_key'] ?? ''));
    $emailValue = trim((string)($_POST['email'] ?? ''));
    $password = (string)($_POST['senha'] ?? '');

    $err = verify_csrf_request()
        ?? (!admin_entry_key_configured() ? tr('error.admin_entry_not_configured') : null)
        ?? ($entryKey === '' ? tr('error.admin_entry_required') : null)
        ?? (!admin_entry_key_matches($entryKey) ? tr('error.admin_entry_invalid') : null)
        ?? validate_email($emailValue)
        ?? ($password === '' ? tr('error.required_password') : null);

    if (!$err) {
        $admin = db_one_prepared($conn, "SELECT * FROM admin WHERE email = ? AND ativo = 1 LIMIT 1", 's', [$emailValue]);

        if (!$admin || !password_matches($password, $admin['palavra_passe'])) {
            $err = tr('error.invalid_admin_credentials');
        } else {
            db_prepared($conn, "UPDATE admin SET ultimo_login = NOW() WHERE idAdmin = ?", 'i', [(int)$admin['idAdmin']]);
            login_admin_session($admin);
            header('Location: ' . admin_default_page($admin));
            exit;
        }
    }
}

include '../includes/header.php';
?>

<section class="auth-shell auth-shell--narrow auth-shell--centered">
  <div class="auth-panel auth-panel--form auth-panel--form-only">
    <div class="auth-card auth-card--premium">
      <div class="auth-card-head">
        <h2 data-t="login_admin_type">Administração</h2>
        <p><?= h(current_lang() === 'en' ? 'Reserved access for the Greenerry team.' : 'Acesso reservado para a equipa Greenerry.') ?></p>
      </div>

      <?php if ($err): ?>
        <div class="alert alert-err"><?= h($err) ?></div>
      <?php endif; ?>

      <form method="post" class="auth-form" novalidate>
        <?= csrf_input() ?>

        <div class="fg">
          <label class="flabel" for="admin_entry_key"><?= h(current_lang() === 'en' ? 'Administrative entry key' : 'Chave de acesso administrativo') ?></label>
          <input id="admin_entry_key" type="password" name="admin_entry_key" class="finput" required maxlength="120" autocomplete="off">
        </div>

        <div class="fg">
          <label class="flabel" for="email">Email</label>
          <input id="email" type="email" name="email" class="finput" required maxlength="150" autocomplete="email" value="<?= h($emailValue) ?>">
        </div>

        <div class="fg">
          <label class="flabel" for="senha" data-t="login_password">Palavra-passe</label>
          <input id="senha" type="password" name="senha" class="finput" required minlength="8" autocomplete="current-password">
        </div>

        <button type="submit" class="btn btn-dark btn-full btn-lg" data-t="login_submit">Entrar</button>
      </form>

      <p class="auth-foot-note auth-foot-note--center">
        <a href="../pages/login.php" data-t="login_user_type">Utilizador / Artista</a>
      </p>
    </div>
  </div>
</section>

<?php include '../includes/footer.php'; ?>
